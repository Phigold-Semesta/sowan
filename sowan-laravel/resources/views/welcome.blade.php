<!DOCTYPE html>
<html lang="id" x-data="{ 
    darkMode: localStorage.getItem('theme') === 'dark',
    mobileMenuOpen: false,
    scrolledFromTop: false,
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
    }
}" @scroll.window="scrolledFromTop = (window.pageYOffset > 300)" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LPSE Kabupaten Karawang</title>
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Alpine Plugins (Intersect untuk Animasi Statistik) & Alpine Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        'sowan-emerald': '#008f5d',
                        'sowan-gold': '#b45309',
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(16, 185, 129, 0.5);
            border-radius: 10px;
        }

        /* Hover Effect untuk Kartu */
        .hover-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .layanan-card:hover {
            background-color: #008f5d; 
            color: white !important;
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 143, 93, 0.3), 0 8px 10px -6px rgba(0, 143, 93, 0.1);
        }

        .layanan-card:hover .icon-box {
            background-color: transparent;
            border-color: white;
        }
        .layanan-card:hover i { color: white !important; }
        .layanan-card:hover h3 { color: white !important; }
        .layanan-card:hover p { color: rgba(255, 255, 255, 0.9) !important; }

        .dark .layanan-card:hover {
            background-color: #047857; 
            box-shadow: 0 20px 25px -5px rgba(4, 120, 87, 0.4), 0 8px 10px -6px rgba(4, 120, 87, 0.2);
        }
        .dark .layanan-card:hover .icon-box { border-color: rgba(255, 255, 255, 0.5); }

        /* Motif Batik Emerald Green Mewah Khusus Hero Section (Sesuai Referensi) */
        .batik-hero-bg {
            background: url("{{ asset('img/batik_emerald_green.png') }}") no-repeat center center;
            background-size: cover;
            position: relative;
        }
        .luxury-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, rgba(6, 78, 59, 0.25) 0%, rgba(2, 44, 34, 0.90) 100%);
            backdrop-filter: contrast(1.1) brightness(0.9);
            z-index: 1;
        }
        .light-glow {
            position: absolute; width: min(700px, 90vw); height: min(700px, 90vw);
            background: rgba(52, 211, 153, 0.12);
            filter: blur(130px); border-radius: 50%; z-index: 2;
            animation: move 25s infinite alternate ease-in-out;
        }
        @keyframes move {
            from { transform: translate(-20%, -20%); }
            to { transform: translate(25%, 25%); }
        }
        .reveal-text {
            display: inline-block;
            animation: tracking-in-expand 0.8s cubic-bezier(0.215, 0.610, 0.355, 1.000) both;
        }
        @keyframes tracking-in-expand {
            0% { letter-spacing: -0.2em; opacity: 0; }
            40% { opacity: 0.6; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body class="antialiased text-slate-800 bg-[#f0f9f4] dark:bg-emerald-950 dark:text-emerald-50 min-h-screen flex flex-col relative overflow-x-hidden 2xl:text-lg">

    <!-- NAVBAR ATAS -->
    <nav class="w-full px-6 sm:px-12 2xl:px-24 py-4 2xl:py-6 flex justify-between items-center relative z-50 bg-white/80 dark:bg-emerald-950/80 backdrop-blur-xl border-b border-white/20 dark:border-emerald-800/50 sticky top-0 shadow-sm">
        
        <!-- Logo & Mobile Toggle -->
        <div class="flex items-center gap-4">
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-emerald-900 border border-slate-200 dark:border-emerald-700 text-[#008f5d] dark:text-emerald-400 shadow-sm">
                <i class="fas fa-bars" x-show="!mobileMenuOpen"></i>
                <i class="fas fa-times" x-show="mobileMenuOpen" x-cloak></i>
            </button>

            <!-- Fokus Logo pada LPSE -->
            <a href="/" class="flex items-center gap-3">
                <div class="bg-white p-2.5 2xl:p-3 rounded-xl shadow-md border border-slate-100 dark:border-none flex items-center justify-center">
                    <i class="fas fa-building text-[#008f5d] text-xl 2xl:text-2xl"></i>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="text-xl 2xl:text-2xl font-black tracking-tighter uppercase text-slate-800 dark:text-white">
                        LPSE
                    </span>
                    <span class="text-[10px] 2xl:text-xs font-bold text-slate-500 dark:text-emerald-300 uppercase tracking-widest mt-0.5">Kab. Karawang</span>
                </div>
            </a>
        </div>

        <!-- Wrapper untuk Menu Utama dan Kontrol Kanan menempel di kanan -->
        <div class="flex items-center gap-6 xl:gap-8 2xl:gap-12">
            
            <!-- Menu Utama (Desktop) -->
            <div class="hidden lg:flex items-center gap-6 xl:gap-8 2xl:gap-12 font-bold text-sm xl:text-base 2xl:text-lg text-slate-600 dark:text-emerald-100">
                <a href="/" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors border-b-2 border-transparent hover:border-[#008f5d] dark:hover:border-emerald-400 py-2">Beranda</a>
                <a href="/profil" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors border-b-2 border-transparent hover:border-[#008f5d] dark:hover:border-emerald-400 py-2">Profil</a>
                <a href="/layanan" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors border-b-2 border-transparent hover:border-[#008f5d] dark:hover:border-emerald-400 py-2">Layanan</a>
                <a href="/panduan" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors border-b-2 border-transparent hover:border-[#008f5d] dark:hover:border-emerald-400 py-2">Panduan</a>
            </div>

            <!-- Kontrol Kanan (Theme & Login) -->
            <div class="flex items-center gap-4 2xl:gap-6">
                <button @click="toggleTheme()" class="w-10 h-10 2xl:w-12 2xl:h-12 flex items-center justify-center rounded-xl bg-white dark:bg-emerald-800 text-slate-500 dark:text-yellow-400 border border-slate-200 dark:border-emerald-700 shadow-sm transition-all hover:scale-110">
                    <i x-show="darkMode" class="fa-solid fa-sun text-lg 2xl:text-xl" x-cloak></i>
                    <i x-show="!darkMode" class="fa-solid fa-moon text-lg 2xl:text-xl" x-cloak></i>
                </button>

                <a href="{{ route('login') }}" class="hidden sm:flex items-center gap-2 px-5 py-2.5 2xl:px-6 2xl:py-3 bg-white dark:bg-emerald-800 border border-slate-200 dark:border-emerald-700 text-slate-700 dark:text-emerald-50 rounded-xl font-bold text-sm 2xl:text-base shadow-sm hover:bg-slate-50 dark:hover:bg-emerald-700 transition-colors">
                    <i class="fas fa-lock text-[#008f5d] dark:text-emerald-400 text-xs 2xl:text-sm"></i>
                    Portal Petugas
                </a>
                
                <a href="{{ route('login') }}" class="flex sm:hidden w-10 h-10 items-center justify-center bg-white dark:bg-emerald-800 border border-slate-200 dark:border-emerald-700 text-[#008f5d] dark:text-emerald-400 rounded-xl shadow-sm hover:bg-slate-50 dark:hover:bg-emerald-700 transition-colors">
                    <i class="fas fa-lock text-sm"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- MENU MOBILE -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="lg:hidden fixed top-[76px] left-0 w-full bg-white/95 dark:bg-emerald-950/95 backdrop-blur-3xl border-b border-slate-200 dark:border-emerald-800/50 z-40 shadow-2xl" x-cloak>
        
        <div class="px-6 py-4 flex flex-col gap-2 font-bold text-base text-slate-700 dark:text-emerald-50 max-h-[80vh] overflow-y-auto">
            <a href="/" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-emerald-900 transition-colors">
                <i class="fas fa-home w-5 text-center text-[#008f5d] dark:text-emerald-400"></i> Beranda
            </a>
            <a href="/profil" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-emerald-900 transition-colors">
                <i class="fas fa-building w-5 text-center text-[#008f5d] dark:text-emerald-400"></i> Profil Instansi
            </a>
            <a href="/layanan" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-emerald-900 transition-colors">
                <i class="fas fa-concierge-bell w-5 text-center text-[#008f5d] dark:text-emerald-400"></i> Layanan Kami
            </a>
            <a href="#statistik" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-emerald-900 transition-colors">
                <i class="fas fa-chart-bar w-5 text-center text-[#008f5d] dark:text-emerald-400"></i> Statistik
            </a>
            <a href="#berita" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-emerald-900 transition-colors">
                <i class="fas fa-newspaper w-5 text-center text-[#008f5d] dark:text-emerald-400"></i> Berita & Info
            </a>
            <a href="/panduan" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-emerald-900 transition-colors">
                <i class="fas fa-book w-5 text-center text-[#008f5d] dark:text-emerald-400"></i> Buku Panduan
            </a>
        </div>
    </div>

    <!-- 1. KONTEN UTAMA (HERO SECTION DENGAN MOTIF BATIK EMERALD GREEN MEWAH) -->
    <main class="flex-grow flex items-center justify-center px-4 relative z-10 py-24 sm:py-32 2xl:py-40 batik-hero-bg overflow-hidden shadow-2xl">
        <div class="luxury-overlay"></div>
        <div class="light-glow top-0 left-0"></div>
        <div class="light-glow bottom-0 right-0" style="animation-delay: -7s;"></div>

        <div class="max-w-4xl lg:max-w-5xl 2xl:max-w-6xl text-center space-y-8 2xl:space-y-10 relative z-20">
            
            <div class="inline-flex items-center gap-2 px-4 py-2 2xl:px-6 2xl:py-3 rounded-full bg-white/10 dark:bg-emerald-900/60 border border-white/20 dark:border-emerald-700 text-xs 2xl:text-sm font-bold text-emerald-300 tracking-widest uppercase mb-4 shadow-sm backdrop-blur-md">
                <span class="w-2 h-2 2xl:w-3 2xl:h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                Pemerintah Kabupaten Karawang
            </div>

            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl 2xl:text-[5.5rem] font-black text-white tracking-tight leading-[1.15] drop-shadow-lg reveal-text">
                Layanan Pengadaan <br>
                <span class="text-emerald-400">
                    Secara Elektronik (LPSE)
                </span>
            </h1>

            <p class="text-base md:text-lg 2xl:text-xl text-emerald-100/90 max-w-2xl 2xl:max-w-3xl mx-auto font-medium leading-relaxed bg-white/5 dark:bg-emerald-950/40 p-6 rounded-3xl backdrop-blur-md border border-white/10 shadow-xl">
                Mewujudkan sistem pengadaan barang dan jasa yang transparan, akuntabel, dan efisien di lingkungan Pemerintah Kabupaten Karawang melalui pemanfaatan teknologi informasi.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 2xl:gap-6 pt-6 2xl:pt-10">
                <a href="{{ route('tamu.onsite.view') }}" class="w-full sm:w-auto px-8 py-4 2xl:px-10 2xl:py-5 bg-emerald-400 hover:bg-emerald-300 text-emerald-950 rounded-2xl font-black shadow-[0_15px_40px_rgba(52,211,153,0.3)] transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-3 text-lg 2xl:text-xl tracking-[0.1em] uppercase">
                    <i class="fas fa-qrcode text-xl 2xl:text-2xl"></i>
                    Buku Tamu Onsite
                </a>
                <a href="{{ route('tamu.login.view') }}" class="w-full sm:w-auto px-8 py-4 2xl:px-10 2xl:py-5 bg-white/10 hover:bg-white/20 border-2 border-emerald-400/50 text-white rounded-2xl font-black shadow-xl backdrop-blur-md transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-3 text-lg 2xl:text-xl tracking-[0.1em] uppercase">
                    <i class="fas fa-calendar-alt text-xl 2xl:text-2xl"></i>
                    Konsultasi Online
                </a>
            </div>
        </div>
    </main>

    <!-- 2. BAGIAN LAYANAN LPSE -->
    <div id="layanan" class="relative z-10 max-w-7xl 2xl:max-w-screen-2xl mx-auto px-6 2xl:px-24 pt-20 pb-20 2xl:pb-32 w-full">
        <div class="text-center mb-12 2xl:mb-16">
            <h2 class="text-3xl 2xl:text-4xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Layanan & Sistem Pengadaan</h2>
            <div class="w-24 h-1.5 2xl:w-32 2xl:h-2 bg-[#008f5d] mx-auto mt-4 rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 2xl:gap-12">
            <!-- Kartu Layanan (Efek Hover) -->
            <div class="layanan-card hover-card bg-white dark:bg-emerald-900/40 border border-slate-200 dark:border-emerald-800/50 p-8 2xl:p-10 rounded-3xl shadow-md text-center flex flex-col items-center cursor-pointer">
                <div class="icon-box w-16 h-16 2xl:w-20 2xl:h-20 rounded-full bg-emerald-50 dark:bg-emerald-800 flex items-center justify-center mb-6 border border-emerald-100 dark:border-emerald-700 transition-colors">
                    <i class="fas fa-laptop-code text-3xl 2xl:text-4xl text-[#008f5d] dark:text-emerald-400 transition-colors"></i>
                </div>
                <h3 class="text-slate-800 dark:text-white font-black text-xl 2xl:text-2xl mb-4 transition-colors">Aplikasi SPSE</h3>
                <p class="text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/60 leading-relaxed font-medium transition-colors">
                    Sistem Pengadaan Secara Elektronik. Sarana utama untuk melaksanakan proses e-Procurement yang bersih dan transparan.
                </p>
            </div>

            <div class="layanan-card hover-card bg-white dark:bg-emerald-900/40 border border-slate-200 dark:border-emerald-800/50 p-8 2xl:p-10 rounded-3xl shadow-md text-center flex flex-col items-center cursor-pointer">
                <div class="icon-box w-16 h-16 2xl:w-20 2xl:h-20 rounded-full bg-emerald-50 dark:bg-emerald-800 flex items-center justify-center mb-6 border border-emerald-100 dark:border-emerald-700 transition-colors">
                    <i class="fas fa-chart-pie text-3xl 2xl:text-4xl text-[#008f5d] dark:text-emerald-400 transition-colors"></i>
                </div>
                <h3 class="text-slate-800 dark:text-white font-black text-xl 2xl:text-2xl mb-4 transition-colors">Aplikasi SiRUP</h3>
                <p class="text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/60 leading-relaxed font-medium transition-colors">
                    Sistem Informasi Rencana Umum Pengadaan. Mengumumkan rencana pengadaan barang dan jasa kepada publik.
                </p>
            </div>

            <div class="layanan-card hover-card bg-white dark:bg-emerald-900/40 border border-slate-200 dark:border-emerald-800/50 p-8 2xl:p-10 rounded-3xl shadow-md text-center flex flex-col items-center cursor-pointer">
                <div class="icon-box w-16 h-16 2xl:w-20 2xl:h-20 rounded-full bg-emerald-50 dark:bg-emerald-800 flex items-center justify-center mb-6 border border-emerald-100 dark:border-emerald-700 transition-colors">
                    <i class="fas fa-id-card text-3xl 2xl:text-4xl text-[#008f5d] dark:text-emerald-400 transition-colors"></i>
                </div>
                <h3 class="text-slate-800 dark:text-white font-black text-xl 2xl:text-2xl mb-4 transition-colors">Aplikasi SIKaP</h3>
                <p class="text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/60 leading-relaxed font-medium transition-colors">
                    Sistem Informasi Kinerja Penyedia. Mengelola data kualifikasi penyedia barang/jasa dan mempercepat evaluasi.
                </p>
            </div>
        </div>
    </div>

    <!-- 3. BAGIAN STATISTIK PENGADAAN (Animasi Counter) -->
    <div id="statistik" class="relative z-10 py-20 2xl:py-28 bg-[#008f5d] dark:bg-emerald-900 text-white border-y border-[#007a4f] dark:border-emerald-800 shadow-inner w-full">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="max-w-7xl 2xl:max-w-screen-2xl mx-auto px-6 2xl:px-24 relative z-10">
            <div class="text-center mb-12 2xl:mb-16">
                <h2 class="text-3xl 2xl:text-4xl font-black uppercase tracking-tight">Statistik LPSE Tahun Ini</h2>
                <div class="w-24 h-1.5 2xl:w-32 2xl:h-2 bg-emerald-300 mx-auto mt-4 rounded-full"></div>
            </div>

            <!-- Kontainer Counter Menggunakan Alpine.js x-intersect -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 2xl:gap-12"
                 x-data="{ shown: false }" 
                 x-intersect.once="shown = true">
                
                <!-- Counter 1 -->
                <div class="text-center space-y-3">
                    <i class="fas fa-file-contract text-4xl 2xl:text-5xl text-emerald-300 mb-2"></i>
                    <h3 class="text-4xl 2xl:text-6xl font-black tracking-tighter" 
                        x-data="{ count: 0, target: 450 }" 
                        x-init="$watch('shown', val => { if(val) { let i = 0; let interval = setInterval(() => { if(i < target) { i += Math.ceil(target/50); count = i > target ? target : i; } else { clearInterval(interval); } }, 30); } })" 
                        x-text="count + '+'">0+</h3>
                    <p class="text-sm 2xl:text-base font-bold text-emerald-100 uppercase tracking-widest">Paket Tender</p>
                </div>
                
                <!-- Counter 2 -->
                <div class="text-center space-y-3">
                    <i class="fas fa-handshake text-4xl 2xl:text-5xl text-emerald-300 mb-2"></i>
                    <h3 class="text-4xl 2xl:text-6xl font-black tracking-tighter" 
                        x-data="{ count: 0, target: 1250 }" 
                        x-init="$watch('shown', val => { if(val) { let i = 0; let interval = setInterval(() => { if(i < target) { i += Math.ceil(target/50); count = i > target ? target : i; } else { clearInterval(interval); } }, 30); } })" 
                        x-text="count + '+'">0+</h3>
                    <p class="text-sm 2xl:text-base font-bold text-emerald-100 uppercase tracking-widest">Paket Non-Tender</p>
                </div>
                
                <!-- Counter 3 -->
                <div class="text-center space-y-3">
                    <i class="fas fa-users-cog text-4xl 2xl:text-5xl text-emerald-300 mb-2"></i>
                    <h3 class="text-4xl 2xl:text-6xl font-black tracking-tighter" 
                        x-data="{ count: 0, target: 820 }" 
                        x-init="$watch('shown', val => { if(val) { let i = 0; let interval = setInterval(() => { if(i < target) { i += Math.ceil(target/50); count = i > target ? target : i; } else { clearInterval(interval); } }, 30); } })" 
                        x-text="count">0</h3>
                    <p class="text-sm 2xl:text-base font-bold text-emerald-100 uppercase tracking-widest">Penyedia Aktif</p>
                </div>

                <!-- Counter 4 -->
                <div class="text-center space-y-3">
                    <i class="fas fa-check-circle text-4xl 2xl:text-5xl text-emerald-300 mb-2"></i>
                    <h3 class="text-4xl 2xl:text-6xl font-black tracking-tighter" 
                        x-data="{ count: 0, target: 1600 }" 
                        x-init="$watch('shown', val => { if(val) { let i = 0; let interval = setInterval(() => { if(i < target) { i += Math.ceil(target/50); count = i > target ? target : i; } else { clearInterval(interval); } }, 30); } })" 
                        x-text="count + '+'">0+</h3>
                    <p class="text-sm 2xl:text-base font-bold text-emerald-100 uppercase tracking-widest">Paket Selesai</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. BAGIAN BERITA & PENGUMUMAN -->
    <div id="berita" class="relative z-10 max-w-7xl 2xl:max-w-screen-2xl mx-auto px-6 2xl:px-24 py-20 2xl:py-28 w-full">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 2xl:mb-16 gap-6">
            <div>
                <h2 class="text-3xl 2xl:text-4xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Berita & Info Terbaru</h2>
                <div class="w-24 h-1.5 2xl:w-32 2xl:h-2 bg-[#008f5d] mt-4 rounded-full"></div>
            </div>
            <a href="#" class="inline-flex items-center gap-2 text-[#008f5d] dark:text-emerald-400 font-bold hover:gap-3 transition-all">
                Lihat Semua Berita <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 2xl:gap-12">
            <!-- Berita 1 -->
            <div class="group bg-white dark:bg-emerald-900/40 rounded-3xl border border-slate-200 dark:border-emerald-800/50 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col cursor-pointer hover:-translate-y-2">
                <div class="h-48 2xl:h-64 bg-slate-200 dark:bg-emerald-800 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400 to-[#008f5d] opacity-80 group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-white/50">
                        <i class="fas fa-image text-4xl 2xl:text-5xl"></i>
                    </div>
                    <span class="absolute top-4 left-4 bg-white/90 dark:bg-emerald-950/90 text-[#008f5d] dark:text-emerald-400 text-xs 2xl:text-sm font-black px-3 py-1.5 rounded-xl backdrop-blur-sm shadow-sm uppercase">Pengumuman</span>
                </div>
                <div class="p-6 2xl:p-8 flex-1 flex flex-col">
                    <p class="text-xs 2xl:text-sm text-slate-500 dark:text-emerald-300/80 font-bold mb-2 flex items-center gap-2">
                        <i class="far fa-calendar-alt"></i> 17 Oktober 2026
                    </p>
                    <h3 class="text-lg 2xl:text-xl font-black text-slate-800 dark:text-white mb-3 group-hover:text-[#008f5d] dark:group-hover:text-emerald-400 transition-colors leading-tight">Pemeliharaan Server Aplikasi SPSE Kabupaten Karawang</h3>
                    <p class="text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/70 font-medium line-clamp-3 mb-4 flex-1">
                        Diberitahukan kepada seluruh pengguna SPSE, akan dilakukan pemeliharaan infrastruktur server pada akhir pekan ini untuk peningkatan keamanan.
                    </p>
                    <span class="text-sm 2xl:text-base font-bold text-[#008f5d] dark:text-emerald-400 group-hover:underline flex items-center gap-1">Baca Selengkapnya <i class="fas fa-chevron-right text-[10px]"></i></span>
                </div>
            </div>

            <!-- Berita 2 -->
            <div class="group bg-white dark:bg-emerald-900/40 rounded-3xl border border-slate-200 dark:border-emerald-800/50 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col cursor-pointer hover:-translate-y-2">
                <div class="h-48 2xl:h-64 bg-slate-200 dark:bg-emerald-800 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-indigo-600 opacity-80 group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-white/50">
                        <i class="fas fa-bullhorn text-4xl 2xl:text-5xl"></i>
                    </div>
                    <span class="absolute top-4 left-4 bg-white/90 dark:bg-emerald-950/90 text-blue-600 dark:text-blue-400 text-xs 2xl:text-sm font-black px-3 py-1.5 rounded-xl backdrop-blur-sm shadow-sm uppercase">Info Tender</span>
                </div>
                <div class="p-6 2xl:p-8 flex-1 flex flex-col">
                    <p class="text-xs 2xl:text-sm text-slate-500 dark:text-emerald-300/80 font-bold mb-2 flex items-center gap-2">
                        <i class="far fa-calendar-alt"></i> 15 Oktober 2026
                    </p>
                    <h3 class="text-lg 2xl:text-xl font-black text-slate-800 dark:text-white mb-3 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">Sosialisasi Penggunaan e-Katalog Lokal bagi UMKM</h3>
                    <p class="text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/70 font-medium line-clamp-3 mb-4 flex-1">
                        Pemerintah Kabupaten Karawang mendorong pelaku UMKM untuk mendaftarkan produknya di e-Katalog Lokal untuk mendukung pengadaan barang.
                    </p>
                    <span class="text-sm 2xl:text-base font-bold text-blue-600 dark:text-blue-400 group-hover:underline flex items-center gap-1">Baca Selengkapnya <i class="fas fa-chevron-right text-[10px]"></i></span>
                </div>
            </div>

            <!-- Berita 3 -->
            <div class="group bg-white dark:bg-emerald-900/40 rounded-3xl border border-slate-200 dark:border-emerald-800/50 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col cursor-pointer hover:-translate-y-2">
                <div class="h-48 2xl:h-64 bg-slate-200 dark:bg-emerald-800 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-400 to-orange-600 opacity-80 group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-white/50">
                        <i class="fas fa-award text-4xl 2xl:text-5xl"></i>
                    </div>
                    <span class="absolute top-4 left-4 bg-white/90 dark:bg-emerald-950/90 text-amber-600 dark:text-amber-400 text-xs 2xl:text-sm font-black px-3 py-1.5 rounded-xl backdrop-blur-sm shadow-sm uppercase">Penghargaan</span>
                </div>
                <div class="p-6 2xl:p-8 flex-1 flex flex-col">
                    <p class="text-xs 2xl:text-sm text-slate-500 dark:text-emerald-300/80 font-bold mb-2 flex items-center gap-2">
                        <i class="far fa-calendar-alt"></i> 10 Oktober 2026
                    </p>
                    <h3 class="text-lg 2xl:text-xl font-black text-slate-800 dark:text-white mb-3 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors leading-tight">LPSE Karawang Meraih Indeks Kematangan UKPBJ Level 3</h3>
                    <p class="text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/70 font-medium line-clamp-3 mb-4 flex-1">
                        Pencapaian luar biasa oleh UKPBJ Kabupaten Karawang yang berhasil mencapai tingkat kematangan proaktif pada standarisasi LKPP Nasional.
                    </p>
                    <span class="text-sm 2xl:text-base font-bold text-amber-600 dark:text-amber-400 group-hover:underline flex items-center gap-1">Baca Selengkapnya <i class="fas fa-chevron-right text-[10px]"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. GALERI DOKUMENTASI -->
    <div id="galeri" class="relative z-10 w-full bg-slate-50 dark:bg-emerald-950/50 border-y border-slate-200 dark:border-emerald-800/50 py-20 2xl:py-28">
        <div class="max-w-7xl 2xl:max-w-screen-2xl mx-auto px-6 2xl:px-24">
            <div class="text-center mb-12 2xl:mb-16">
                <h2 class="text-3xl 2xl:text-4xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Galeri Kegiatan</h2>
                <div class="w-24 h-1.5 2xl:w-32 2xl:h-2 bg-[#008f5d] mx-auto mt-4 rounded-full"></div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 2xl:gap-6">
                <div class="aspect-square rounded-2xl overflow-hidden relative group cursor-pointer border border-slate-200 dark:border-emerald-800">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500 to-[#008f5d] opacity-70 group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="absolute inset-0 flex items-center justify-center"><i class="fas fa-camera text-white/50 text-3xl 2xl:text-5xl"></i></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                        <p class="text-white font-bold text-sm 2xl:text-base">Rakor UKPBJ Jabar</p>
                    </div>
                </div>
                <div class="aspect-square md:col-span-2 rounded-2xl overflow-hidden relative group cursor-pointer border border-slate-200 dark:border-emerald-800">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-indigo-700 opacity-70 group-hover:scale-105 transition-transform duration-500"></div>
                    <div class="absolute inset-0 flex items-center justify-center"><i class="fas fa-users text-white/50 text-4xl 2xl:text-6xl"></i></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                        <p class="text-white font-bold text-sm 2xl:text-base">Bimbingan Teknis SiRUP bagi OPD Kab. Karawang</p>
                    </div>
                </div>
                <div class="aspect-square rounded-2xl overflow-hidden relative group cursor-pointer border border-slate-200 dark:border-emerald-800">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500 to-orange-600 opacity-70 group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="absolute inset-0 flex items-center justify-center"><i class="fas fa-handshake text-white/50 text-3xl 2xl:text-5xl"></i></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                        <p class="text-white font-bold text-sm 2xl:text-base">Penandatanganan Fakta Integritas</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-8 2xl:mt-12">
                <a href="#" class="inline-block px-6 py-3 2xl:px-8 2xl:py-4 rounded-full border-2 border-[#008f5d] text-[#008f5d] dark:border-emerald-400 dark:text-emerald-400 font-bold hover:bg-[#008f5d] hover:text-white dark:hover:bg-emerald-400 dark:hover:text-emerald-950 transition-colors text-sm 2xl:text-base shadow-sm">
                    Lihat Semua Dokumentasi
                </a>
            </div>
        </div>
    </div>

    <!-- 6. MEGA FOOTER ELEGAN -->
    <footer class="relative z-10 bg-white/95 dark:bg-emerald-950/95 border-t border-slate-200 dark:border-emerald-800/50 pt-16 pb-32 2xl:pb-48 mt-auto backdrop-blur-3xl shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
        <div class="max-w-7xl 2xl:max-w-screen-2xl mx-auto px-6 sm:px-12 2xl:px-24">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 2xl:gap-16 mb-12">
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-slate-50 dark:bg-emerald-900 p-2.5 rounded-xl shadow-sm border border-slate-200 dark:border-emerald-700">
                            <i class="fas fa-building text-[#008f5d] dark:text-emerald-400 text-2xl"></i>
                        </div>
                        <div class="flex flex-col leading-none">
                            <span class="text-2xl font-black tracking-tighter uppercase text-slate-800 dark:text-white">LPSE</span>
                            <span class="text-xs font-bold text-slate-500 dark:text-emerald-300 uppercase tracking-widest mt-1">Kab. Karawang</span>
                        </div>
                    </div>
                    <p class="text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/70 font-medium leading-relaxed">
                        Layanan Pengadaan Secara Elektronik Kabupaten Karawang berdedikasi mewujudkan tata kelola pengadaan barang dan jasa yang profesional, mandiri, dan berintegritas tinggi.
                    </p>
                </div>

                <div>
                    <h4 class="text-slate-800 dark:text-white font-black uppercase tracking-widest text-sm 2xl:text-base mb-6 flex items-center gap-2">
                        <i class="fas fa-link text-[#008f5d] dark:text-emerald-400"></i> Tautan Navigasi
                    </h4>
                    <ul class="space-y-4 font-semibold text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/70">
                        <li><a href="/" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors flex items-center gap-2"><i class="fas fa-chevron-right text-[10px]"></i> Beranda</a></li>
                        <li><a href="#layanan" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors flex items-center gap-2"><i class="fas fa-chevron-right text-[10px]"></i> Layanan Pengadaan</a></li>
                        <li><a href="#berita" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors flex items-center gap-2"><i class="fas fa-chevron-right text-[10px]"></i> Berita & Artikel</a></li>
                        <li><a href="#panduan" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors flex items-center gap-2"><i class="fas fa-chevron-right text-[10px]"></i> Buku Panduan</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-[#008f5d] dark:hover:text-emerald-400 transition-colors flex items-center gap-2"><i class="fas fa-lock text-[10px]"></i> Login Admin</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-slate-800 dark:text-white font-black uppercase tracking-widest text-sm 2xl:text-base mb-6 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-[#008f5d] dark:text-emerald-400"></i> Kontak Resepsionis
                    </h4>
                    <ul class="space-y-4 font-medium text-sm 2xl:text-base text-slate-600 dark:text-emerald-100/70">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-building text-slate-400 mt-1"></i>
                            <span>Kompleks Perkantoran Pemda<br>Jl. Jend. A. Yani No.1, Karawang<br>Jawa Barat 41315</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-phone-alt text-slate-400"></i>
                            <span>(0267) 401111 / 401112</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-envelope text-slate-400"></i>
                            <span>lpse@karawangkab.go.id</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-slate-800 dark:text-white font-black uppercase tracking-widest text-sm 2xl:text-base mb-6 flex items-center gap-2">
                        <i class="fas fa-share-nodes text-[#008f5d] dark:text-emerald-400"></i> Media Sosial & Info
                    </h4>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 2xl:w-12 2xl:h-12 rounded-full bg-slate-100 dark:bg-emerald-900 border border-slate-200 dark:border-emerald-700 flex items-center justify-center text-slate-600 dark:text-emerald-100 hover:bg-[#008f5d] hover:text-white dark:hover:bg-emerald-500 transition-all shadow-sm hover:scale-110 hover:-translate-y-1" title="Instagram">
                            <i class="fab fa-instagram text-lg 2xl:text-xl"></i>
                        </a>
                        <a href="#" class="w-10 h-10 2xl:w-12 2xl:h-12 rounded-full bg-slate-100 dark:bg-emerald-900 border border-slate-200 dark:border-emerald-700 flex items-center justify-center text-slate-600 dark:text-emerald-100 hover:bg-[#008f5d] hover:text-white dark:hover:bg-emerald-500 transition-all shadow-sm hover:scale-110 hover:-translate-y-1" title="YouTube">
                            <i class="fab fa-youtube text-lg 2xl:text-xl"></i>
                        </a>
                        <a href="#" class="w-10 h-10 2xl:w-12 2xl:h-12 rounded-full bg-slate-100 dark:bg-emerald-900 border border-slate-200 dark:border-emerald-700 flex items-center justify-center text-slate-600 dark:text-emerald-100 hover:bg-[#008f5d] hover:text-white dark:hover:bg-emerald-500 transition-all shadow-sm hover:scale-110 hover:-translate-y-1" title="Facebook">
                            <i class="fab fa-facebook-f text-lg 2xl:text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-emerald-800/50 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-xs 2xl:text-sm font-bold text-slate-500 dark:text-emerald-300/60 uppercase tracking-widest text-center md:text-left">
                    &copy; {{ date('Y') }} LPSE Kabupaten Karawang. Hak Cipta Dilindungi.
                </p>
                <div class="flex items-center gap-1.5 text-[10px] 2xl:text-xs font-bold text-slate-400 dark:text-emerald-400/50 uppercase tracking-widest">
                    <span>Sistem Buku Tamu Terintegrasi SOWAN</span>
                    <i class="fas fa-heart text-[#008f5d] dark:text-emerald-500 animate-pulse mx-1"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- WIDGET CONTAINER (Kanan Bawah) -->
    <div x-data="{ 
        activeWidget: null,
        messages: [
            { sender: 'ai', text: 'Halo! Saya SOWAN AI Assistant. Ada pertanyaan seputar LPSE atau layanan pengadaan?' }
        ], 
        userInput: '',
        isTyping: false,
        async sendMessage() {
            if(this.userInput.trim() === '') return;
            let question = this.userInput;
            this.messages.push({ sender: 'user', text: question });
            this.userInput = '';
            this.isTyping = true;
            setTimeout(() => { this.scrollToBottom(); }, 50);

            try {
                let response = await fetch('/portal/sowan-ai/chat', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    },
                    body: JSON.stringify({ message: question })
                });
                let data = await response.json();
                this.messages.push({ sender: 'ai', text: data.reply });
            } catch (error) {
                this.messages.push({ sender: 'ai', text: 'SOWAN AI saat ini hanya bisa diakses penuh dari dalam Portal Petugas/Tamu. Silakan Login.' });
            } finally {
                this.isTyping = false;
                setTimeout(() => { this.scrollToBottom(); }, 50);
            }
        },
        scrollToBottom() {
            const container = document.getElementById('chat-container');
            if(container) { container.scrollTop = container.scrollHeight; }
        }
    }" class="fixed bottom-6 right-6 2xl:bottom-10 2xl:right-10 z-[9999] flex flex-col items-end gap-3.5 2xl:gap-5">

        <!-- TOMBOL KEMBALI KE ATAS -->
        <button x-show="scrolledFromTop" 
                @click="window.scrollTo({top: 0, behavior: 'smooth'})"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-10"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-10"
                class="w-12 h-12 2xl:w-14 2xl:h-14 bg-white dark:bg-emerald-800 text-[#008f5d] dark:text-emerald-400 border-2 border-slate-200 dark:border-emerald-700 rounded-xl shadow-lg flex items-center justify-center transition-all hover:bg-[#008f5d] hover:text-white dark:hover:bg-emerald-600 hover:-translate-y-1 mb-2"
                title="Kembali ke Atas" x-cloak>
            <i class="fas fa-arrow-up text-xl 2xl:text-2xl"></i>
        </button>

        <!-- Widget Chatbot AI -->
        <div class="relative" x-show="activeWidget === null || activeWidget === 'ai'">
            <button @click="activeWidget = activeWidget === 'ai' ? null : 'ai'" class="w-12 h-12 2xl:w-14 2xl:h-14 rounded-full bg-gradient-to-br from-[#008f5d] to-emerald-700 text-white shadow-xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 group border-2 border-white/20" title="SOWAN AI Assistant">
                <i class="fas fa-robot text-lg 2xl:text-xl group-hover:rotate-12 transition-transform" x-show="activeWidget !== 'ai'"></i>
                <i class="fas fa-times text-lg 2xl:text-xl" x-show="activeWidget === 'ai'" x-cloak></i>
                <span class="absolute top-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-white rounded-full animate-pulse" x-show="activeWidget !== 'ai'"></span>
            </button>

            <div x-show="activeWidget === 'ai'" 
                 x-transition.opacity.translate.y.duration.300ms
                 class="absolute bottom-full mb-3 right-0 w-[90vw] sm:w-[380px] h-[500px] 2xl:w-[450px] 2xl:h-[600px] bg-white dark:bg-emerald-900 border border-emerald-100 dark:border-emerald-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden backdrop-blur-lg" x-cloak>
                
                <div class="bg-gradient-to-r from-[#008f5d] to-emerald-700 p-4 text-white flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-white">
                            <i class="fas fa-brain text-emerald-200"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-sm 2xl:text-base tracking-wide uppercase">SOWAN AI Assistant</h3>
                            <span class="text-[10px] 2xl:text-xs text-emerald-200 flex items-center gap-1 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Online & Siap Membantu
                            </span>
                        </div>
                    </div>
                    <button @click="activeWidget = null" class="text-white/80 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>

                <div id="chat-container" class="flex-1 p-4 overflow-y-auto custom-scrollbar space-y-3 bg-slate-50/50 dark:bg-emerald-950/50 text-xs sm:text-sm 2xl:text-base">
                    <template x-for="msg in messages">
                        <div :class="msg.sender === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="msg.sender === 'user' ? 'bg-[#008f5d] text-white rounded-2xl rounded-tr-none' : 'bg-white dark:bg-emerald-800 text-slate-800 dark:text-emerald-50 border border-slate-100 dark:border-emerald-700 rounded-2xl rounded-tl-none shadow-sm'" 
                                 class="p-3.5 max-w-[80%] leading-relaxed font-medium">
                                <p x-text="msg.text" class="whitespace-pre-line"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="isTyping" class="flex justify-start" x-cloak>
                        <div class="bg-white dark:bg-emerald-800 border border-slate-100 dark:border-emerald-700 p-3 rounded-2xl rounded-tl-none shadow-sm flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce"></span>
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay:0.2s"></span>
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay:0.4s"></span>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white dark:bg-emerald-900 border-t border-emerald-50 dark:border-emerald-800">
                    <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                        <input type="text" x-model="userInput" placeholder="Ketik pertanyaan..." 
                               class="flex-1 bg-slate-100 dark:bg-emerald-950 border border-transparent focus:border-emerald-500 dark:focus:border-emerald-400 text-slate-800 dark:text-emerald-50 text-xs sm:text-sm 2xl:text-base rounded-xl px-4 py-3 focus:outline-none transition-all">
                        <button type="submit" class="w-11 h-11 2xl:w-12 2xl:h-12 rounded-xl bg-[#008f5d] hover:bg-emerald-700 text-white flex items-center justify-center shrink-0 shadow-md active:scale-95 transition-all">
                            <i class="fas fa-paper-plane text-xs 2xl:text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Widget WhatsApp Konsul -->
        <a href="https://wa.me/6281234567890" target="_blank" x-show="activeWidget === null" class="w-12 h-12 2xl:w-14 2xl:h-14 rounded-full bg-[#25D366] text-white shadow-xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 group cursor-pointer border-2 border-white/20" title="WhatsApp Konsultasi">
            <i class="fab fa-whatsapp text-2xl 2xl:text-3xl group-hover:rotate-12 transition-transform"></i>
        </a>

        <!-- Widget Aksesibilitas Disabilitas -->
        <div class="relative" x-show="activeWidget === null || activeWidget === 'a11y'">
            <button @click="activeWidget = activeWidget === 'a11y' ? null : 'a11y'" class="w-12 h-12 2xl:w-14 2xl:h-14 rounded-full bg-[#008f5d] hover:bg-emerald-700 text-white shadow-xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 group cursor-pointer border-2 border-emerald-400/20" title="Menu Aksesibilitas Disabilitas">
                <i class="fas fa-universal-access text-2xl 2xl:text-3xl group-hover:rotate-12 transition-transform" x-show="activeWidget !== 'a11y'"></i>
                <i class="fas fa-times text-xl" x-show="activeWidget === 'a11y'" x-cloak></i>
            </button>

            <div id="a11y-panel" x-show="activeWidget === 'a11y'" x-transition.opacity.translate.y.duration.300ms class="absolute bottom-full mb-3 right-0 w-80 2xl:w-96 bg-white dark:bg-emerald-900 border border-emerald-100 dark:border-emerald-800 rounded-3xl shadow-2xl p-4 2xl:p-5 flex flex-col gap-3 backdrop-blur-lg" x-cloak>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-emerald-800 pb-2">
                    <span class="font-black text-xs 2xl:text-sm uppercase tracking-wider text-slate-700 dark:text-emerald-100 flex items-center gap-2">
                        <i class="fas fa-universal-access text-[#008f5d] dark:text-emerald-400 text-lg"></i> Aksesibilitas Disabilitas
                    </span>
                    <button @click="activeWidget = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex flex-col gap-2 text-xs 2xl:text-sm max-h-[320px] 2xl:max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
                    <button onclick="toggleTextSize()" class="flex items-center justify-between p-2.5 2xl:p-3 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Perbesar Teks (Zoom)</span>
                        <i class="fas fa-text-height"></i>
                    </button>
                    <button onclick="toggleContrast()" class="flex items-center justify-between p-2.5 2xl:p-3 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Kontras Tinggi & Monokrom</span>
                        <i class="fas fa-adjust"></i>
                    </button>
                    <button onclick="toggleLineSpacing()" class="flex items-center justify-between p-2.5 2xl:p-3 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Spasi Baris Lebih Luas</span>
                        <i class="fas fa-arrows-alt-v"></i>
                    </button>
                    <button onclick="toggleHighlightLinks()" class="flex items-center justify-between p-2.5 2xl:p-3 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Sorot Tautan (Garis Bawah)</span>
                        <i class="fas fa-link"></i>
                    </button>
                    <button onclick="toggleDyslexiaFont()" class="flex items-center justify-between p-2.5 2xl:p-3 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Font Ramah Disleksia</span>
                        <i class="fas fa-font"></i>
                    </button>
                    <button onclick="resetA11y()" class="flex items-center justify-center gap-2 p-2.5 2xl:p-3 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-950/40 dark:text-red-400 transition-colors font-bold mt-1">
                        <i class="fas fa-undo"></i> Reset Pengaturan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Aksesibilitas Logika Panel -->
    <script>
        let isLargeText = false;
        let isHighContrast = false;
        let isLineSpacing = false;
        let isHighlightLinks = false;
        let isDyslexiaFont = false;

        function toggleTextSize() {
            isLargeText = !isLargeText;
            document.body.style.fontSize = isLargeText ? '1.25rem' : '';
        }

        function toggleContrast() {
            isHighContrast = !isHighContrast;
            if(isHighContrast) {
                document.documentElement.classList.add('contrast-more', 'grayscale');
            } else {
                document.documentElement.classList.remove('contrast-more', 'grayscale');
            }
        }

        function toggleLineSpacing() {
            isLineSpacing = !isLineSpacing;
            document.body.style.lineHeight = isLineSpacing ? '2.2' : '';
        }

        function toggleHighlightLinks() {
            isHighlightLinks = !isHighlightLinks;
            if(isHighlightLinks) {
                const style = document.createElement('style');
                style.id = 'a11y-link-style';
                style.innerHTML = 'a { text-decoration: underline !important; font-weight: bold !important; color: #34d399 !important; }';
                document.head.appendChild(style);
            } else {
                const el = document.getElementById('a11y-link-style');
                if(el) el.remove();
            }
        }

        function toggleDyslexiaFont() {
            isDyslexiaFont = !isDyslexiaFont;
            document.body.style.fontFamily = isDyslexiaFont ? 'Comic Sans MS, Arial, sans-serif' : '';
        }

        function resetA11y() {
            isLargeText = false;
            isHighContrast = false;
            isLineSpacing = false;
            isHighlightLinks = false;
            isDyslexiaFont = false;
            document.documentElement.classList.remove('contrast-more', 'grayscale');
            document.body.style.fontSize = '';
            document.body.style.lineHeight = '';
            document.body.style.fontFamily = '';
            const el = document.getElementById('a11y-link-style');
            if(el) el.remove();
        }
    </script>
</body>
</html>