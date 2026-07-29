<?php

namespace App\Http\Controllers;

use App\Models\Tamu;
use App\Models\Kunjungan;
use App\Models\Layanan;
use App\Models\PetugasTujuan;
use App\Models\Konsultasi;
use app\models\RatingLayanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class TamuController extends Controller
{
    /**
     * Tampilan Dashboard Utama Pada Tamu Online
     */
    public function dashboard()
    {
        $tamu = Auth::guard('tamu')->user();
        
        if (!$tamu) {
            return redirect()->route('tamu.login.view')->with('error', 'Sesi Anda telah berakhir.');
        }
        
        // Query riwayat kunjungan khusus tamu yang sedang login
        $riwayatKunjunganQuery = Kunjungan::where('gmail', $tamu->gmail)
                                    ->orderBy('waktu_masuk', 'desc');

        $totalKunjungan = $riwayatKunjunganQuery->count();
        $kunjunganTerakhirRecord = $riwayatKunjunganQuery->first();
        
        $riwayatKunjungan = $riwayatKunjunganQuery->paginate(5);

        // Menggunakan model Layanan dengan relasi dokumens untuk modal buku pedoman
        $dokumenPedoman = Layanan::with('dokumens')->get();

        return view('tamu.dashboard', compact(
            'tamu', 
            'riwayatKunjungan', 
            'totalKunjungan', 
            'kunjunganTerakhirRecord', 
            'dokumenPedoman'
        ));
    }

    /**
     * Tampilan halaman validasi pilihan bagi tamu lama (Onsite)
     */
    public function validasiLamaIndex()
    {
        $tamu = Auth::guard('tamu')->user();
        
        if (!$tamu) {
            return redirect()->route('tamu.onsite.view')->with('error', 'Sesi Anda telah berakhir.');
        }

        return view('tamu.validasi_lama', compact('tamu'));
    }

    /**
     * Memproses pilihan dari halaman validasi tamu lama
     */
    public function prosesValidasiLama(Request $request)
    {
        $request->validate([
            'pilihan' => 'required|in:lama,baru'
        ]);

        if ($request->pilihan === 'lama') {
            // Jika memilih menggunakan data lama, langsung arahkan ke form isi kunjungan lama
            return redirect()->route('tamu.form_tamu_lama');
        } else {
            // Jika memilih ingin memperbarui/mengisi data baru, arahkan ke form edit profil/tamu baru
            return redirect()->route('tamu.form_tamu_baru')->with('info', 'Silakan perbarui data Anda.');
        }
    }

    /**
     * Tampilan Halaman Riwayat Kunjungan Tamu
     */
    public function riwayat()
    {
        $tamu = Auth::guard('tamu')->user();

        if (!$tamu) {
            return redirect()->route('tamu.login.view')->with('error', 'Sesi Anda telah berakhir.');
        }

        // Mengambil seluruh riwayat kunjungan khusus untuk tamu yang sedang login
        $riwayatKunjungan = Kunjungan::where('gmail', $tamu->gmail)
                                   ->orderBy('waktu_masuk', 'desc')
                                   ->get();

        return view('tamu.riwayat.index', compact('tamu', 'riwayatKunjungan'));
    }

    /**
     * Tampilan Halaman Konsultasi Online (List + Form Dropdown)
     */
    public function konsultasiOnline()
    {
        $tamu = Auth::guard('tamu')->user();
        if (!$tamu) {
            return redirect()->route('tamu.login.view')->with('error', 'Sesi Anda telah berakhir.');
        }
        
        // Perbaikan: Pastikan relasi 'user' dimuat agar nama pemateri muncul di sisi tamu
        $jadwal_konsultasi = \App\Models\Konsultasi::with(['user', 'layanan'])
                                                ->where('gmail', $tamu->gmail)
                                                ->orderBy('created_at', 'desc') 
                                                ->get();

        $layanan = Layanan::orderBy('nama_layanan', 'asc')->get();
        
        $petugas = \App\Models\User::whereIn('role', ['administrator', 'petugas', 'pimpinan'])
                                ->orderBy('nama_lengkap', 'asc')
                                ->get();

        return view('tamu.konsultasi_online.index', compact('tamu', 'jadwal_konsultasi', 'layanan', 'petugas'));
    }

    /**
     * Menyimpan pengajuan janji konsultasi baru oleh tamu
     */
    public function simpanKonsultasi(Request $request)
    {
        $tamu = Auth::guard('tamu')->user();

        $validated = $request->validate([
            'id_layanan'       => 'required|exists:layanan,id_layanan',
            'id_petugas'       => 'required|exists:user,id_user', // Pastikan ID ini merujuk ke tabel users
            'waktu_mulai'      => 'required|date|after:now',
            'topik_konsultasi' => 'required|string|max:255',
            'durasi_menit'     => 'required|integer|min:15|max:90',
        ]);

        // Perbaikan: Menggunakan Model Konsultasi::create() untuk sinkronisasi mass assignment
        \App\Models\Konsultasi::create([
            'gmail'            => $tamu->gmail,
            'id_layanan'       => $validated['id_layanan'],
            'id_user'          => $validated['id_petugas'],
            'topik_konsultasi' => $validated['topik_konsultasi'],
            'waktu_mulai'      => $validated['waktu_mulai'],
            'durasi_menit'     => $validated['durasi_menit'],
            'status'           => 'pending',
        ]);

        return redirect()->route('tamu.konsultasi_online.index')
                         ->with('success', 'Janji konsultasi berhasil diajukan, silakan tunggu konfirmasi petugas.');
    }

    /**
     * Memperbarui pengajuan janji konsultasi oleh tamu
     */
    public function updateKonsultasi(Request $request, $id)
    {
        $tamu = Auth::guard('tamu')->user();
        
        // Validasi input
        $validated = $request->validate([
            'id_layanan'       => 'required|exists:layanan,id_layanan',
            'id_petugas'       => 'required|exists:user,id_user',
            'waktu_mulai'      => 'required|date|after:now',
            'topik_konsultasi' => 'required|string|max:255',
            'durasi_menit'     => 'required|integer|min:15|max:90',
        ]);

        $konsultasi = \App\Models\Konsultasi::where('id_konsultasi', $id)
                                        ->where('gmail', $tamu->gmail)
                                        ->where('status', 'pending') // Hanya bisa edit jika masih pending
                                        ->firstOrFail();

        $konsultasi->update([
            'id_layanan'       => $validated['id_layanan'],
            'id_user'          => $validated['id_petugas'],
            'topik_konsultasi' => $validated['topik_konsultasi'],
            'waktu_mulai'      => $validated['waktu_mulai'],
            'durasi_menit'     => $validated['durasi_menit'],
        ]);

        return redirect()->route('tamu.konsultasi_online.index')
                         ->with('success', 'Janji konsultasi berhasil diperbarui.');
    }

    /**
     * Menghapus pengajuan janji konsultasi oleh tamu
     */
    public function hapusKonsultasi($id)
    {
        $tamu = Auth::guard('tamu')->user();

        $konsultasi = \App\Models\Konsultasi::where('id_konsultasi', $id)
                                        ->where('gmail', $tamu->gmail)
                                        ->where('status', 'pending') // Hanya bisa hapus jika masih pending
                                        ->firstOrFail();

        $konsultasi->delete();

        return redirect()->route('tamu.konsultasi_online.index')
                         ->with('success', 'Janji konsultasi berhasil dihapus.');
    }

    /**
     * Tampilan form tamu (Resepsionis Utama)
     */
    public function index()
    {
        $tamu = Auth::guard('tamu')->user();
        
        if (!$tamu) {
            return redirect()->route('tamu.onsite.view')->with('error', 'Silakan masukkan email terlebih dahulu.');
        }

        $layanan = Layanan::with('dokumens')->orderBy('nama_layanan', 'asc')->get();
        $petugas = PetugasTujuan::orderBy('nama_petugas', 'asc')->get();
        
        $isNewGuest = ($tamu->nama_tamu === 'Tamu Baru' || is_null($tamu->no_wa));

        if ($isNewGuest) {
            return view('tamu.form_tamu_baru', ['gmail' => $tamu->gmail, 'layanan' => $layanan, 'petugas' => $petugas]);
        } else {
            // Tampilkan form kunjungan tamu lama
            return view('tamu.form_tamu_lama', compact('tamu', 'layanan', 'petugas'));
        }
    }

    /**
     * Tampilan Form Tamu Baru secara eksplisit
     */
    public function showFormBaru() 
    {
        $tamu = Auth::guard('tamu')->user();
        
        if (!$tamu) {
            return redirect()->route('tamu.onsite.view')->with('error', 'Sesi Anda telah berakhir.');
        }

        $layanan = Layanan::with('dokumens')->orderBy('nama_layanan', 'asc')->get();
        $petugas = PetugasTujuan::orderBy('nama_petugas', 'asc')->get();
        $gmail = $tamu->gmail;

        return view('tamu.form_tamu_baru', compact('gmail', 'layanan', 'petugas'));
    }

    /**
     * Tampilan Form Tamu Lama secara eksplisit
     */
    public function showFormLama() 
    {
        $tamu = Auth::guard('tamu')->user();
        
        if (!$tamu) {
            return redirect()->route('tamu.onsite.view')->with('error', 'Sesi Anda telah berakhir.');
        }

        $layanan = Layanan::with('dokumens')->orderBy('nama_layanan', 'asc')->get();
        $petugas = PetugasTujuan::orderBy('nama_petugas', 'asc')->get();

        return view('tamu.form_tamu_lama', compact('tamu', 'layanan', 'petugas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gmail'         => 'required|email',
            'nama_tamu'     => 'required|string',
            'id_layanan'    => 'required|exists:layanan,id_layanan',
            'id_petugas'    => 'required|exists:petugas_tujuan,id_petugas',
            'skor'          => 'nullable|integer',
            'komentar'      => 'nullable|string',
            'no_wa'         => 'nullable|string',
            'nama_instansi' => 'nullable|string',
            'alamat_kantor' => 'nullable|string',
            'hadir_sebagai' => 'nullable|string',
            'jenis_tamu' => 'nullable|in:penyedia,non-penyedia', // Validasi opsional untuk jenis_tamu
        ]);

        try {
            $tamuRecord = Tamu::where('gmail', $validated['gmail'])->first();
            $isNew = !$tamuRecord;
            
            $result = DB::transaction(function () use ($validated, $tamuRecord, $isNew) {
                $tamu = $tamuRecord ?? new Tamu(['gmail' => $validated['gmail']]);
                
                $tamu->fill([
                    'nama_tamu'     => $validated['nama_tamu'],
                    'no_wa'         => $validated['no_wa'] ?? $tamu->no_wa,
                    'nama_instansi' => $validated['nama_instansi'] ?? $tamu->nama_instansi,
                    'alamat_kantor' => $validated['alamat_kantor'] ?? $tamu->alamat_kantor,
                    'hadir_sebagai' => $validated['hadir_sebagai'] ?? $tamu->hadir_sebagai,
                ]);
                $tamu->save();

                $tanggalHariIni = now()->format('Y-m-d');
                $antreanTerakhir = Kunjungan::whereDate('waktu_masuk', $tanggalHariIni)->max('nomor_antrean') ?? 0;
                
                $kunjungan = Kunjungan::create([
                    'gmail'         => $tamu->gmail,
                    'id_layanan'    => $validated['id_layanan'],
                    'id_petugas'    => $validated['id_petugas'],
                    'waktu_masuk'   => now(),
                    'nomor_antrean' => $antreanTerakhir + 1,
                    'status'        => 'Belum Dilayani',
                ]);

                if (!empty($validated['skor'])) {
                    DB::table('rating_layanan')->insert([
                        'id_kunjungan' => $kunjungan->id_kunjungan,
                        'skor'         => $validated['skor'],
                        'komentar'     => $validated['komentar'] ?? null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }

                return [
                    'tamu'      => $tamu, 
                    'kunjungan' => $kunjungan, 
                    'isNew'     => $isNew
                ];
            });

            Auth::guard('tamu')->logout();

            // Tentukan rute sukses berdasarkan input tipe_tamu yang dikirimkan form
            $tipeTamu = $request->input('tipe_tamu');

            if ($tipeTamu === 'baru') {
                $routeName = 'tamu.success_baru';
            } elseif ($tipeTamu === 'lama') {
                $routeName = 'tamu.success_lama';
            } else {
                $routeName = $result['isNew'] ? 'tamu.success_baru' : 'tamu.success_lama';
            }
            
            // Mengirim antrean sebagai query parameter agar tidak hilang saat logout
            return redirect()->route($routeName, [
                'nama_tamu' => urlencode($result['tamu']->nama_tamu),
                'antrean'   => $result['kunjungan']->nomor_antrean
            ]);

        } catch (\Exception $e) {
            Log::error("Error saat simpan tamu: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

   /**
     * Tampilan halaman Rating dan Saran
     */
    public function ratingIndex()
    {
        $tamu = Auth::guard('tamu')->user();

        if (!$tamu) {
            return redirect()->route('tamu.login.view')->with('error', 'Sesi Anda telah berakhir.');
        }

        // Ambil seluruh riwayat data rating yang pernah diberikan oleh tamu ini agar tampil lengkap ke bawah
        $rating = DB::table('rating_layanan')
                    ->join('kunjungan', 'rating_layanan.id_kunjungan', '=', 'kunjungan.id_kunjungan')
                    ->where('kunjungan.gmail', $tamu->gmail)
                    ->select('rating_layanan.*', 'kunjungan.waktu_masuk')
                    ->orderBy('rating_layanan.created_at', 'desc')
                    ->get();

        return view('tamu.rating.index', compact('tamu', 'rating'));
    }

    /**
     * Menyimpan Rating dan Saran baru (Selalu membuat entri baru agar riwayat ulasan tidak tertimpa)
     */
    public function simpanRating(Request $request)
    {
        $tamu = Auth::guard('tamu')->user();

        if (!$tamu) {
            return redirect()->route('tamu.login.view')->with('error', 'Sesi Anda telah berakhir.');
        }

        $validated = $request->validate([
            'id_kunjungan' => 'nullable|exists:kunjungan,id_kunjungan',
            'skor'         => 'required|integer|min:1|max:5',
            'komentar'     => 'nullable|string',
        ]);

        $idKunjungan = $validated['id_kunjungan'] ?? null;

        if (!$idKunjungan) {
            $layananDefault = Layanan::first();
            $petugasDefault = PetugasTujuan::first();

            // Hitung nomor antrean harian secara otomatis
            $tanggalHariIni = now()->format('Y-m-d');
            $antreanTerakhir = Kunjungan::whereDate('waktu_masuk', $tanggalHariIni)->max('nomor_antrean') ?? 0;

            // Buat entri kunjungan baru khusus untuk setiap pengiriman rating/feedback agar riwayat bertambah terus
            $kunjunganBaru = Kunjungan::create([
                'gmail'         => $tamu->gmail,
                'id_layanan'    => $layananDefault ? $layananDefault->id_layanan : 1,
                'id_petugas'    => $petugasDefault ? $petugasDefault->id_petugas : 1,
                'waktu_masuk'   => now(),
                'nomor_antrean' => $antreanTerakhir + 1,
                'status'        => 'Belum Dilayani',
            ]);

            $idKunjungan = $kunjunganBaru->id_kunjungan;
        }

        // Simpan sebagai data baru (insert) agar riwayat penilaian sebelumnya tetap tampil tersusun ke bawah
        DB::table('rating_layanan')->insert([
            'id_kunjungan' => $idKunjungan,
            'skor'         => $validated['skor'],
            'komentar'     => $validated['komentar'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()->route('tamu.rating.index')
                         ->with('success', 'Terima kasih atas rating dan saran Anda!');
    }

    public function unduhPdfAntrean(Request $request)
{
    $data = [
        'antrean'   => $request->query('antrean', 'A-00'),
        'nama_tamu' => $request->query('nama_tamu', 'Tamu Terhormat'),
        'instansi'  => $request->query('instansi', '-'),
        'layanan'   => $request->query('layanan', 'Layanan Umum'),
        'petugas'   => $request->query('petugas', 'Petugas Piket'),
        'waktu'     => now()->format('d-m-Y H:i'),
    ];

    $pdf = Pdf::loadView('tamu.pdf_antrean', $data);
    return $pdf->download('Bukti-Antrean-' . $data['antrean'] . '.pdf');
}
}