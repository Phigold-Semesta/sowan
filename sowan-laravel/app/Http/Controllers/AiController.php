<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\PetugasTujuan;
use App\Models\Tamu;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AiController extends Controller
{
    private const NAMA_PLACEHOLDER = ['', 'Tamu Tidak Dikenal', 'Tamu Otomatis AI'];
    private const INSTANSI_PLACEHOLDER = ['', 'Instansi Pribadi', 'Instansi Pribadi / Umum', 'Instansi Umum (AI Scanner)'];
    private const GELAR = ['pak', 'bapak', 'bu', 'ibu', 'mas', 'mbak', 'kak', 'dr', 'ir', 'drs', 'bpk', 'saudara'];

    public function dashboard(): View
    {
        $totalKunjungan = Kunjungan::count();
        $kunjunganHariIni = Kunjungan::whereDate('waktu_masuk', today())->count();
        $totalTamu = Tamu::count();
        $totalPetugas = User::count();
        $kunjunganTerbaru = Kunjungan::with('tamu')->latest('waktu_masuk')->take(10)->get();

        return view('ai_scanner.dashboard', compact(
            'totalKunjungan', 'kunjunganHariIni', 'totalTamu', 'totalPetugas', 'kunjunganTerbaru'
        ));
    }

    public function kamera(): View
    {
        return view('petugas.ai_scanner.kamera');
    }

    public function dataTamu(): View
    {
        $daftarKunjungan = Kunjungan::with('tamu')->latest('waktu_masuk')->paginate(10);
        return view('ai_scanner.tabel', ['kunjungan' => $daftarKunjungan]);
    }

    // ------------------------------------------------------------------
    // LOGIKA PENCOCOKAN WAJAH (COSINE SIMILARITY)
    // ------------------------------------------------------------------

    private function hitungKemiripanWajah(array $vec1, array $vec2): float
    {
        $n = count($vec1);
        if ($n === 0 || $n !== count($vec2)) {
            return 0.0;
        }

        $dot = $normA = $normB = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $a = (float) $vec1[$i];
            $b = (float) $vec2[$i];
            $dot += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        return ($normA == 0.0 || $normB == 0.0) ? 0.0 : $dot / (sqrt($normA) * sqrt($normB));
    }

    private function ambilVektor(mixed $nilai): ?array
    {
        $vec = is_string($nilai) ? json_decode($nilai, true) : $nilai;
        return (is_array($vec) && count($vec) > 0) ? $vec : null;
    }

    private function cariTamuByWajah(array $embeddingBaru): array
    {
        $bestScore = 0.0;
        $bestTamu = null;

        // Hanya mencari dari kolom face_embedding di tabel tamu
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
    // UTILITAS NLP (PEMBERSIHAN TEKS & PENCARIAN NAMA PETUGAS)
    // ------------------------------------------------------------------

    private function bersihNilai(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }
        $s = trim((string) $nilai);
        return in_array(Str::lower($s), ['', 'null', '-', 'tidak disebutkan', 'tidak ada'], true) ? null : $s;
    }

    private function isPlaceholder(?string $nilai, array $daftar): bool
    {
        return $nilai === null || in_array(trim($nilai), $daftar, true);
    }

    private function normalisasiNama(string $nama): string
    {
        $nama = Str::lower(trim($nama));
        $nama = preg_replace('/[^a-z\s]/', ' ', $nama);
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

    private function cariPetugasTujuan(?string $cocokPython, ?string $disebut): array
    {
        $kandidat = array_values(array_filter([$cocokPython, $disebut]));
        if (!$kandidat) {
            return [null, 0.0];
        }

        $threshold = (float) env('PETUGAS_MATCH_THRESHOLD', 0.75);
        $hasil = [];

        // Menggunakan model PetugasTujuan
        foreach (PetugasTujuan::all() as $petugas) {
            $namaMaster = array_filter([
                $petugas->nama_petugas ?? $petugas->nama_tujuan ?? $petugas->nama ?? ''
            ]);

            $best = 0.0;
            foreach ($namaMaster as $nm) {
                if ($nm === '') continue;
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

        return [$hasil[0]['petugas'], $hasil[0]['skor']];
    }

    // ------------------------------------------------------------------
    // PROSES UTAMA (API KE PYTHON & PENYIMPANAN DB)
    // ------------------------------------------------------------------

    public function processAi(Request $request): JsonResponse
    {
        @set_time_limit(240);

        $request->validate([
            'media_file' => 'required|file|mimetypes:video/webm,audio/webm,video/x-matroska,video/mp4,video/quicktime,video/x-msvideo|max:20480',
        ]);

        $file = $request->file('media_file');
        $pythonAiUrl = env('PYTHON_AI_URL', 'http://127.0.0.1:8001/api/v1/analyze');

        // Mengambil daftar petugas dari model PetugasTujuan
        $daftarPetugas = PetugasTujuan::all()->map(function ($p) {
            return ['nama_petugas' => $p->nama_petugas ?? $p->nama_tujuan ?? $p->nama ?? ''];
        })->filter(fn($p) => $p['nama_petugas'] !== '')->values()->all();

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

        $namaAi     = $this->bersihNilai($tamuData['nama_tamu'] ?? null);
        $instansiAi = $this->bersihNilai($tamuData['nama_instansi'] ?? null);
        $waAi       = $this->bersihNilai($tamuData['no_wa'] ?? null);
        $threshold  = (float) env('FACE_MATCH_THRESHOLD', 0.70);

        $petugasDisebut = $this->bersihNilai($kunjunganData['petugas_disebut'] ?? null);
        $petugasCocokPy = $this->bersihNilai($kunjunganData['petugas_cocok'] ?? null);
        
        [$petugasMaster, $skorPetugas] = $this->cariPetugasTujuan($petugasCocokPy, $petugasDisebut);

        $petugasTujuanTeks = $petugasMaster->nama_petugas ?? $petugasMaster->nama_tujuan ?? $petugasMaster->nama ?? $petugasDisebut ?? 'Administrator LPSE';

        try {
            [$tamu, $kunjungan, $kategoriTamu, $skor, $terdekat] = DB::transaction(
                function () use (
                    $newEmbedding, $namaAi, $instansiAi, $waAi, $kunjunganData, $threshold,
                    $petugasTujuanTeks
                ) {
                    $kategoriTamu = 'Tamu Baru';
                    $tamu = null;
                    $bestScore = 0.0;
                    $terdekat = null;

                    if ($newEmbedding) {
                        [$terdekat, $bestScore] = $this->cariTamuByWajah($newEmbedding);

                        if ($terdekat && $bestScore >= $threshold) {
                            $tamu = $terdekat;
                            $kategoriTamu = 'Tamu Lama';
                        }
                    }

                    if ($tamu) {
                        $update = [];
                        if ($namaAi && $this->isPlaceholder($tamu->nama_tamu, self::NAMA_PLACEHOLDER)) {
                            $update['nama_tamu'] = $namaAi;
                        }
                        if ($instansiAi && $this->isPlaceholder($tamu->nama_instansi, self::INSTANSI_PLACEHOLDER)) {
                            $update['nama_instansi'] = $instansiAi;
                        }
                        if ($waAi && empty($tamu->no_wa)) {
                            $update['no_wa'] = $waAi;
                        }
                        
                        // Perbarui vektor wajah jika yang baru lebih baik/jelas namun belum menimpa
                        if ($newEmbedding && $bestScore < 0.95) {
                            $update['face_embedding'] = json_encode($newEmbedding);
                        }

                        if ($update) {
                            $tamu->update($update);
                        }
                    } else {
                        $tamu = Tamu::create([
                            'nama_tamu'      => $namaAi ?? 'Tamu Tidak Dikenal',
                            'no_wa'          => $waAi,
                            'nama_instansi'  => $instansiAi ?? 'Instansi Pribadi',
                            'face_embedding' => $newEmbedding ? json_encode($newEmbedding) : null,
                        ]);
                    }

                    $kunjungan = Kunjungan::create([
                        'tamu_id'               => $tamu->id,
                        'user_id'               => null,
                        'waktu_masuk'           => now(),
                        'kategori_tamu'         => $kategoriTamu,
                        'petugas_tujuan'        => $petugasTujuanTeks,
                        'keterangan_keperluan'  => $this->bersihNilai($kunjunganData['keterangan_keperluan'] ?? null) ?? '-',
                        'status'                => 'Belum Dilayani',
                    ]);

                    return [$tamu, $kunjungan, $kategoriTamu, $bestScore, $terdekat];
                }
            );
        } catch (Throwable $e) {
            Log::error('AI DB Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Hasil AI diterima, tetapi gagal disimpan ke database.',
                'detail'  => $e->getMessage(),
            ], 500);
        }

        $tamu->makeHidden('face_embedding');

        return response()->json([
            'status'  => 'success',
            'message' => 'Analisis AI berhasil diproses dan disimpan!',
            'data'    => [
                'tamu'      => $tamu,
                'kunjungan' => $kunjungan,
                'ai_debug'  => [
                    'transkrip'          => $debug['transkrip'] ?? '',
                    'sumber_ekstraksi'   => $debug['sumber_ekstraksi'] ?? null,
                    'wajah_terdeteksi'   => $debug['wajah_terdeteksi'] ?? ($newEmbedding !== null),
                    'audio_ada'          => $debug['audio_ada'] ?? null,
                    'gemini_error'       => $debug['gemini_error'] ?? null,
                    'skor_kemiripan'     => round($skor, 3),
                    'ambang_wajah'       => $threshold,
                    'tamu_terdekat'      => $terdekat ? ('#' . $terdekat->id . ' ' . $terdekat->nama_tamu) : null,
                    'petugas_disebut'    => $petugasDisebut,
                    'petugas_terdaftar'  => $petugasMaster !== null,
                    'skor_petugas'       => round($skorPetugas, 3),
                ],
            ],
        ]);
    }
}