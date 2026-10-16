<?php



use App\Http\Controllers\AuthController;

use App\Http\Controllers\TamuController;

use App\Http\Controllers\UserController;

use App\Http\Controllers\PetugasController;

use App\Http\Controllers\AiController;

use App\Http\Controllers\AdminController;

use App\Http\Controllers\PimpinanController;

 use App\Http\Controllers\ChatbotController;

use App\Http\Controllers\LiveChatController;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Auth;



/*

|--------------------------------------------------------------------------

| Web Routes - SOWAN v2 (Digital Guest Book LPSE Karawang)

|--------------------------------------------------------------------------

| Desain Sistem: Emerald Green Luxury

| Build with: Laravel 12

|--------------------------------------------------------------------------

*/
/*
|--------------------------------------------------------------------------
| Web Routes - SOWAN v2
|--------------------------------------------------------------------------
*/
// --- 0. RUTE UTAMA (ROOT) ---
// Memperbaiki masalah 404 saat akses localhost:8000/
Route::get('/', function () {
    return redirect()->route('tamu.onsite.view');
});

// --- 2. PORTAL PUBLIK (Tamu Online) ---
Route::prefix('portal')->name('tamu.')->group(function () {
    Route::get('/', [AuthController::class, 'showPublik'])->name('login.view'); 
    Route::post('/login', [AuthController::class, 'loginOnline'])->name('login.online');
    Route::get('/register', [AuthController::class, 'showSignup'])->name('register.view');
    Route::post('/register/store', [AuthController::class, 'registerOnline'])->name('register.store');
    Route::post('/check-email', [AuthController::class, 'checkEmail'])->name('check-email');
    
    Route::middleware('tamu.auth')->group(function () {
        Route::get('/dashboard', [TamuController::class, 'dashboard'])->name('dashboard');
        Route::get('/profil', [TamuController::class, 'profil'])->name('profil');
        Route::get('/riwayat', [TamuController::class, 'riwayat'])->name('riwayat');
        Route::get('/kunjungan-baru', [TamuController::class, 'kunjunganBaru'])->name('kunjungan.baru');
        Route::post('/kunjungan-simpan', [TamuController::class, 'simpanKunjungan'])->name('kunjungan.simpan');
        // Rute logout untuk portal publik (nama: tamu.logout.tamu)
    // --- TAMBAHAN: Rute Konsultasi Online Tamu ---
       // Rute untuk menampilkan halaman daftar konsultasi
    Route::get('/konsultasi-online', [TamuController::class, 'konsultasiOnline'])->name('konsultasi_online.index');
    
    // Rute untuk memproses pengajuan janji baru dari modal
    Route::post('/konsultasi-simpan', [TamuController::class, 'simpanKonsultasi'])->name('konsultasi.simpan');

Route::put('/konsultasi/{id}/update', [TamuController::class, 'updateKonsultasi'])->name('konsultasi_online.update');
Route::delete('/konsultasi/{id}/hapus', [TamuController::class, 'hapusKonsultasi'])->name('konsultasi_online.hapus');

Route::get('/tamu/chat-fetch', [LiveChatController::class, 'fetchTamu'])->name('chat.fetch');
    Route::post('/tamu/chat-send', [LiveChatController::class, 'sendTamu'])->name('chat.send');
    Route::post('/sowan-ai/chat', [ChatbotController::class, 'sendMessage'])->name('ai.chat');
    // --- TAMBAHAN: Rute Rating & Saran Tamu ---
        Route::get('/rating', [TamuController::class, 'ratingIndex'])->name('rating.index');
        Route::post('/rating-simpan', [TamuController::class, 'simpanRating'])->name('rating.simpan');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout.tamu');
    });
});

// --- 3. JALUR TAMU ONSITE ---
// --- 3. JALUR TAMU ONSITE ---
Route::prefix('tamu-onsite')->name('tamu.onsite.')->group(function () {
    Route::get('/', [AuthController::class, 'showTamuOnsite'])->name('view');
    Route::post('/check-email', [AuthController::class, 'checkEmailOnsite'])->name('check-email');
});

// --- TAMBAHAN: RUTE VALIDASI TAMU LAMA ONSITE ---
Route::prefix('tamu')->name('tamu.')->middleware('tamu.auth')->group(function () {
    // Route untuk menampilkan halaman validasi pilihan data tamu lama
    Route::get('/validasi-lama', [TamuController::class, 'validasiLamaIndex'])->name('validasi_lama');
    // Route untuk memproses pilihan tombol (gunakan data lama atau ubah baru)
    Route::post('/proses-validasi', [TamuController::class, 'prosesValidasiLama'])->name('proses_validasi');
});

