@extends('layouts.app_tamu')

@section('title', 'Dashboard Tamu - SOWAN V2')

@section('content')
<div class="space-y-8 animate__animated animate__fadeIn">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-4xl font-black text-emerald-900 dark:text-emerald-400 tracking-tighter uppercase italic">
                Selamat Datang
            </h1>
            <p class="text-slate-500 dark:text-slate-400 font-bold mt-1 tracking-wide">
                {{ $tamu->nama_tamu }}, portal SOWAN Anda siap digunakan.
            </p>
        </div>
        <div class="bg-white dark:bg-emerald-900/30 px-6 py-3 rounded-2xl border border-slate-200 dark:border-emerald-800 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Waktu Akses</p>
            <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">{{ now()->format('d M Y | H:i') }} WIB</p>
        </div>
    </div>

    {{-- Statistik Ringkas Tamu (Dinamis dari Database) --}}
    @php
        $formattedTanggalTerakhir = $kunjunganTerakhirRecord 
            ? \Carbon\Carbon::parse($kunjunganTerakhirRecord->waktu_masuk)->translatedFormat('d F Y') 
            : 'Belum Ada';
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 p-6 rounded-[2rem] text-white shadow-sm">
            <p class="text-emerald-200 text-[10px] uppercase font-black tracking-[0.2em] mb-1">Total Kunjungan</p>
            <h3 class="text-4xl font-black tracking-tighter">{{ $totalKunjungan ?? 0 }} <span class="text-lg font-bold opacity-70">Kali</span></h3>
        </div>
        <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-400 text-[10px] uppercase font-black tracking-[0.2em] mb-1">Kunjungan Terakhir</p>
            <h3 class="text-xl font-black text-slate-800 dark:text-slate-200 mt-1">{{ $formattedTanggalTerakhir }}</h3>
        </div>
        <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-400 text-[10px] uppercase font-black tracking-[0.2em] mb-1">Status Akun</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <h3 class="text-xl font-black text-emerald-600">TERVERIFIKASI</h3>
            </div>
        </div>
    </div>

    {{-- Quick Actions & Card Buku Pedoman / Panduan PBJ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Card Aksi Cepat --}}
        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col justify-between">
            <div>
                <h3 class="text-emerald-900 dark:text-emerald-400 font-black uppercase text-sm tracking-widest mb-6">Aksi Cepat</h3>
                <div class="grid grid-cols-2 gap-4">
                    <a href="{{ route('tamu.form_tamu_lama') }}" class="flex flex-col items-center justify-center p-6 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-all border border-emerald-100 dark:border-emerald-800">
                        <i class="fas fa-plus text-2xl text-emerald-600 mb-3"></i>
                        <span class="text-xs font-bold text-emerald-900 dark:text-emerald-100">Kunjungan Baru</span>
                    </a>
                    <a href="{{ route('tamu.riwayat') }}" class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-800 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800">
                        <i class="fas fa-history text-2xl text-slate-500 mb-3"></i>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Riwayat</span>
                    </a>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Butuh Konsultasi?</span>
                <a href="{{ route('tamu.konsultasi_online.index') }}" class="text-xs font-black text-emerald-600 hover:underline">Jadwal Online →</a>
            </div>
        </div>

        {{-- Card Buku Pedoman & Panduan Pengadaan Barang dan Jasa --}}
        <div class="bg-slate-900 dark:bg-emerald-950 p-8 rounded-[2.5rem] text-white shadow-sm relative overflow-hidden border border-slate-800 flex flex-col justify-between">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-xl italic uppercase">Buku Pedoman & Panduan PBJ</h3>
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-wider rounded-full border border-emerald-500/30">Dokumen Resmi</span>
                </div>
                <p class="text-emerald-200 text-xs mb-6">Akses dan unduh peraturan, buku pedoman, serta panduan teknis Pengadaan Barang/Jasa Pemerintah terbaru dari LPSE.</p>
                
                <button type="button" onclick="openPedomanModal()" class="inline-flex items-center px-6 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-95">
                    <i class="fas fa-book-open mr-2"></i> Buka Daftar Buku Pedoman →
                </button>
            </div>
            <i class="fas fa-file-pdf absolute -bottom-6 -right-6 text-[150px] text-white/5 rotate-12 pointer-events-none"></i>
        </div>
    </div>
</div>

{{-- MODAL DAFTAR BUKU PEDOMAN & PANDUAN PBJ --}}
<div id="pedomanModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate__animated animate__fadeIn">
    <div class="bg-white dark:bg-slate-900 w-full max-w-3xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col max-h-[85vh]">
        
        {{-- Modal Header --}}
        <div class="p-6 md:p-8 bg-emerald-900 text-white flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-800 flex items-center justify-center text-emerald-300">
                    <i class="fas fa-book text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black uppercase tracking-wider">Perpustakaan Buku Pedoman PBJ</h3>
                    <p class="text-xs text-emerald-300 font-medium">Dokumen panduan resmi LPSE Kabupaten Karawang</p>
                </div>
            </div>
            <button type="button" onclick="closePedomanModal()" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Modal Body (List Dokumen PDF dari Model Layanan) --}}
        <div class="p-6 md:p-8 overflow-y-auto space-y-4 flex-grow">
            @php
                $hasDocs = false;
                if(isset($dokumenPedoman)) {
                    foreach($dokumenPedoman as $l) {
                        if($l->dokumens && $l->dokumens->count() > 0) {
                            $hasDocs = true;
                            break;
                        }
                    }
                }
            @endphp

            @if(isset($dokumenPedoman) && $hasDocs)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($dokumenPedoman as $l)
                        @if($l->dokumens && $l->dokumens->count() > 0)
                            @foreach($l->dokumens as $doc)
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-emerald-500/50 transition-all gap-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400 flex items-center justify-center font-black flex-shrink-0 text-lg shadow-sm">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight">{{ $doc->nama_dokumen }}</h4>
                                            <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mt-0.5">
                                                Kategori: {{ $l->nama_layanan }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center">
                                            <i class="fas fa-eye mr-1.5"></i> Lihat
                                        </a>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" download="{{ $doc->nama_dokumen }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider transition-all shadow-md flex items-center">
                                            <i class="fas fa-download mr-1.5"></i> Unduh
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 space-y-3">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Belum ada buku pedoman atau dokumen yang diunggah admin.</p>
                </div>
            @endif
        </div>

        {{-- Modal Footer --}}
        <div class="p-4 md:p-6 bg-slate-50 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex justify-end">
            <button type="button" onclick="closePedomanModal()" class="px-6 py-2.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs uppercase tracking-wider rounded-xl transition-all">
                Tutup
            </button>
        </div>

    </div>
</div>

{{-- SCRIPT MODAL BUKU PEDOMAN --}}
<script>
    function openPedomanModal() {
        document.getElementById('pedomanModal').classList.remove('hidden');
    }
    function closePedomanModal() {
        document.getElementById('pedomanModal').classList.add('hidden');
    }
</script>
@endsection