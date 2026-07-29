<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Data Tamu - SOWAN v2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);
            background-attachment: fixed;
            overflow-x: hidden;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .validation-card {
            animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            backdrop-filter: blur(20px);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.9) translateY(40px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .blob {
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(16, 185, 129, 0.1);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            animation: float 10s infinite alternate ease-in-out;
        }

        @keyframes float {
            from { transform: translate(0, 0); }
            to { transform: translate(30px, 50px); }
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #065f46; border-radius: 10px; }
    </style>
</head>
<body class="p-4 sm:p-6 md:p-10">

    <div class="blob -top-20 -left-20"></div>
    <div class="blob -bottom-20 -right-20" style="background: rgba(52, 211, 153, 0.08); animation-delay: -5s;"></div>

    <div class="w-full max-w-lg md:max-w-xl mx-auto text-center relative z-10">
        
        <!-- Header Brand -->
        <div class="mb-6 md:mb-10">
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-black italic tracking-tighter text-white leading-none">
                SOWAN <span class="text-emerald-400">V2</span>
            </h1>
            <div class="flex items-center justify-center space-x-3 mt-3 opacity-60">
                <div class="h-[1px] w-6 md:w-10 bg-emerald-400"></div>
                <p class="text-[9px] sm:text-[10px] md:text-xs font-black uppercase tracking-[0.4em] text-emerald-100">
                    LPSE Kabupaten Karawang
                </p>
                <div class="h-[1px] w-6 md:w-10 bg-emerald-400"></div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="validation-card bg-white rounded-[2.5rem] sm:rounded-[3rem] md:rounded-[4rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.8)] p-6 sm:p-10 md:p-12 relative overflow-hidden border border-white/40 text-center space-y-6">
            
            <!-- Icon Badge -->
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-emerald-50 text-[#008f5d] rounded-2xl sm:rounded-3xl flex items-center justify-center mx-auto text-2xl sm:text-3xl font-black shadow-inner">
                <i class="fas fa-user-check"></i>
            </div>

            <!-- Title & Description -->
            <div class="space-y-2">
                <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-800 uppercase tracking-tighter italic px-2">
                    Selamat Datang <span class="text-[#008f5d]">{{ $tamu->nama_tamu }}</span>!
                </h2>
                <p class="text-slate-500 text-[10px] sm:text-xs font-bold uppercase tracking-widest leading-relaxed px-4">
                    Kami mendeteksi akun dengan email <span class="text-slate-800 font-black">{{ $tamu->gmail }}</span> sudah terdaftar di sistem SOWAN V2.
                </p>
            </div>

            <!-- Profile Info Box -->
            <div class="bg-slate-50 p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-100 text-left space-y-2.5">
                <div class="flex flex-col sm:flex-row sm:justify-between text-xs gap-1 sm:gap-0">
                    <span class="font-black text-slate-400 uppercase tracking-wider text-[10px] sm:text-xs">Instansi:</span>
                    <span class="font-black text-slate-700 uppercase text-xs sm:text-sm truncate max-w-full sm:max-w-[280px]">{{ $tamu->nama_instansi }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between text-xs gap-1 sm:gap-0">
                    <span class="font-black text-slate-400 uppercase tracking-wider text-[10px] sm:text-xs">No. WhatsApp:</span>
                    <span class="font-black text-slate-700 text-xs sm:text-sm">{{ $tamu->no_wa }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between text-xs gap-1 sm:gap-0">
                    <span class="font-black text-slate-400 uppercase tracking-wider text-[10px] sm:text-xs">Jenis Tamu:</span>
                    <span class="font-black text-slate-700 uppercase text-xs sm:text-sm">{{ $tamu->jenis_tamu }}</span>
                </div>
            </div>

            <p class="text-[10px] sm:text-[11px] font-bold text-slate-600 uppercase tracking-wide leading-relaxed px-2">
                Apakah Anda ingin menggunakan data perusahaan/profil tersebut di atas untuk kunjungan kali ini, atau ingin memperbaruinya?
            </p>

            <!-- Action Buttons Form -->
            <form action="{{ route('tamu.proses_validasi') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <button type="submit" name="pilihan" value="lama" class="w-full py-3.5 sm:py-4 bg-[#008f5d] hover:bg-emerald-600 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-emerald-200 transition-all active:scale-95 flex items-center justify-center">
                        <i class="fas fa-check-circle mr-2"></i> Gunakan Data Lama
                    </button>
                    <button type="submit" name="pilihan" value="baru" class="w-full py-3.5 sm:py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all active:scale-95 border border-slate-200 flex items-center justify-center">
                        <i class="fas fa-edit mr-2"></i> Ubah / Data Baru
                    </button>
                </div>
            </form>

            <!-- Back Link -->
            <div class="pt-2 sm:pt-4 border-t border-slate-100">
                <a href="{{ route('tamu.onsite.view') }}" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors inline-flex items-center">
                    <i class="fas fa-arrow-left mr-1.5"></i> Batalkan & Kembali
                </a>
            </div>
        </div>

        <!-- Footer System Info -->
        <p class="mt-6 sm:mt-8 text-[8px] md:text-[10px] text-emerald-100/30 uppercase font-black tracking-[0.5em]">
            Verified Guest Management System
        </p>
    </div>

</body>
</html>