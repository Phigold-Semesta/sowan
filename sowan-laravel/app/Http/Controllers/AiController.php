<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\PetugasTujuan;
use App\Models\Tamu;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * AI Scanner SOWAN, alur dua tahap:
 *   1) processAi  : menganalisis video (wajah + suara), TIDAK menyimpan apa pun.
 *   2) konfirmasi : menyimpan tamu + kunjungan setelah petugas memeriksa formulir.
 *
 * Tamu cukup berdiri di depan webcam dan berbicara. Gmail TIDAK perlu diucapkan:
 *   - Tamu lama (wajah dikenali): semua data diambil dari database.
 *   - Tamu baru tanpa gmail     : dibuatkan gmail sementara (@walkin.sowan.local).
 */
class AiController extends Controller
{
    private const GELAR = ['pak', 'bapak', 'bu', 'ibu', 'mas', 'mbak', 'kak', 'dr', 'ir', 'drs', 'bpk', 'saudara'];

    // SESUAIKAN nama view dengan folder resources/views SOWAN Anda
    private const VIEW_KAMERA = 'petugas.ai_scanner.kamera';

    private const DOMAIN_SEMENTARA = 'walkin.sowan.local';
    private const MASA_SESI_MENIT = 15;

    public function kamera(): View
    {
        return view(self::VIEW_KAMERA);
    }

    // ------------------------------------------------------------------
    // WAJAH
    // ------------------------------------------------------------------

    private function hitungKemiripanWajah(array $a, array $b): float
    {
        $n = count($a);
        if ($n === 0 || $n !== count($b)) {
            return 0.0;
        }

        $dot = $na = $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $na += $x * $x;
            $nb += $y * $y;
        }

