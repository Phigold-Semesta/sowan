<!DOCTYPE html>
<html lang="id" x-data="{ 
    darkMode: localStorage.getItem('theme') === 'dark',
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
    }
}" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | SOWAN LPSE Karawang</title>
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'sowan-emerald': '#008f5d',
                        'sowan-gold': '#b45309',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --emerald-primary: #008f5d;
            --emerald-light: #ecfdf5;
            --gold-accent: #b45309;
            --gold-light: #fffbeb;
            --gray-soft: #6b7280;
            --gray-bg: #f3f4f6;
            --dark-emerald: #064e3b;
        }

        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            transition: background-color 0.3s ease;
        }
        
        .sidebar-active {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        #main-sidebar {
            width: 88px; 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 800;
            border: 1px solid;
            display: inline-flex;
            align-items: center;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        @media (min-width: 1024px) {
            #main-sidebar:hover, #main-sidebar.desktop-expanded { width: 288px; }

            #main-sidebar .nav-text, 
            #main-sidebar .logo-full, 
            #main-sidebar .menu-header {
                opacity: 0;
                transition: opacity 0.3s ease;
                display: none;
            }

            #main-sidebar:hover .nav-text, #main-sidebar.desktop-expanded .nav-text,
            #main-sidebar:hover .logo-full, #main-sidebar.desktop-expanded .logo-full, 
            #main-sidebar:hover .menu-header, #main-sidebar.desktop-expanded .menu-header {
                opacity: 1;
                display: flex;
            }

            #main-sidebar .icon-buku-collapsed { display: flex; }
            #main-sidebar:hover .icon-buku-collapsed, #main-sidebar.desktop-expanded .icon-buku-collapsed { display: none; }
            
            #main-sidebar .nav-item { justify-content: center; }
            #main-sidebar:hover .nav-item, #main-sidebar.desktop-expanded .nav-item { justify-content: flex-start; }

            #main-sidebar:hover .btn-logout, #main-sidebar.desktop-expanded .btn-logout { justify-content: flex-start !important; }
        }

        @media (max-width: 1024px) {
            #main-sidebar { position: fixed; left: -100%; width: 280px; }
            #main-sidebar.show-sidebar { left: 0; }
            #main-sidebar .nav-text, #main-sidebar .logo-full { display: flex; opacity: 1; }
            #main-sidebar .icon-buku-collapsed { display: none; }
            .btn-logout { justify-content: center !important; }
        }

        .swal2-popup {
            border-radius: 2rem !important;
            padding: 2rem !important;
        }
        .swal2-styled.swal2-confirm {
            background-color: #008f5d !important;
            border-radius: 1rem !important;
            padding: 0.75rem 2rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            font-size: 0.75rem !important;
        }
    </style>