// --- 4. PROSES KUNJUNGAN ---
// Grup A: Hanya bisa diakses saat tamu masih login (Form & Store)
Route::prefix('tamu')->name('tamu.')->middleware('tamu.auth')->group(function () {
    Route::get('/form-baru', [TamuController::class, 'showFormBaru'])->name('form_tamu_baru');
    Route::get('/form-lama', [TamuController::class, 'showFormLama'])->name('form_tamu_lama');
    Route::get('/hadir', [TamuController::class, 'index'])->name('index'); 
    Route::post('/simpan', [TamuController::class, 'store'])->name('store');
    Route::get('/panduan/{id}', [TamuController::class, 'downloadPanduan'])->name('panduan.download');
   
});

// Rute Unduh PDF diletakkan di sini agar bebas diakses tanpa harus login/auth
    Route::get('/unduh-pdf', [TamuController::class, 'unduhPdfAntrean'])->name('unduh.pdf');
// Grup B: Terbuka (Tidak pakai middleware tamu.auth) 
// Agar bisa diakses tepat setelah tamu logout di TamuController@store
Route::prefix('tamu')->name('tamu.')->group(function () {
    // Rute Unduh PDF diletakkan di sini agar bebas diakses tanpa harus login/auth
    Route::get('/unduh-pdf', [TamuController::class, 'unduhPdfAntrean'])->name('unduh.pdf');
    Route::get('/sukses/baru/{nama_tamu}', function ($nama_tamu) {
        return view('tamu.success_tamu_baru', ['nama_tamu' => urldecode($nama_tamu)]);
    })->name('success_baru')->where('nama_tamu', '.*');

    Route::get('/sukses/lama/{nama_tamu}', function ($nama_tamu) {
        return view('tamu.success_tamu_lama', ['nama_tamu' => urldecode($nama_tamu)]);
    })->name('success_lama')->where('nama_tamu', '.*');
});

// --- 5. JALUR AUTENTIKASI (Internal) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.proses');
}); // Penutup kurung ini sebelumnya hilang