        return ($na == 0.0 || $nb == 0.0) ? 0.0 : $dot / (sqrt($na) * sqrt($nb));
    }

    /** Tahan terhadap data berbentuk array, JSON, atau JSON ter-encode ganda. */
    private function ambilVektor(mixed $nilai): ?array
    {
        for ($i = 0; $i < 2 && is_string($nilai); $i++) {
            $nilai = json_decode($nilai, true);
        }
        return (is_array($nilai) && count($nilai) > 0) ? $nilai : null;
    }

    /** Array bila model punya $casts, JSON string bila tidak. */
    private function nilaiSimpanVektor(array $vektor): array|string
    {
        return (new Tamu())->hasCast('face_embedding') ? $vektor : json_encode($vektor);
    }

    /** @return array{0: ?Tamu, 1: float} */
    private function cariTamuByWajah(array $embeddingBaru): array
    {
        $bestScore = 0.0;
        $bestTamu = null;

        // face_embedding ada di $hidden (tidak ke JSON), tetapi tetap terbaca di server
        foreach (Tamu::whereNotNull('face_embedding')->get() as $tamuDb) {
            if ($v = $this->ambilVektor($tamuDb->face_embedding)) {
                $score = $this->hitungKemiripanWajah($embeddingBaru, $v);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestTamu = $tamuDb;
                }
            }
        }

        return [$bestTamu, $bestScore];
    }

    // ------------------------------------------------------------------
    // UTILITAS
    // ------------------------------------------------------------------

    private function bersihNilai(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }
        $s = trim((string) $nilai);
        return in_array(Str::lower($s), ['', 'null', '-', 'tidak disebutkan', 'tidak ada'], true) ? null : $s;
    }

    private function namaPetugas(PetugasTujuan $p): string
    {
        return (string) ($p->nama_petugas ?? $p->nama_tujuan ?? $p->nama ?? '');
    }

    private function aliasPetugas(PetugasTujuan $p): array
    {
        return isset($p->alias) ? array_values(array_filter(array_map('trim', explode(',', (string) $p->alias)))) : [];
    }

    private function gmailSementara(): string
    {
        return 'walkin-' . Str::lower(Str::random(10)) . '@' . self::DOMAIN_SEMENTARA;
    }

    private function isGmailSementara(?string $gmail): bool
    {
        return $gmail !== null && str_ends_with($gmail, '@' . self::DOMAIN_SEMENTARA);
    }

    // ------------------------------------------------------------------
    // PENCOCOKAN PETUGAS
    // ------------------------------------------------------------------

    private function normalisasiNama(string $nama): string
    {
        $nama = preg_replace('/[^a-z\s]/', ' ', Str::lower(trim($nama)));
        $kata = array_filter(preg_split('/\s+/', $nama), fn ($k) => $k !== '' && !in_array($k, self::GELAR, true));
        return implode(' ', $kata);
    }

    private function skorNama(string $didengar, string $master): float
    {
        $a = $this->normalisasiNama($didengar);
        $b = $this->normalisasiNama($master);
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }

        $kataA = explode(' ', $a);
        $kataB = explode(' ', $b);
        foreach ($kataA as $ka) {
            if (strlen($ka) >= 3 && in_array($ka, $kataB, true)) {
                return 0.9;
            }
        }

        similar_text($a, $b, $persen);
        $skor = $persen / 100;

        foreach ($kataA as $ka) {
            foreach ($kataB as $kb) {
                if (strlen($ka) >= 3 && strlen($kb) >= 3) {
                    similar_text($ka, $kb, $p);
                    $skor = max($skor, $p / 100 * 0.95);
                }
            }
        }

        return $skor;
    }

    /** @return array{0: ?PetugasTujuan, 1: float} */
    private function cariPetugasTujuan(?string $cocokPython, ?string $disebut): array
    {
        $kandidat = array_values(array_filter([$cocokPython, $disebut]));
        if (!$kandidat) {
            return [null, 0.0];
        }

        $threshold = (float) env('PETUGAS_MATCH_THRESHOLD', 0.75);
        $hasil = [];

        foreach (PetugasTujuan::all() as $petugas) {
            $namaMaster = array_filter(array_merge([$this->namaPetugas($petugas)], $this->aliasPetugas($petugas)));

            $best = 0.0;
            foreach ($namaMaster as $nm) {
                foreach ($kandidat as $kd) {
                    $best = max($best, $this->skorNama($kd, $nm));
                }
            }
            $hasil[] = ['petugas' => $petugas, 'skor' => $best];
        }

        usort($hasil, fn ($x, $y) => $y['skor'] <=> $x['skor']);

        if (!$hasil || $hasil[0]['skor'] < $threshold) {
            return [null, $hasil[0]['skor'] ?? 0.0];
        }
        // Dua petugas sama kuat: jangan menebak
        if (isset($hasil[1]) && abs($hasil[0]['skor'] - $hasil[1]['skor']) < 0.001) {
            return [null, $hasil[0]['skor']];
        }

        return [$hasil[0]['petugas'], $hasil[0]['skor']];
    }

    // ------------------------------------------------------------------
    // DAFTAR UNTUK DROPDOWN
    // ------------------------------------------------------------------

    /** Daftar layanan. Mengenali nama kolom label secara otomatis bila bukan nama_layanan. */
    private function daftarLayanan(): array
    {
        return DB::table('layanan')->get()->map(function ($row) {
            $a = (array) $row;
            $id = $a['id_layanan'] ?? reset($a);

            $nama = $a['nama_layanan'] ?? $a['nama'] ?? $a['layanan'] ?? null;
            if ($nama === null) {
                foreach ($a as $kolom => $nilai) {
                    if ($kolom !== 'id_layanan' && is_string($nilai) && !str_ends_with($kolom, '_at')) {
                        $nama = $nilai;
                        break;
                    }
                }
            }

            return ['id_layanan' => $id, 'nama' => $nama ?? ('Layanan #' . $id)];
        })->sortBy('id_layanan')->values()->all();
    }

    private function daftarPetugasDropdown(): array
    {
        return PetugasTujuan::all()
            ->map(fn ($p) => ['id_petugas' => $p->id_petugas, 'nama_petugas' => $this->namaPetugas($p)])
            ->filter(fn ($p) => $p['nama_petugas'] !== '')
            ->sortBy('nama_petugas', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------
    // TAHAP 1: ANALISIS (belum menyimpan apa pun ke database)
    // ------------------------------------------------------------------

    public function processAi(Request $request): JsonResponse
    {
        @set_time_limit(240);

        $request->validate([
            'media_file' => 'required|file|mimetypes:video/webm,audio/webm,video/x-matroska,video/mp4,video/quicktime,video/x-msvideo|max:20480',
        ]);

        $file = $request->file('media_file');
        $pythonAiUrl = env('PYTHON_AI_URL', 'http://127.0.0.1:8001/api/v1/analyze');

        $daftarPetugas = PetugasTujuan::all()
            ->map(fn ($p) => ['nama_petugas' => $this->namaPetugas($p), 'alias' => implode(',', $this->aliasPetugas($p))])
            ->filter(fn ($p) => $p['nama_petugas'] !== '')
            ->values()
            ->all();

        try {
            $response = Http::timeout(180)
                ->connectTimeout(10)
                ->acceptJson()
                ->attach('media_file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($pythonAiUrl, ['daftar_petugas' => json_encode($daftarPetugas)]);
        } catch (ConnectionException $e) {
            Log::error('AI Engine Connection Error: ' . $e->getMessage());
            $timeout = str_contains($e->getMessage(), 'cURL error 28');

            return response()->json([
                'status'  => 'error',
                'message' => $timeout
                    ? 'AI Engine terlalu lama memproses (timeout). Coba rekaman yang lebih pendek.'
                    : 'AI Engine Python tidak dapat dihubungi. Pastikan uvicorn berjalan di port 8001.',
                'detail'  => $e->getMessage(),
            ], 503);
        }

        if ($response->failed()) {
            Log::error('AI Engine Python Error', [
                'status' => $response->status(),
                'body'   => Str::limit($response->body(), 2000),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'AI Engine Python membalas dengan error (HTTP ' . $response->status() . ').',
                'detail'  => Str::limit($response->body(), 500),
            ], 502);
        }

        $aiResult = $response->json();

        if (!is_array($aiResult)) {
            Log::error('AI Engine: balasan Python bukan JSON', ['body' => Str::limit($response->body(), 2000)]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Balasan AI Engine bukan JSON yang valid.',
                'detail'  => Str::limit($response->body(), 500),
            ], 502);
        }

        $aiData        = $aiResult['data'] ?? [];
        $tamuData      = $aiData['tamu'] ?? [];
        $kunjunganData = $aiData['kunjungan'] ?? [];
        $newEmbedding  = $this->ambilVektor($aiData['face_embedding'] ?? null);
        $debug         = $aiResult['debug'] ?? [];
        $threshold     = (float) env('FACE_MATCH_THRESHOLD', 0.70);

        // Identifikasi MURNI berdasarkan wajah (bukan nama / gmail)
        [$terdekat, $skor] = $newEmbedding ? $this->cariTamuByWajah($newEmbedding) : [null, 0.0];
        $dikenali = $terdekat !== null && $skor >= $threshold;

        $petugasDisebut = $this->bersihNilai($kunjunganData['petugas_disebut'] ?? null);
        [$petugas, $skorPetugas] = $this->cariPetugasTujuan(
            $this->bersihNilai($kunjunganData['petugas_cocok'] ?? null),
            $petugasDisebut
        );

        // Data sementara disimpan di SESSION server (vektor wajah tidak dikirim ke browser).
        // Memakai session karena pasti aktif, tidak bergantung pada tabel cache.
        $token = (string) Str::uuid();
        $request->session()->put('ai_scan', [
            'token'     => $token,
            'embedding' => $newEmbedding,
            'gmail'     => $dikenali ? $terdekat->gmail : null,
            'expires'   => now()->addMinutes(self::MASA_SESI_MENIT)->timestamp,
        ]);

        $gmailTamu = $dikenali ? $terdekat->gmail : null;

        return response()->json([
            'status' => 'success',
            'token'  => $token,
            'data'   => [
                'kategori'        => $dikenali ? 'Tamu Lama' : 'Tamu Baru',
                'gmail'           => $gmailTamu,
                'gmail_sementara' => $this->isGmailSementara($gmailTamu),
                'nama_tamu'       => $dikenali ? $terdekat->nama_tamu     : $this->bersihNilai($tamuData['nama_tamu'] ?? null),
                'no_wa'           => $dikenali ? $terdekat->no_wa         : $this->bersihNilai($tamuData['no_wa'] ?? null),
                'jenis_tamu'      => $dikenali ? $terdekat->jenis_tamu    : null,
                'nama_instansi'   => $dikenali ? $terdekat->nama_instansi : $this->bersihNilai($tamuData['nama_instansi'] ?? null),
                'alamat_kantor'   => $dikenali ? $this->bersihNilai($terdekat->alamat_kantor) : null,
                'hadir_sebagai'   => $dikenali ? $this->bersihNilai($terdekat->hadir_sebagai) : null,
                'id_petugas'      => $petugas?->id_petugas,
                'petugas_disebut' => $petugasDisebut,
                'keperluan'       => $this->bersihNilai($kunjunganData['keterangan_keperluan'] ?? null),
            ],
            'ai_debug' => [
                'transkrip'         => $debug['transkrip'] ?? '',
                'sumber_ekstraksi'  => $debug['sumber_ekstraksi'] ?? null,
                'wajah_terdeteksi'  => $debug['wajah_terdeteksi'] ?? ($newEmbedding !== null),
                'audio_ada'         => $debug['audio_ada'] ?? null,
                'gemini_error'      => $debug['gemini_error'] ?? null,
                'galeri_bertambah'  => $debug['galeri_bertambah'] ?? null,
                'skor_kemiripan'    => round($skor, 3),
                'ambang_wajah'      => $threshold,
                'tamu_terdekat'     => $terdekat ? $terdekat->nama_tamu : null,
                'petugas_terdaftar' => $petugas !== null,
                'skor_petugas'      => round($skorPetugas, 3),
            ],
            'daftar_petugas' => $this->daftarPetugasDropdown(),
            'daftar_layanan' => $this->daftarLayanan(),
        ]);
    }

    // ------------------------------------------------------------------
    // TAHAP 2: KONFIRMASI & SIMPAN
    // ------------------------------------------------------------------

    public function konfirmasi(Request $request): JsonResponse
    {
        // Rapikan masukan sebelum divalidasi
        $request->merge([
            'no_wa' => preg_replace('/[^0-9+]/', '', (string) $request->input('no_wa', '')),
            'gmail' => Str::lower(trim((string) $request->input('gmail', ''))) ?: null,
        ]);

        $v = $request->validate([
            'token'         => 'required|string',
            'gmail'         => 'nullable|email|max:255',
            'nama_tamu'     => 'required|string|max:100',
            'no_wa'         => ['required', 'regex:/^\+?[0-9]{8,20}$/'],
            'jenis_tamu'    => 'required|in:Penyedia,Non-Penyedia',
            'nama_instansi' => 'nullable|string|max:100',
            'alamat_kantor' => 'nullable|string',
            'hadir_sebagai' => 'nullable|string|max:50',
            'id_layanan'    => 'required|integer|exists:layanan,id_layanan',
            'id_petugas'    => 'required|integer|exists:petugas_tujuan,id_petugas',
            'keperluan'     => 'nullable|string|max:2000',
        ], [
            'no_wa.required' => 'No. WhatsApp wajib diisi.',
            'no_wa.regex'    => 'No. WhatsApp tidak valid (8 sampai 20 angka).',
            'gmail.email'    => 'Format gmail/email tidak valid.',
        ]);

        $sesi = $request->session()->get('ai_scan');
        if (
            !$sesi
            || !hash_equals((string) $sesi['token'], (string) $v['token'])
            || now()->timestamp > (int) $sesi['expires']
        ) {
            return response()->json(['status' => 'error', 'message' => 'Sesi kedaluwarsa. Silakan rekam ulang.'], 410);
        }

        // Tamu lama: gmail dari database. Tamu baru: gmail ketikan petugas, atau gmail sementara.
        $gmail = $sesi['gmail'] ?: ($v['gmail'] ?? $this->gmailSementara());

        $atribut = [
            'nama_tamu'     => $v['nama_tamu'],
            'no_wa'         => $v['no_wa'],
            'jenis_tamu'    => $v['jenis_tamu'],
            'nama_instansi' => $v['nama_instansi'] ?? null,
            'alamat_kantor' => $v['alamat_kantor'] ?? null,
            'hadir_sebagai' => $v['hadir_sebagai'] ?? null,
        ];

        try {
            $kunjungan = DB::transaction(function () use ($gmail, $atribut, $sesi, $v) {
                $tamu = Tamu::find($gmail);

                if ($tamu) {
                    // Hanya timpa kolom yang terisi, agar data lama tidak hilang
                    $tamu->update(array_filter($atribut, fn ($x) => $x !== null));

                    // Wajah lama tidak ditimpa; hanya diisi bila tamu belum punya data wajah
                    if (empty($tamu->face_embedding) && $sesi['embedding']) {
                        $tamu->update(['face_embedding' => $this->nilaiSimpanVektor($sesi['embedding'])]);
                    }
                } else {
                    // Kolom NOT NULL yang tidak bisa diambil dari suara diberi isian bawaan '-'
                    $tamu = Tamu::create(array_merge(
                        ['gmail' => $gmail],
                        $atribut,
                        [
                            'alamat_kantor'  => $atribut['alamat_kantor'] ?? '-',
                            'hadir_sebagai'  => $atribut['hadir_sebagai'] ?? '-',
                            'face_embedding' => $sesi['embedding'] ? $this->nilaiSimpanVektor($sesi['embedding']) : null,
                        ]
                    ));
                }

                return Kunjungan::create([
                    'gmail'                => $tamu->gmail,
                    'id_layanan'           => $v['id_layanan'],
                    'id_petugas'           => $v['id_petugas'],
                    'waktu_masuk'          => now(),
                    'status'               => 'Belum Dilayani',
                    'keterangan_keperluan' => $v['keperluan'] ?? null,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('AI konfirmasi gagal: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan ke database.',
                'detail'  => $e->getMessage(),
            ], 500);
        }

        $request->session()->forget('ai_scan');

        return response()->json([
            'status'          => 'success',
            'message'         => 'Kunjungan tersimpan.',
            'id'              => $kunjungan->getKey(),
            'gmail_sementara' => $this->isGmailSementara($gmail),
        ]);
    }
}