</head>
<body class="antialiased text-slate-800 bg-[#f0f9f4] dark:bg-emerald-950 dark:text-emerald-50 transition-colors duration-300">

    <div id="sidebar-overlay" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/40 z-30 hidden backdrop-blur-sm"></div>

    <div class="flex min-h-screen relative overflow-hidden">
        
        <aside id="main-sidebar" class="bg-[#008f5d] dark:bg-emerald-900 h-screen text-white flex flex-col z-40 shadow-2xl shrink-0 overflow-hidden group">
            
            <div class="p-6 h-24 flex items-center border-b border-white/10 shrink-0">
                <div class="logo-full items-center gap-3">
                    <div class="bg-white p-2 rounded-xl shadow-lg shrink-0">
                        <i class="fas fa-book-open text-[#008f5d] text-lg"></i>
                    </div>
                    <span class="font-extrabold tracking-tighter text-lg uppercase leading-none">SOWAN<br><span class="text-emerald-300 text-xs text-nowrap">LPSE Karawang</span></span>
                </div>

                <div class="icon-buku-collapsed w-full justify-center">
                    <div class="bg-white p-3 rounded-2xl shadow-xl border-4 border-emerald-400/30">
                        <i class="fas fa-book-open text-[#008f5d] text-2xl"></i>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 mt-6 overflow-y-auto custom-scrollbar space-y-2">
                <div class="menu-header px-4 py-3 text-[10px] font-black text-emerald-200/50 uppercase tracking-[0.2em]">Navigasi Utama</div>
    
                <a href="{{ route('dashboard') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('dashboard') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                    <i class="fas fa-chart-line w-6 text-center text-sm"></i>
                    <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">
                        {{ auth()->user()->role === 'pimpinan' ? 'Dashboard Eksekutif' : 'Dashboard' }}
                    </span>
                </a>

                {{-- Menu Khusus Pimpinan --}}
                @if(auth()->user()->role === 'pimpinan')
                    <a href="{{ route('pimpinan.laporan.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('pimpinan.laporan.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-file-export w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Rekapitulasi Laporan</span>
                    </a>
                    <a href="{{ route('pimpinan.aktivitas.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('pimpinan.aktivitas.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-fingerprint w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Aktivitas Global</span>
                    </a>
                    <a href="{{ route('pimpinan.rating.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('pimpinan.rating.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-star-half-stroke w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Rating Layanan</span>
                    </a>
                    <a href="{{ route('pimpinan.konsultasi.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('pimpinan.konsultasi.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <div class="relative w-6 text-center">
                            <i class="fas fa-video text-sm"></i>
                            {{-- Notif Badge Dinamis Khusus Pimpinan --}}
                            @php 
                                $jumlahNotif = \App\Models\Konsultasi::where('status', 'pending')->where('id_user', auth()->user()->id_user)->count(); 
                            @endphp
                            @if($jumlahNotif >= 0)
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[9px] font-black h-4 w-4 flex items-center justify-center rounded-full border-2 border-[#008f5d]">
                                    {{ $jumlahNotif }}
                                </span>
                            @endif
                        </div>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Konsultasi Online</span>
                    </a>
                @endif

                {{-- Menu Khusus Petugas --}}
                @if(auth()->user()->role === 'petugas')
                    <a href="{{ route('petugas.manajemen_tamu.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('petugas.manajemen_tamu.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-user-check w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Data Tamu</span>
                    </a>
                    <a href="{{ route('petugas.konsultasi.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('petugas.konsultasi.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <div class="relative w-6 text-center">
                            <i class="fas fa-video text-sm"></i>
                            {{-- Notif Badge Dinamis Khusus Petugas --}}
                            @php 
                                $jumlahNotif = \App\Models\Konsultasi::where('status', 'pending')->where('id_user', auth()->user()->id_user)->count(); 
                            @endphp
                            @if($jumlahNotif >= 0)
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[9px] font-black h-4 w-4 flex items-center justify-center rounded-full border-2 border-[#008f5d]">
                                    {{ $jumlahNotif }}
                                </span>
                            @endif
                        </div>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Konsultasi Online</span>
                    </a>
                @endif

                {{-- Menu Khusus Administrator --}}
                @if(auth()->user()->role === 'administrator')
                    <div class="menu-header px-4 py-3 text-[10px] font-black text-emerald-200/50 uppercase tracking-[0.2em] mt-4">Manajemen Sistem</div>
                    <a href="{{ route('admin.users.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('admin.users.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-users-gear w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Manajemen Pengguna</span>
                    </a>
                    <a href="{{ route('admin.master.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('admin.master.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-database w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Data Master</span>
                    </a>
                    <a href="{{ route('admin.aktivitas.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('admin.aktivitas.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-fingerprint w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Aktivitas Global</span>
                    </a>
                    
                    <div class="menu-header px-4 py-3 text-[10px] font-black text-emerald-200/50 uppercase tracking-[0.2em] mt-4">Monitoring & Output</div>
                    <a href="{{ route('admin.rating.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('admin.rating.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-star-half-stroke w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Rating Layanan</span>
                    </a>
                    <a href="{{ route('admin.laporan.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('admin.laporan.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-file-export w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Laporan Global</span>
                    </a>
                    <a href="{{ route('admin.konsultasi.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('admin.konsultasi.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <div class="relative w-6 text-center">
                            <i class="fas fa-video text-sm"></i>
                            {{-- Notif Badge Dinamis Khusus Admin (Menghitung Semua) --}}
                            @php 
                                $jumlahNotif = \App\Models\Konsultasi::where('status', 'pending')->count(); 
                            @endphp
                            @if($jumlahNotif >= 0)
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[9px] font-black h-4 w-4 flex items-center justify-center rounded-full border-2 border-[#008f5d]">
                                    {{ $jumlahNotif }}
                                </span>
                            @endif
                        </div>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Konsultasi Online</span>
                    </a>
                @endif
                
                {{-- Menu Monitoring (Petugas) --}}
                @if(auth()->user()->role === 'petugas')
                    <div class="menu-header px-4 py-3 text-[10px] font-black text-emerald-200/50 uppercase tracking-[0.2em] mt-4">Monitoring & Output</div>
                    <a href="{{ route('petugas.rating.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('petugas.rating.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-star-half-stroke w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Rating Layanan</span>
                    </a>
                    <a href="{{ route('petugas.laporan.index') }}" class="nav-item flex items-center py-4 px-5 rounded-2xl transition-all {{ Route::is('petugas.laporan.*') ? 'sidebar-active' : 'hover:bg-white/10' }}">
                        <i class="fas fa-file-export w-6 text-center text-sm"></i>
                        <span class="nav-text ml-3 text-sm font-bold tracking-wide text-nowrap">Laporan Kunjungan</span>
                    </a>
                @endif
            </nav>

            <div class="p-4 mb-4">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
                <button onclick="confirmLogout(event)" class="btn-logout nav-item w-full flex items-center justify-center py-4 px-6 rounded-2xl bg-gradient-to-br from-emerald-800 to-emerald-600 hover:from-emerald-700 hover:to-emerald-500 hover:shadow-[0_8px_20px_rgba(16,185,129,0.3)] text-emerald-50 transition-all border border-emerald-500/20 group">
                    <i class="fas fa-power-off w-6 text-center group-hover:scale-110 transition-transform"></i>
                    <span class="nav-text ml-3 text-sm font-bold tracking-widest uppercase text-nowrap">Log out</span>
                </button>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
            <header class="h-20 bg-white dark:bg-emerald-900 border-b border-emerald-50 dark:border-emerald-800 shadow-sm flex justify-between items-center px-8 z-20 shrink-0 transition-colors duration-300">
                <div class="flex items-center gap-4">
                    <button onclick="toggleMobileSidebar()" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-800 text-[#008f5d] dark:text-emerald-400">
                        <i class="fas fa-bars"></i>
                    </button>
                    {{-- Tombol Burger Desktop Baru --}}
                    <button onclick="toggleDesktopSidebar()" class="hidden lg:flex w-10 h-10 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-800 text-[#008f5d] dark:text-emerald-400 hover:bg-emerald-100 transition-all">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="flex flex-col leading-none">
                        <h2 class="text-slate-800 dark:text-white font-black text-xl tracking-tighter uppercase italic">@yield('title', 'Dashboard')</h2>
                        <p class="text-[9px] font-bold text-slate-400 dark:text-emerald-400/60 uppercase tracking-[0.25em] mt-1.5 hidden sm:block">SOWAN v2 • LPSE Karawang</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button @click="toggleTheme()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-500 dark:text-yellow-400 border border-slate-100 dark:border-emerald-700 transition-all hover:scale-110">
                        <i x-show="darkMode" class="fa-solid fa-sun text-lg" x-cloak></i>
                        <i x-show="!darkMode" class="fa-solid fa-moon text-lg" x-cloak></i>
                    </button>

                    <div class="flex items-center gap-4 bg-slate-50 dark:bg-emerald-800/50 py-1.5 pl-4 pr-1.5 rounded-2xl border border-slate-100 dark:border-emerald-700">
                        <div class="text-right leading-tight hidden md:block">
                            <p class="text-xs font-black text-slate-800 dark:text-emerald-50 uppercase tracking-tighter">{{ auth()->user()->nama_lengkap }}</p>
                            <div class="flex items-center justify-end gap-1.5 mt-0.5">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                                <p class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest italic leading-none">{{ strtoupper(auth()->user()->role) }}</p>
                            </div>
                        </div>
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->nama_lengkap) }}&background=008f5d&color=fff&bold=true" class="w-10 h-10 rounded-xl border-2 border-white dark:border-emerald-700 shadow-sm">
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    <!-- KONTAINER UTAMA 3 WIDGET MENGAMBANG (DENGAN URL PREFIX DINAMIS UNTUK ADMINISTRATOR/PETUGAS/PIMPINAN) -->
    <div x-data="{ 
        activeWidget: null,
        rolePrefix: '{{ auth()->user()->role === "administrator" ? "admin" : auth()->user()->role }}',
        
        // AI State
        messages: [
            { sender: 'ai', text: 'Halo! Saya SOWAN AI Assistant siap membantu Anda seputar informasi layanan konsultasi dan pengadaan barang/jasa di LPSE Karawang. Ada yang bisa saya bantu?' }
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
                let response = await fetch('/' + this.rolePrefix + '/ai-chat', {
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
                this.messages.push({ sender: 'ai', text: 'Maaf, terjadi kesalahan koneksi ke server SOWAN AI.' });
            } finally {
                this.isTyping = false;
                setTimeout(() => { this.scrollToBottom(); }, 50);
            }
        },
        scrollToBottom() {
            const container = document.getElementById('chat-container');
            if(container) { container.scrollTop = container.scrollHeight; }
        },

        // CS State
        messagesCS: [], 
        inputCS: '',
        initCS() {
            this.fetchMessages();
            setInterval(() => { if(this.activeWidget === 'cs') this.fetchMessages(); }, 3000);
        },
        async fetchMessages() {
            try {
                let res = await fetch('/' + this.rolePrefix + '/chat-fetch');
                let data = await res.json();
                this.messagesCS = data.messages;
            } catch(e) {}
        },
        async sendCS() {
            if(this.inputCS.trim() === '') return;
            let msg = this.inputCS;
            this.inputCS = '';
            try {
                await fetch('/' + this.rolePrefix + '/chat-send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    },
                    body: JSON.stringify({ message: msg })
                });
                this.fetchMessages();
                setTimeout(() => { this.scrollCS(); }, 50);
            } catch(e) {}
        },
        scrollCS() {
            const el = document.getElementById('cs-container');
            if(el) el.scrollTop = el.scrollHeight;
        }
    }" x-init="initCS()" class="fixed bottom-6 right-6 z-[9999] flex flex-col-reverse items-end gap-3.5">

        <!-- 1. WIDGET CHATBOT AI SOWAN ASSISTANT (Paling Bawah) -->
        <div class="relative" x-show="activeWidget === null || activeWidget === 'ai'">
            <button @click="activeWidget = activeWidget === 'ai' ? null : 'ai'" class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-600 to-[#008f5d] text-white shadow-2xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 group cursor-pointer border-2 border-white/20" title="SOWAN AI Assistant">
                <i class="fas fa-robot text-base group-hover:rotate-12 transition-transform" x-show="activeWidget !== 'ai'"></i>
                <i class="fas fa-times text-base" x-show="activeWidget === 'ai'" x-cloak></i>
                <span class="absolute top-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-white rounded-full animate-pulse" x-show="activeWidget !== 'ai'"></span>
            </button>

            <div x-show="activeWidget === 'ai'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                 x-cloak
                 class="absolute bottom-full mb-3 right-0 w-[90vw] sm:w-[380px] h-[500px] bg-white dark:bg-emerald-900 border border-emerald-100 dark:border-emerald-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden backdrop-blur-lg">
                
                <div class="bg-gradient-to-r from-[#008f5d] to-emerald-700 p-4 text-white flex items-center justify-between shrink-0 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-white">
                            <i class="fas fa-brain text-emerald-200"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-sm tracking-wide uppercase">SOWAN AI Assistant</h3>
                            <span class="text-[10px] text-emerald-200 flex items-center gap-1 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Online & Siap Membantu
                            </span>
                        </div>
                    </div>
                    <button @click="activeWidget = null" class="text-white/80 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>

                <div id="chat-container" class="flex-1 p-4 overflow-y-auto custom-scrollbar space-y-3 bg-slate-50/50 dark:bg-emerald-950/50 text-xs sm:text-sm">
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
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white dark:bg-emerald-900 border-t border-emerald-50 dark:border-emerald-800 shrink-0">
                    <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                        <input type="text" x-model="userInput" placeholder="Ketik pertanyaan seputar LPSE Karawang..." 
                               class="flex-1 bg-slate-100 dark:bg-emerald-950 border border-transparent focus:border-emerald-500 dark:focus:border-emerald-400 text-slate-800 dark:text-emerald-50 text-xs sm:text-sm rounded-xl px-4 py-3 focus:outline-none transition-all">
                        <button type="submit" class="w-11 h-11 rounded-xl bg-[#008f5d] hover:bg-emerald-700 text-white flex items-center justify-center shrink-0 shadow-md active:scale-95 transition-all">
                            <i class="fas fa-paper-plane text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. WIDGET LIVE CUSTOMER SERVICE (CS) (Di Tengah) -->
        <div class="relative" x-show="activeWidget === null || activeWidget === 'cs'">
            <button @click="activeWidget = activeWidget === 'cs' ? null : 'cs'; if(activeWidget === 'cs') setTimeout(() => scrollCS(), 50)" class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-2xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 group cursor-pointer border-2 border-white/20" title="Live Chat Customer Service">
                <i class="fas fa-headset text-base group-hover:rotate-12 transition-transform" x-show="activeWidget !== 'cs'"></i>
                <i class="fas fa-times text-base" x-show="activeWidget === 'cs'" x-cloak></i>
            </button>

            <div x-show="activeWidget === 'cs'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                 x-cloak
                 class="absolute bottom-full mb-3 right-0 w-[90vw] sm:w-[380px] h-[480px] bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden backdrop-blur-lg">
                
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-4 text-white flex items-center justify-between shrink-0 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-white">
                            <i class="fas fa-user-shield text-blue-200"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-sm tracking-wide uppercase">Live Customer Service</h3>
                            <span class="text-[10px] text-blue-200 flex items-center gap-1 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Terhubung dengan Petugas
                            </span>
                        </div>
                    </div>
                    <button @click="activeWidget = null" class="text-white/80 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>

                <div id="cs-container" class="flex-1 p-4 overflow-y-auto custom-scrollbar space-y-3 bg-slate-50/50 dark:bg-slate-950/50 text-xs sm:text-sm">
                    <div class="flex justify-start">
                        <div class="bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-100 dark:border-slate-700 rounded-2xl rounded-tl-none shadow-sm p-3.5 max-w-[80%] font-medium">
                            <p>Halo! Selamat datang di Layanan Bantuan Live CS SOWAN LPSE Karawang. Ada kendala atau pertanyaan yang bisa dibantu oleh petugas kami?</p>
                        </div>
                    </div>
                    <template x-for="chat in messagesCS">
                        <div :class="chat.sender_type === '{{ auth()->user()->role }}' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="chat.sender_type === '{{ auth()->user()->role }}' ? 'bg-blue-600 text-white rounded-2xl rounded-tr-none' : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-100 dark:border-slate-700 rounded-2xl rounded-tl-none shadow-sm'" 
                                 class="p-3.5 max-w-[80%] leading-relaxed font-medium">
                                <p x-text="chat.message" class="whitespace-pre-line"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-3 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 shrink-0">
                    <form @submit.prevent="sendCS()" class="flex items-center gap-2">
                        <input type="text" x-model="inputCS" placeholder="Ketik pesan ke petugas CS..." 
                               class="flex-1 bg-slate-100 dark:bg-slate-950 border border-transparent focus:border-blue-500 text-slate-800 dark:text-slate-100 text-xs sm:text-sm rounded-xl px-4 py-3 focus:outline-none transition-all">
                        <button type="submit" class="w-11 h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shrink-0 shadow-md active:scale-95 transition-all">
                            <i class="fas fa-paper-plane text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. WIDGET AKSESIBILITAS RAMAH DISABILITAS (Paling Atas di Kolom Kanan) -->
        <div class="relative" x-show="activeWidget === null || activeWidget === 'a11y'">
            <button @click="activeWidget = activeWidget === 'a11y' ? null : 'a11y'" class="w-12 h-12 rounded-full bg-slate-800 dark:bg-emerald-700 text-white shadow-xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 group cursor-pointer border-2 border-white/20" title="Menu Aksesibilitas Disabilitas">
                <i class="fas fa-universal-access text-base group-hover:rotate-45 transition-transform" x-show="activeWidget !== 'a11y'"></i>
                <i class="fas fa-times text-base" x-show="activeWidget === 'a11y'" x-cloak></i>
            </button>

            <div id="a11y-panel" x-show="activeWidget === 'a11y'" class="absolute bottom-full mb-3 right-0 w-80 bg-white dark:bg-emerald-900 border border-emerald-100 dark:border-emerald-800 rounded-3xl shadow-2xl p-4 flex flex-col gap-3 backdrop-blur-lg">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-emerald-800 pb-2">
                    <span class="font-black text-xs uppercase tracking-wider text-slate-700 dark:text-emerald-100 flex items-center gap-2">
                        <i class="fas fa-universal-access text-emerald-600 dark:text-emerald-400"></i> Aksesibilitas Ramah Disabilitas
                    </span>
                    <button @click="activeWidget = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex flex-col gap-2 text-xs max-h-[320px] overflow-y-auto custom-scrollbar pr-1">
                    <button onclick="toggleTextSize()" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Perbesar Teks (Zoom)</span>
                        <i class="fas fa-text-height"></i>
                    </button>
                    <button onclick="toggleContrast()" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Kontras Tinggi & Monokrom</span>
                        <i class="fas fa-adjust"></i>
                    </button>
                    <button onclick="toggleLineSpacing()" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Spasi Baris Lebih Luas</span>
                        <i class="fas fa-arrows-alt-v"></i>
                    </button>
                    <button onclick="toggleHighlightLinks()" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Sorot & Garisbawahi Tautan</span>
                        <i class="fas fa-link"></i>
                    </button>
                    <button onclick="toggleDyslexiaFont()" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-emerald-800 text-slate-700 dark:text-emerald-50 hover:bg-emerald-50 dark:hover:bg-emerald-700 transition-colors font-semibold">
                        <span>Font Ramah Disleksia</span>
                        <i class="fas fa-font"></i>
                    </button>
                    <button onclick="resetA11y()" class="flex items-center justify-center gap-2 p-2.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-950/40 dark:text-red-400 transition-colors font-bold mt-1">
                        <i class="fas fa-undo text-[10px]"></i> Reset Pengaturan
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('show-sidebar');
            overlay.classList.toggle('hidden');
        }

        // Fungsi toggle untuk Desktop
        function toggleDesktopSidebar() {
            document.getElementById('main-sidebar').classList.toggle('desktop-expanded');
        }

        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin ingin keluar?',
                text: "Sesi Anda akan segera diakhiri.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#008f5d',
                cancelButtonColor: '#f1f5f9',
                confirmButtonText: 'YA, LOG OUT',
                cancelButtonText: 'BATAL',
                reverseButtons: true,
                background: document.documentElement.classList.contains('dark') ? '#064e3b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#ecfdf5' : '#1e293b',
                customClass: {
                    cancelButton: 'text-slate-600 font-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        }

        let isLargeText = false;
        let isHighContrast = false;
        let isLineSpacing = false;
        let isHighlightLinks = false;
        let isDyslexiaFont = false;

        function toggleA11yPanel() {
            const panel = document.getElementById('a11y-panel');
            if(panel) panel.classList.toggle('hidden');
        }

        function toggleTextSize() {
            isLargeText = !isLargeText;
            document.body.style.fontSize = isLargeText ? '1.18rem' : '1rem';
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
            document.body.style.lineHeight = isLineSpacing ? '2.2' : '1.5';
        }

        function toggleHighlightLinks() {
            isHighlightLinks = !isHighlightLinks;
            if(isHighlightLinks) {
                const style = document.createElement('style');
                style.id = 'a11y-link-style';
                style.innerHTML = 'a { text-decoration: underline !important; font-weight: bold !important; color: #047857 !important; }';
                document.head.appendChild(style);
            } else {
                const el = document.getElementById('a11y-link-style');
                if(el) el.remove();
            }
        }

        function toggleDyslexiaFont() {
            isDyslexiaFont = !isDyslexiaFont;
            document.body.style.fontFamily = isDyslexiaFont ? 'Comic Sans MS, Arial, sans-serif' : "'Plus Jakarta Sans', sans-serif";
        }

        function resetA11y() {
            isLargeText = false;
            isHighContrast = false;
            isLineSpacing = false;
            isHighlightLinks = false;
            isDyslexiaFont = false;
            document.documentElement.classList.remove('contrast-more', 'grayscale');
            document.body.style.fontSize = '1rem';
            document.body.style.lineHeight = '1.5';
            document.body.style.fontFamily = "'Plus Jakarta Sans', sans-serif";
            const el = document.getElementById('a11y-link-style');
            if(el) el.remove();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: document.documentElement.classList.contains('dark') ? '#064e3b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#ecfdf5' : '#1e293b',
            });

            @if(session('success'))
                Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Waduh...',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#008f5d',
                    background: document.documentElement.classList.contains('dark') ? '#064e3b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#ecfdf5' : '#1e293b',
                });
            @endif
        });
    </script>
</body>
</html>