// --- 6. AREA TERPROTEKSI (WAJIB LOGIN INTERNAL) ---
Route::middleware('auth')->group(function () {
    // Logout internal
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // --- RUTE GLOBAL UNTUK SEMUA ROLE (Admin, Petugas, Pimpinan) ---
    // Pindahkan ke sini agar semua aktor yang login bisa akses
    Route::post('/user/toggle-status', [App\Http\Controllers\UserController::class, 'toggleStatus'])->name('user.toggle-status');
    /**
     * Dashboard Redirector (Otomatis sesuai Role)
     * Sudah di dalam grup middleware('auth'), jadi $user tidak akan null
     */
    Route::get('/dashboard', function () {
        $user = Auth::user(); 
        
        return match ($user->role) {
            'administrator' => redirect()->route('admin.dashboard'),
            'petugas'       => redirect()->route('petugas.dashboard'),
            'pimpinan'      => redirect()->route('pimpinan.dashboard'),
            default         => abort(403, 'Role tidak terdefinisi.'),
        };
    })->name('dashboard');



// --- GRUP AKSES: PETUGAS 📋 (PERBAIKAN) ---
    Route::middleware('role:petugas')->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/dashboard', [PetugasController::class, 'dashboard'])->name('dashboard');

        Route::resource('manajemen_tamu', PetugasController::class)
             ->parameters(['manajemen_tamu' => 'tamu']);
        
        Route::patch('manajemen_tamu/{tamu}/status', [PetugasController::class, 'updateStatus'])
             ->name('manajemen_tamu.updateStatus');

        // Grup laporan diperbaiki posisinya
        Route::prefix('laporan')->name('laporan.')->group(function() {
            Route::get('/', [PetugasController::class, 'laporanIndex'])->name('index');
            Route::get('/export', [PetugasController::class, 'laporan_export'])->name('export'); 
        });

        // Grup rating sekarang sudah benar berada di dalam grup petugas
        Route::prefix('rating')->name('rating.')->group(function() {
            Route::get('/', [PetugasController::class, 'ratingIndex'])->name('index');
            Route::post('/{id}/tanggapan', [PetugasController::class, 'ratingTanggapan'])->name('tanggapan');
        });

Route::post('/sowan-ai/chat', [ChatbotController::class, 'sendMessage'])->name('ai.chat');

        // --- TAMBAHAN: FITUR KONSULTASI ONLINE PETUGAS ---
Route::prefix('konsultasi')->name('konsultasi.')->group(function() {
    Route::get('/', [PetugasController::class, 'konsultasiIndex'])->name('index');
    Route::post('/toggle-status', [PetugasController::class, 'toggleStatusLayanan'])->name('toggle');
    Route::post('/{id}/konfirmasi', [PetugasController::class, 'konfirmasiKonsultasi'])->name('konfirmasi');

    // Pastikan letaknya di dalam group 'petugas/konsultasi'
Route::post('/{id}/proses', [PetugasController::class, 'prosesKeputusan'])->name('proses');
});

// --- TAMBAHAN RUTE AI SCANNER ---
        Route::prefix('ai-scanner')->name('ai_scanner.')->group(function () {
            Route::get('/kamera', [\App\Http\Controllers\AiController::class, 'kamera'])->name('kamera');
            Route::post('/process', [\App\Http\Controllers\AiController::class, 'processAi'])->name('process');
        });

// Tambahkan ini di dalam group route petugas Anda
Route::post('/petugas/konsultasi/{id}/selesaikan', [PetugasController::class, 'selesaikanKonsultasi'])->name('konsultasi.selesaikan');
    });

   
    // --- GRUP AKSES: ADMINISTRATOR 🛡️ ---
    Route::middleware('role:administrator')->prefix('admin')->name('admin.')->group(function () {
        
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::resource('users', UserController::class)
             ->parameters(['users' => 'user']);

        // --- MASTER DATA ---
        Route::prefix('master')->name('master.')->group(function() {
            
            Route::get('/', [AdminController::class, 'master_index'])->name('index');

            Route::prefix('layanan')->name('layanan.')->group(function() {
                Route::get('/', [AdminController::class, 'layanan_index'])->name('index');
                Route::get('/create', [AdminController::class, 'layanan_create'])->name('create');
                Route::post('/', [AdminController::class, 'layanan_store'])->name('store');
                Route::get('/{id}', [AdminController::class, 'layanan_show'])->name('show');
                Route::get('/{id}/edit', [AdminController::class, 'layanan_edit'])->name('edit');
                Route::put('/{id}', [AdminController::class, 'layanan_update'])->name('update');
                Route::delete('/{id}', [AdminController::class, 'layanan_destroy'])->name('destroy');

                // Mengelola Dokumen Panduan Layanan (Sesuai Use Case)
                Route::get('/{id}/panduan', [AdminController::class, 'layanan_panduan'])->name('panduan');
                Route::post('/{id}/panduan', [AdminController::class, 'layanan_panduan_store'])->name('panduan.store');
                Route::delete('/panduan/{id_dokumen}', [AdminController::class, 'layanan_panduan_destroy'])->name('panduan.destroy');
            });

            Route::prefix('tujuan')->name('tujuan.')->group(function() {
                Route::get('/', [AdminController::class, 'tujuan_index'])->name('index');
                Route::get('/create', [AdminController::class, 'tujuan_create'])->name('create');
                Route::post('/store', [AdminController::class, 'tujuan_store'])->name('tujuan_store'); 
                Route::get('/{id}', [AdminController::class, 'tujuan_show'])->name('show');
                Route::get('/{id}/edit', [AdminController::class, 'tujuan_edit'])->name('edit');
                Route::put('/{id}', [AdminController::class, 'tujuan_update'])->name('update');
                Route::delete('/{id}', [AdminController::class, 'tujuan_destroy'])->name('destroy');
            });
        });

        // --- MENU LAPORAN KUNJUNGAN ---
        Route::prefix('laporan')->name('laporan.')->group(function() {
            Route::get('/', [AdminController::class, 'laporan_index'])->name('index');
            Route::get('/export', [AdminController::class, 'laporan_export'])->name('export');
            Route::get('/{id}', [AdminController::class, 'laporan_show'])->name('show');
            Route::get('/{id}/edit', [AdminController::class, 'laporan_edit'])->name('edit');
            Route::put('/{id}', [AdminController::class, 'laporan_update'])->name('update');
            Route::delete('/{id}', [AdminController::class, 'laporan_destroy'])->name('destroy');
        });

        // --- MANAJEMEN RATING LAYANAN ---
        Route::prefix('rating')->name('rating.')->group(function() {
            Route::get('/', [AdminController::class, 'rating_index'])->name('index');
            Route::get('/{id}', [AdminController::class, 'rating_show'])->name('show');
            Route::put('/{id}', [AdminController::class, 'rating_tanggapan'])->name('update');
            Route::post('/{id}/tanggapan', [AdminController::class, 'rating_tanggapan'])->name('tanggapan');
            Route::delete('/{id}', [AdminController::class, 'rating_destroy'])->name('destroy');
        });

        // --- MANAJEMEN KONSULTASI ONLINE ---
        Route::prefix('konsultasi')->name('konsultasi.')->group(function() {
            Route::get('/', [AdminController::class, 'konsultasi_index'])->name('index');
            Route::delete('/{id}', [AdminController::class, 'konsultasi_destroy'])->name('destroy');

            // TAMBAHKAN DUA RUTE INI
    Route::post('/{id}/proses', [AdminController::class, 'prosesKonsultasi'])->name('proses');
    Route::post('/{id}/selesaikan', [AdminController::class, 'selesaikanKonsultasi'])->name('selesaikan');
        });

// --- MANAJEMEN LIVE CHAT (ADMIN / CS) ---
        Route::get('/chat', [LiveChatController::class, 'indexAdmin'])->name('chat.index');
        Route::get('/chat-fetch', [LiveChatController::class, 'fetchAdmin'])->name('chat.fetch');
        Route::post('/chat-send', [LiveChatController::class, 'sendAdmin'])->name('chat.send');

        // Monitoring Aktivitas Global (Audit Log)
        Route::prefix('aktivitas')->name('aktivitas.')->group(function() {
            Route::get('/', [AdminController::class, 'aktivitas_global'])->name('index');
        });

        // ... (kode aktivitas sebelumnya)
        Route::prefix('aktivitas')->name('aktivitas.')->group(function() {
            Route::get('/', [AdminController::class, 'aktivitas_global'])->name('index');
        });

        // --- TAMBAHAN: MONITORING SISTEM (Laravel Pulse) ---
        Route::get('/pulse', function () {
            return view('pulse::dashboard');
        })->name('pulse');

    });


  // --- GRUP AKSES: PIMPINAN 👔 ---
    Route::middleware('role:pimpinan')->prefix('pimpinan')->name('pimpinan.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [PimpinanController::class, 'dashboard'])->name('dashboard');
        
        // Laporan
        Route::get('/laporan', [PimpinanController::class, 'laporanIndex'])->name('laporan.index');
        Route::get('/laporan/export', [PimpinanController::class, 'laporanExport'])->name('laporan.export');
        
        // Monitoring Rating & Detail (AJAX)
        Route::get('/rating', [PimpinanController::class, 'ratingIndex'])->name('rating.index');
        Route::get('/rating/{id}', [PimpinanController::class, 'ratingShow'])->name('rating.show');
        
        // Log Aktivitas
        Route::get('/aktivitas', [PimpinanController::class, 'aktivitasIndex'])->name('aktivitas.index');

        // Fitur Konsultasi Online (Disesuaikan agar tidak duplikat prefiks)
    Route::prefix('konsultasi')->name('konsultasi.')->group(function() {
        Route::get('/', [PimpinanController::class, 'konsultasiIndex'])->name('index');
        Route::post('/toggle-status', [PimpinanController::class, 'toggleStatusLayanan'])->name('toggle');
        Route::post('/{id}/konfirmasi', [PimpinanController::class, 'konfirmasiKonsultasi'])->name('konfirmasi');
        
        // Rute Selesaikan (Tanpa prefiks pimpinan lagi, karena sudah di dalam prefix 'konsultasi')
        Route::post('/{id}/selesaikan', [PimpinanController::class, 'selesaikanKonsultasi'])->name('selesaikan');
        
        // Rute Proses (Sesuai dengan yang dipanggil di JavaScript view)
        Route::post('/{id}/proses', [PimpinanController::class, 'prosesKonsultasi'])->name('proses');
    });
    });

    // --- FITUR STATISTIK & GRAFIK (SHARED) ---
    Route::middleware(['role:pimpinan,administrator'])->group(function () {
        Route::get('/statistik', [TamuController::class, 'stats'])->name('statistik.index');
    });

   
  // --- RUTE UNIVERSAL CHAT & AI UNTUK SEMUA ROLE INTERNAL ---
    foreach (['administrator', 'petugas', 'pimpinan'] as $role) {
        $prefix = ($role === 'administrator') ? 'admin' : $role;
        
        Route::middleware(['auth'])->prefix($prefix)->name("$role.")->group(function () use ($role) {
            // Route AI Chat Assistant (Hanya daftarkan jika belum ada di grup spesifik)
            if ($role !== 'petugas') {
                Route::post('/ai-chat', [\App\Http\Controllers\ChatbotController::class, 'sendMessage'])->name('ai.chat');
            }
            
            // Route Live Customer Service (CS) untuk Petugas & Pimpinan
            // (Administrator menggunakan endpoint khususnya sendiri di atas)
            if ($role !== 'administrator') {
                Route::get('/chat-fetch', [\App\Http\Controllers\LiveChatController::class, 'fetchMessages'])->name('chat.fetch');
                Route::post('/chat-send', [\App\Http\Controllers\LiveChatController::class, 'sendMessage'])->name('chat.send');
            }
        });
    }

   
});