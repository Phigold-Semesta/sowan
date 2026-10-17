@extends('layouts.app')

@section('title', 'Kamera AI Scanner')

@section('content')
<div class="max-w-7xl mx-auto space-y-8 px-4">

    <!-- Header Judul -->
    <div class="bg-white dark:bg-emerald-900 border border-slate-200 dark:border-emerald-800 px-8 py-6 rounded-3xl shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4 transition-colors">
        <div>
            <h1 class="text-2xl font-black italic tracking-tight text-slate-900 dark:text-emerald-50">KAMERA AI SCANNER</h1>
            <p class="text-sm font-medium text-slate-500 dark:text-emerald-200/70 mt-0.5">Pindai wajah (YOLO + DeepFace) & suarakan keperluan Anda (Whisper AI).</p>
        </div>
        <div class="flex items-center space-x-2 bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-700 px-4 py-2 rounded-full text-xs font-bold text-emerald-700 dark:text-emerald-400">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Webcam Controller Ready</span>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

        <!-- Sisi Kiri: Viewport Webcam -->
        <div class="xl:col-span-2 bg-zinc-900 border border-zinc-800 p-6 rounded-3xl shadow-2xl flex flex-col space-y-4">

            <div class="flex flex-col space-y-1">
                <label class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Pilih Perangkat Kamera:</label>
                <select id="camera-select" class="w-full px-4 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">Memuat daftar kamera...</option>
                </select>
                <button type="button" id="btn-reload-cam" class="self-start text-xs font-bold text-emerald-400 hover:text-emerald-300 underline pt-1">
                    Muat Ulang Kamera
                </button>
            </div>

            <!-- Tampilan kamera biasa + overlay kotak deteksi -->
            <div id="video-wrap" class="relative w-full aspect-video rounded-2xl overflow-hidden bg-black shadow-inner border-2 border-zinc-800">
                <video id="webcam" autoplay playsinline muted class="w-full h-full object-cover transform -scale-x-100"></video>
                <canvas id="det-canvas" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>

                <!-- Lencana jumlah deteksi -->
                <div id="det-badge" class="absolute top-3 left-3 px-3 py-1.5 rounded-lg bg-black/55 text-[11px] font-bold text-white backdrop-blur-sm pointer-events-none">
                    Memuat deteksi...
                </div>
            </div>

            <!-- Pengaturan deteksi -->
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-bold text-zinc-300">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="chk-face" checked class="accent-emerald-500 w-4 h-4">
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500 mr-1"></span>Deteksi Wajah</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="chk-obj" checked class="accent-sky-400 w-4 h-4">
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-sky-400 mr-1"></span>Deteksi Objek</span>
                </label>
            </div>

            <p class="text-xs font-semibold text-emerald-400 text-center">Pastikan wajah terlihat jelas dan menghadap cahaya. Cukup sebutkan nama, instansi, nomor WhatsApp, keperluan, dan petugas yang dituju. Tidak perlu menyebutkan email.</p>

            <!-- Panel diagnosa kamera -->
            <div id="cam-diag" class="text-[11px] font-mono text-zinc-400 bg-zinc-950 border border-zinc-800 rounded-xl p-3 space-y-0.5 break-words"></div>
        </div>

        <!-- Sisi Kanan: Panel Kontrol -->
        <div class="xl:col-span-1 bg-white dark:bg-emerald-900 border border-slate-200 dark:border-emerald-800 p-8 rounded-3xl shadow-sm flex flex-col justify-between space-y-6 transition-colors">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-emerald-50 mb-2">Panel Kontrol AI</h3>
                <p class="text-xs text-slate-500 dark:text-emerald-200/70 mb-6">Kendalikan perekaman secara manual. Pastikan wajah Anda terekam oleh garis pemindai untuk validasi keamanan.</p>

                <div id="status-box" class="p-6 bg-slate-50 border border-slate-200 rounded-2xl text-center space-y-2 transition-colors duration-300">
                    <p id="status-text" class="text-xs font-bold text-slate-600 uppercase tracking-widest">Status: Siap Merekam</p>
                    <div id="timer" class="text-4xl font-black text-slate-400 font-mono">00:00</div>
                </div>
            </div>

            <div class="space-y-3" id="control-panel">
                <button id="btn-start" onclick="mulaiPerekaman()" class="w-full py-4 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-lg shadow-emerald-600/25 transition flex items-center justify-center space-x-2 disabled:opacity-70">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <span>Mulai Perekaman</span>
                </button>

                <div id="recording-controls" class="hidden grid grid-cols-2 gap-3">
                    <button id="btn-pause" onclick="jedaLanjutPerekaman()" class="py-4 px-4 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-2xl shadow-lg transition flex items-center justify-center space-x-2">
                        <svg id="icon-pause" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        <span id="text-pause">Jeda</span>
                    </button>

                    <button id="btn-stop" onclick="berhentiPerekaman()" class="py-4 px-4 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-2xl shadow-lg transition flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                        <span>Berhenti</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Analisis AI + Formulir Konfirmasi -->
    <div id="result-box" class="hidden bg-white dark:bg-emerald-900 border border-emerald-200 dark:border-emerald-700 p-8 rounded-3xl shadow-xl space-y-6">
        <h3 class="text-lg font-black text-emerald-900 dark:text-emerald-400 flex items-center gap-2">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Konfirmasi Data Kunjungan
        </h3>

        <div id="result-content" class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm"></div>

        <div id="form-konfirmasi" class="hidden space-y-4">
            <p id="info-kategori" class="text-sm font-black text-emerald-700 dark:text-emerald-300"></p>
            <input type="hidden" id="f-token">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <label class="block">
                    <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Nama tamu *</span>
                    <input id="f-nama" type="text" maxlength="100" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3">
                </label>
                <label class="block">
                    <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">No. WhatsApp *</span>
                    <input id="f-wa" type="text" inputmode="tel" maxlength="25" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3">
                </label>
                <label class="block">
                    <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Jenis tamu *</span>
                    <select id="f-jenis" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3">
                        <option value="">- Pilih jenis tamu -</option>
                        <option value="Penyedia">Penyedia</option>
                        <option value="Non-Penyedia">Non-Penyedia</option>
                    </select>
                </label>
                <label class="block">
                    <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Nama instansi</span>
                    <input id="f-instansi" type="text" maxlength="100" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3">
                </label>
                <label class="block">
                    <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Layanan *</span>
                    <select id="f-layanan" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3"></select>
                </label>
                <label class="block">
                    <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Petugas yang ditemui *</span>
                    <select id="f-petugas" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3"></select>
                </label>
                <label class="block md:col-span-2">
                    <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Keperluan kunjungan</span>
                    <textarea id="f-keperluan" rows="2" maxlength="2000" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3"></textarea>
                </label>

                <!-- Isian opsional: boleh dikosongkan -->
                <details class="md:col-span-2 rounded-xl border border-slate-200 dark:border-emerald-700 p-3">
                    <summary class="cursor-pointer text-xs font-bold text-slate-600 dark:text-emerald-200">Data tambahan (opsional, boleh dikosongkan)</summary>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <label class="block md:col-span-2">
                            <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Gmail / email tamu (bila kosong, dibuatkan otomatis)</span>
                            <input id="f-gmail" type="email" maxlength="255" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3 read-only:opacity-60">
                        </label>
                        <label class="block">
                            <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Hadir sebagai</span>
                            <input id="f-hadir" type="text" maxlength="50" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3">
                        </label>
                        <label class="block">
                            <span class="block text-xs font-bold text-slate-600 dark:text-emerald-200 mb-1">Alamat kantor</span>
                            <textarea id="f-alamat" rows="1" class="w-full border border-slate-200 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 rounded-xl p-3"></textarea>
                        </label>
                    </div>
                </details>
            </div>

            <button type="button" id="btn-simpan" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl disabled:opacity-60">Konfirmasi &amp; Simpan Kunjungan</button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const MAX_SECONDS = 60;
    const AUDIO_CONSTRAINTS = { echoCancellation: true, noiseSuppression: true, autoGainControl: true, channelCount: 1 };
    const CAMERA_KEYWORDS = ['logitech', 'c270', 'c505', '046d'];

    // Alamat endpoint (nama route mengikuti grup petugas.ai_scanner)
    const URL_PROCESS    = "{{ route('petugas.ai_scanner.process') }}";
    const URL_KONFIRMASI = "{{ route('petugas.ai_scanner.konfirmasi') }}";

    // Pustaka deteksi (dimuat setelah kamera tampil, kegagalannya tidak mengganggu kamera)
    const LIB_TFJS   = 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.22.0/dist/tf.min.js';
    const LIB_COCO   = 'https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js';
    // Beberapa sumber BlazeFace dicoba berurutan sampai ada yang berhasil
    const LIB_FACE_CANDIDATES = [
        'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface',
        'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js',
        'https://unpkg.com/@tensorflow-models/blazeface',
        'https://unpkg.com/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js',
    ];
    const DETECT_INTERVAL_MS = 120;   // jeda antar deteksi
    const TRACK_TTL_MS = 700;         // kotak hilang bila tidak terlihat selama ini
    const COLOR_FACE = '#10b981';
    const COLOR_OBJ  = '#38bdf8';

    const LABEL_ID = {
        'person': 'orang', 'chair': 'kursi', 'bottle': 'botol', 'cup': 'cangkir', 'cell phone': 'ponsel',
        'laptop': 'laptop', 'tv': 'tv', 'book': 'buku', 'keyboard': 'keyboard', 'mouse': 'mouse',
        'backpack': 'tas ransel', 'handbag': 'tas', 'clock': 'jam', 'couch': 'sofa', 'bed': 'tempat tidur',
        'potted plant': 'tanaman', 'dining table': 'meja', 'umbrella': 'payung', 'scissors': 'gunting',
        'vase': 'vas', 'remote': 'remote', 'bowl': 'mangkuk', 'bench': 'bangku', 'bird': 'burung',
        'cat': 'kucing', 'dog': 'anjing', 'car': 'mobil', 'motorcycle': 'motor', 'bicycle': 'sepeda',
        'tie': 'dasi', 'suitcase': 'koper', 'toothbrush': 'sikat gigi', 'teddy bear': 'boneka'
    };

    let mediaRecorder = null;
    let recordedChunks = [];
    let timerInterval = null;
    let secondsElapsed = 0;
    let localStream = null;
    let kameraSiap = false;

    const $ = (id) => document.getElementById(id);
    const videoElement      = $('webcam');
    const cameraSelect      = $('camera-select');
    const btnStart          = $('btn-start');
    const recordingControls = $('recording-controls');
    const btnPause          = $('btn-pause');
    const textPause         = $('text-pause');
    const iconPause         = $('icon-pause');
    const statusText        = $('status-text');
    const timerDisplay      = $('timer');
    const statusBox         = $('status-box');
    const camDiag           = $('cam-diag');
    const overlay           = $('det-canvas');
    const detBadge          = $('det-badge');
    const chkFace           = $('chk-face');
    const chkObj            = $('chk-obj');

    const ICON_PLAY  = '<path d="M8 5v14l11-7z"/>';
    const ICON_PAUSE = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
    const PAUSE_BTN_CLASS  = "py-4 px-4 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-2xl shadow-lg transition flex items-center justify-center space-x-2";
    const RESUME_BTN_CLASS = "py-4 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg transition flex items-center justify-center space-x-2";

    const START_BTN_HTML = (label) =>
        `<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg><span>${label}</span>`;
    const SPINNER_HTML = (label) =>
        `<svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>${label}</span>`;

    // ---------- Tema status ----------
    const THEMES = {
        idle:  { box: 'bg-slate-50 border-slate-200',     text: 'text-slate-600',   timer: 'text-slate-400' },
        rec:   { box: 'bg-red-50 border-red-200',         text: 'text-red-600',     timer: 'text-red-600' },
        pause: { box: 'bg-amber-50 border-amber-200',     text: 'text-amber-600',   timer: 'text-amber-600' },
        busy:  { box: 'bg-blue-50 border-blue-200',       text: 'text-blue-700',    timer: 'text-blue-600' },
        ok:    { box: 'bg-emerald-50 border-emerald-200', text: 'text-emerald-700', timer: 'text-emerald-600' },
        fail:  { box: 'bg-rose-50 border-rose-200',       text: 'text-rose-700',    timer: 'text-rose-500' },
    };

    function setStatus(theme, message, pulse) {
        const t = THEMES[theme];
        statusBox.className = `p-6 border rounded-2xl text-center space-y-2 transition-colors duration-300 ${t.box}`;
        statusText.className = `text-xs font-bold uppercase tracking-widest ${t.text} ${pulse ? 'animate-pulse' : ''}`;
        timerDisplay.className = `text-4xl font-black font-mono ${t.timer}`;
        statusText.innerText = message;
    }

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    // ---------- Diagnosa kamera ----------
    const diag = { pesan: [], error: null, devices: [], deteksi: 'belum dimulai' };

    function renderDiag() {
        if (!camDiag) return;
        const aman = window.isSecureContext ? 'ya' : 'TIDAK (kamera akan diblokir browser)';
        const dev = diag.devices.length
            ? diag.devices.map(d => `${esc(d.kind)}: ${esc(d.label || '(tanpa label)')}`).join('<br>&nbsp;&nbsp;')
            : '(belum ada)';
        camDiag.innerHTML =
            `<div>Alamat halaman: ${esc(location.origin)} | konteks aman: ${esc(aman)}</div>` +
            `<div>Perangkat terdeteksi:<br>&nbsp;&nbsp;${dev}</div>` +
            (diag.pesan.length ? `<div class="text-emerald-400">${diag.pesan.map(esc).join(' &rarr; ')}</div>` : '') +
            `<div class="text-sky-400">Deteksi AI: ${esc(diag.deteksi)}</div>` +
            (diag.error ? `<div class="text-rose-400">ERROR: ${esc(diag.error)}</div>` : '');
    }

    function catat(pesan) {
        diag.pesan.push(pesan);
        if (diag.pesan.length > 6) diag.pesan.shift();
        renderDiag();
    }

    function catatError(error, konteks) {
        const nama = (error && error.name) ? error.name : 'Error';
        const penjelasan = {
            NotAllowedError: 'Izin kamera/mikrofon ditolak. Klik ikon di address bar, pilih Allow, lalu muat ulang. Cek juga Windows Settings > Privacy > Camera.',
            PermissionDeniedError: 'Izin kamera/mikrofon ditolak.',
            NotFoundError: 'Kamera tidak ditemukan. Periksa kabel/port USB.',
            DevicesNotFoundError: 'Kamera tidak ditemukan. Periksa kabel/port USB.',
            NotReadableError: 'Kamera sedang dipakai aplikasi/tab lain (tab sandbox, Camera Windows, Zoom, OBS). Tutup lalu klik Muat Ulang Kamera.',
            TrackStartError: 'Kamera sedang dipakai aplikasi/tab lain.',
            OverconstrainedError: 'Pengaturan resolusi tidak didukung kamera.',
            SecurityError: 'Diblokir kebijakan keamanan (HTTPS atau header Permissions-Policy).',
        }[nama] || (error && error.message) || 'Kesalahan tidak diketahui.';
        diag.error = `[${konteks}] ${nama}: ${penjelasan}`;
        console.error(konteks, error);
        renderDiag();
    }

    // Tampilkan error JavaScript apa pun di panel diagnosa
    window.addEventListener('error', (e) => {
        diag.error = 'Error JavaScript: ' + e.message + (e.filename ? ' (' + e.filename.split('/').pop() + ':' + e.lineno + ')' : '');
        renderDiag();
    });
    window.addEventListener('unhandledrejection', (e) => {
        diag.error = 'Error tak tertangani: ' + ((e.reason && (e.reason.message || e.reason.name)) || e.reason);
        renderDiag();
    });

    function cocokKamera(label) {
        const l = (label || '').toLowerCase();
        return CAMERA_KEYWORDS.some(k => l.includes(k));
    }

    // =====================================================================
    // DETEKSI OBJEK & WAJAH (real-time, kotak mengikuti posisi)
    // =====================================================================
    let tracks = [];           // kotak yang sedang ditampilkan
    let cocoModel = null;
    let faceModel = null;      // { tipe: 'blazeface' | 'native', model }
    let detectorsStarted = false;
    let detectBusy = false;
    let lastDetectAt = 0;

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = () => reject(new Error('Gagal memuat ' + new URL(src).host + '/' + src.split('/').slice(-1)[0]));
            document.head.appendChild(s);
        });
    }

    // Mencoba beberapa sumber BlazeFace; terakhir pakai FaceDetector bawaan browser
    async function muatModelWajah() {
        const galat = [];
        for (const src of LIB_FACE_CANDIDATES) {
            try {
                await loadScript(src);
                if (window.blazeface && typeof blazeface.load === 'function') {
                    const model = await blazeface.load();
                    catat('model wajah: BlazeFace dari ' + new URL(src).host);
                    return { tipe: 'blazeface', model };
                }
                galat.push('objek blazeface tidak ditemukan di ' + new URL(src).host);
            } catch (e) {
                galat.push(e.message);
            }
        }

        if ('FaceDetector' in window) {
            try {
                const model = new FaceDetector({ fastMode: true, maxDetectedFaces: 5 });
                catat('model wajah: FaceDetector bawaan browser');
                return { tipe: 'native', model };
            } catch (e) {
                galat.push('FaceDetector: ' + e.message);
            }
        } else {
            galat.push('FaceDetector bawaan browser tidak tersedia');
        }

        throw new Error(galat.join(' | '));
    }

    // Menyeragamkan hasil deteksi wajah dari kedua jenis detektor
    async function deteksiWajah() {
        if (!faceModel) return [];
        if (faceModel.tipe === 'blazeface') {
            const hasil = await faceModel.model.estimateFaces(videoElement, false);
            return hasil.map(f => ({
                label: 'wajah',
                skor: Array.isArray(f.probability) ? f.probability[0] : f.probability,
                x: f.topLeft[0], y: f.topLeft[1],
                w: f.bottomRight[0] - f.topLeft[0], h: f.bottomRight[1] - f.topLeft[1],
            }));
        }
        const hasil = await faceModel.model.detect(videoElement);
        return hasil.map(f => ({
            label: 'wajah', skor: 0.9,
            x: f.boundingBox.x, y: f.boundingBox.y, w: f.boundingBox.width, h: f.boundingBox.height,
        }));
    }

    async function mulaiDetektor() {
        if (detectorsStarted) return;
        detectorsStarted = true;
        try {
            diag.deteksi = 'memuat TensorFlow.js...';
            renderDiag();
            await loadScript(LIB_TFJS);
            try { await tf.setBackend('webgl'); } catch (e) { /* pakai backend bawaan */ }
            await tf.ready();

            diag.deteksi = 'memuat model objek & wajah (pertama kali bisa beberapa detik)...';
            renderDiag();

            const tugas = [
                loadScript(LIB_COCO).then(() => cocoSsd.load({ base: 'lite_mobilenet_v2' }))
                    .then(m => { cocoModel = m; })
                    .catch(e => { catat('model objek gagal: ' + e.message); }),
                muatModelWajah()
                    .then(m => { faceModel = m; })
                    .catch(e => { catat('model wajah gagal: ' + e.message); }),
            ];
            await Promise.all(tugas);

            const siap = [cocoModel ? 'objek' : null, faceModel ? 'wajah' : null].filter(Boolean);
            diag.deteksi = siap.length ? 'aktif (' + siap.join(' + ') + ') | backend: ' + tf.getBackend()
                                       : 'tidak aktif (model gagal dimuat, kamera tetap berfungsi)';
            renderDiag();
            if (siap.length) jalankanLoopDeteksi();
        } catch (e) {
            diag.deteksi = 'gagal: ' + e.message + ' (kamera tetap berfungsi)';
            detBadge.textContent = 'Deteksi tidak aktif';
            renderDiag();
        }
    }

    // Memperbarui daftar kotak dengan hasil deteksi terbaru (pencocokan terdekat agar bergerak mulus)
    function perbaruiTrack(jenis, deteksi) {
        const sekarang = performance.now();
        const lama = tracks.filter(t => t.jenis === jenis);
        const terpakai = new Set();

        deteksi.forEach(d => {
            let terbaik = null, jarakTerbaik = Infinity;
            lama.forEach(t => {
                if (terpakai.has(t) || t.label !== d.label) return;
                const jarak = Math.hypot((t.tx + t.tw / 2) - (d.x + d.w / 2), (t.ty + t.th / 2) - (d.y + d.h / 2));
                if (jarak < jarakTerbaik && jarak < Math.max(t.tw, t.th) * 1.2) {
                    jarakTerbaik = jarak;
                    terbaik = t;
                }
            });
            if (terbaik) {
                terpakai.add(terbaik);
                Object.assign(terbaik, { tx: d.x, ty: d.y, tw: d.w, th: d.h, skor: d.skor, terakhir: sekarang });
            } else {
                tracks.push({
                    jenis, label: d.label, skor: d.skor,
                    x: d.x, y: d.y, w: d.w, h: d.h,
                    tx: d.x, ty: d.y, tw: d.w, th: d.h,
                    terakhir: sekarang,
                });
            }
        });

        tracks = tracks.filter(t => t.jenis !== jenis || (sekarang - t.terakhir) < TRACK_TTL_MS);
    }

    async function deteksiSekali() {
        if (detectBusy || videoElement.readyState < 2 || !videoElement.videoWidth || document.hidden) return;
        detectBusy = true;
        try {
            if (faceModel && chkFace.checked) {
                perbaruiTrack('wajah', await deteksiWajah());
            } else {
                tracks = tracks.filter(t => t.jenis !== 'wajah');
            }

            if (cocoModel && chkObj.checked) {
                const hasil = await cocoModel.detect(videoElement, 20, 0.5);
                perbaruiTrack('objek', hasil.map(o => ({
                    label: o.class,
                    skor: o.score,
                    x: o.bbox[0], y: o.bbox[1], w: o.bbox[2], h: o.bbox[3],
                })));
            } else {
                tracks = tracks.filter(t => t.jenis !== 'objek');
            }
        } catch (e) {
            diag.deteksi = 'error saat mendeteksi: ' + e.message;
            renderDiag();
        } finally {
            detectBusy = false;
        }
    }

    function jalankanLoopDeteksi() {
        const loop = async () => {
            const sekarang = performance.now();
            if (sekarang - lastDetectAt >= DETECT_INTERVAL_MS) {
                lastDetectAt = sekarang;
                await deteksiSekali();
            }
            setTimeout(loop, 30);
        };
        loop();
        requestAnimationFrame(gambarOverlay);
    }

    function kotakBulat(ctx, x, y, w, h, r) {
        r = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    // Menggambar kotak pada kanvas, dengan pemetaan object-cover dan kamera cermin
    function gambarOverlay() {
        requestAnimationFrame(gambarOverlay);

        const cw = overlay.clientWidth, ch = overlay.clientHeight;
        const dpr = window.devicePixelRatio || 1;
        if (overlay.width !== Math.round(cw * dpr) || overlay.height !== Math.round(ch * dpr)) {
            overlay.width = Math.round(cw * dpr);
            overlay.height = Math.round(ch * dpr);
        }
        const ctx = overlay.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, cw, ch);

        const vw = videoElement.videoWidth, vh = videoElement.videoHeight;
        if (!vw || !vh) return;

        const skala = Math.max(cw / vw, ch / vh);
        const ox = (cw - vw * skala) / 2;
        const oy = (ch - vh * skala) / 2;
        const sekarang = performance.now();

        let jumlahWajah = 0, jumlahObjek = 0;

        tracks.forEach(t => {
            // geser halus menuju posisi terbaru
            t.x += (t.tx - t.x) * 0.35;
            t.y += (t.ty - t.y) * 0.35;
            t.w += (t.tw - t.w) * 0.35;
            t.h += (t.th - t.h) * 0.35;

            const umur = sekarang - t.terakhir;
            const alfa = Math.max(0, Math.min(1, 1 - (umur - 250) / (TRACK_TTL_MS - 250)));
            if (alfa <= 0) return;

            const w = t.w * skala, h = t.h * skala;
            let x = t.x * skala + ox;
            const y = t.y * skala + oy;
            x = cw - x - w; // video ditampilkan cermin

            const warna = t.jenis === 'wajah' ? COLOR_FACE : COLOR_OBJ;
            const nama = t.jenis === 'wajah' ? 'Wajah' : (LABEL_ID[t.label] || t.label);
            const teks = `${nama} ${Math.round((t.skor || 0) * 100)}%`;

            ctx.globalAlpha = alfa;
            ctx.lineWidth = 3;
            ctx.strokeStyle = warna;
            kotakBulat(ctx, x, y, w, h, 8);
            ctx.stroke();

            ctx.font = '700 12px "Plus Jakarta Sans", system-ui, sans-serif';
            const lebarTeks = ctx.measureText(teks).width + 14;
            const tinggiLabel = 20;
            const ly = y - tinggiLabel >= 0 ? y - tinggiLabel : y;
            ctx.fillStyle = warna;
            kotakBulat(ctx, x, ly, lebarTeks, tinggiLabel, 5);
            ctx.fill();
            ctx.fillStyle = '#04120c';
            ctx.textBaseline = 'middle';
            ctx.fillText(teks, x + 7, ly + tinggiLabel / 2 + 0.5);
            ctx.globalAlpha = 1;

            if (t.jenis === 'wajah') jumlahWajah++; else jumlahObjek++;
        });

        const aktifWajah = faceModel && chkFace.checked;
        const aktifObjek = cocoModel && chkObj.checked;
        detBadge.textContent = (aktifWajah || aktifObjek)
            ? `Wajah: ${jumlahWajah} | Objek: ${jumlahObjek}`
            : 'Deteksi nonaktif';
    }

    // =====================================================================
    // KAMERA
    // =====================================================================
    async function getCameras() {
        kameraSiap = false;
        diag.error = null;
        diag.pesan = [];
        renderDiag();

        // Pengawas: bila 6 detik belum tampil, beri petunjuk
        setTimeout(() => {
            if (!kameraSiap && !diag.error) {
                diag.error = 'Kamera belum merespons setelah 6 detik. Kemungkinan kamera sedang dipakai tab/aplikasi lain (tab sandbox, Camera Windows, Zoom, OBS), atau ada kotak izin yang menunggu di address bar. Tutup yang lain lalu klik Muat Ulang Kamera.';
                renderDiag();
            }
        }, 6000);

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            cameraSelect.innerHTML = '<option value="">Kamera diblokir (butuh HTTPS atau localhost)</option>';
            diag.error = 'navigator.mediaDevices tidak tersedia. Buka lewat http://localhost, http://127.0.0.1, atau HTTPS.';
            renderDiag();
            return;
        }

        // Lepas stream yang sedang aktif agar tidak bentrok saat dimuat ulang manual
        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        tracks = [];

        try {
            let tmp;
            try {
                tmp = await navigator.mediaDevices.getUserMedia({ video: true, audio: AUDIO_CONSTRAINTS });
                catat('izin kamera+mikrofon OK');
            } catch (e) {
                if (e.name === 'NotReadableError') throw e;
                tmp = await navigator.mediaDevices.getUserMedia({ video: true });
                catat('izin kamera OK (mikrofon gagal: ' + e.name + ')');
            }
            tmp.getTracks().forEach(t => t.stop()); // lepas stream sementara

            const devices = await navigator.mediaDevices.enumerateDevices();
            diag.devices = devices.filter(d => d.kind === 'videoinput' || d.kind === 'audioinput');
            const videoDevices = devices.filter(d => d.kind === 'videoinput');

            cameraSelect.innerHTML = '';
            let targetDeviceId = null;

            if (videoDevices.length === 0) {
                cameraSelect.innerHTML = '<option value="">Tidak ada perangkat kamera terdeteksi</option>';
                diag.error = 'Browser tidak melihat perangkat kamera sama sekali.';
                renderDiag();
                return;
            }

            videoDevices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Kamera ${index + 1}`;

                if (!targetDeviceId && cocokKamera(device.label)) {
                    option.selected = true;
                    targetDeviceId = device.deviceId;
                }
                cameraSelect.appendChild(option);
            });

            if (!targetDeviceId) {
                targetDeviceId = videoDevices[0].deviceId;
                cameraSelect.selectedIndex = 0;
            }

            renderDiag();
            await startStream(targetDeviceId);

        } catch (error) {
            cameraSelect.innerHTML = '<option value="">Gagal memuat perangkat kamera</option>';
            catatError(error, 'getCameras');
        }
    }

    async function startStream(deviceId) {
        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        tracks = [];

        const percobaan = [
            ['720p + mikrofon', {
                video: { deviceId: { exact: deviceId }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: AUDIO_CONSTRAINTS
            }],
            ['resolusi bawaan + mikrofon', { video: { deviceId: { exact: deviceId } }, audio: AUDIO_CONSTRAINTS }],
            ['video saja (tanpa suara)', { video: { deviceId: { exact: deviceId } }, audio: false }],
        ];

        let errorTerakhir = null;
        for (const [nama, konfigurasi] of percobaan) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia(konfigurasi);
                videoElement.srcObject = stream;
                localStream = stream;
                kameraSiap = true;
                diag.error = null;
                catat('kamera aktif: ' + nama);
                if (konfigurasi.audio === false) {
                    diag.error = 'Mikrofon tidak bisa diakses, rekaman akan tanpa suara. Periksa izin mikrofon dan pilih mikrofon webcam di pengaturan suara Windows.';
                    renderDiag();
                }
                mulaiDetektor(); // muat model deteksi sekali, tidak menghalangi kamera
                return;
            } catch (e) {
                errorTerakhir = e;
                catat('gagal (' + nama + '): ' + e.name);
                if (e.name === 'SecurityError') break;
            }
        }
        catatError(errorTerakhir, 'startStream');
    }

    cameraSelect.addEventListener('change', function () {
        if (this.value) startStream(this.value);
    });
    $('btn-reload-cam').addEventListener('click', getCameras);

    window.addEventListener('beforeunload', () => {
        if (localStream) localStream.getTracks().forEach(t => t.stop());
    });

    // ---------- Stopwatch ----------
    function updateTimer() {
        const m = Math.floor(secondsElapsed / 60).toString().padStart(2, '0');
        const s = (secondsElapsed % 60).toString().padStart(2, '0');
        timerDisplay.innerText = `${m}:${s}`;
    }

    function startTimer() {
        clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            secondsElapsed++;
            updateTimer();
            if (secondsElapsed >= MAX_SECONDS) berhentiPerekaman();
        }, 1000);
    }

    // ---------- Aksi 1: Mulai ----------
    function mulaiPerekaman() {
        recordedChunks = [];
        if (!localStream) {
            alert("Webcam belum aktif! Lihat panel diagnosa di bawah video, atau klik Muat Ulang Kamera.");
            return;
        }

        const candidates = ['video/webm;codecs=vp8,opus', 'video/webm;codecs=vp9,opus', 'video/webm'];
        const mimeType = candidates.find(t => window.MediaRecorder && MediaRecorder.isTypeSupported(t));
        const options = { videoBitsPerSecond: 1500000, audioBitsPerSecond: 128000 };
        if (mimeType) options.mimeType = mimeType;

        try {
            mediaRecorder = new MediaRecorder(localStream, options);
        } catch (e) {
            mediaRecorder = new MediaRecorder(localStream);
        }

        mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) recordedChunks.push(event.data);
        };

        mediaRecorder.onstop = () => {
            kirimKeServer(new Blob(recordedChunks, { type: 'video/webm' }));
        };

        mediaRecorder.start();

        btnStart.classList.add('hidden');
        recordingControls.classList.remove('hidden');
        $('result-box').classList.add('hidden');
        $('form-konfirmasi').classList.add('hidden');

        setStatus('rec', 'SEDANG MEREKAM... SEBUTKAN NAMA, INSTANSI, NO. WA, KEPERLUAN, DAN PETUGAS!', true);

        secondsElapsed = 0;
        updateTimer();
        startTimer();
    }

    // ---------- Aksi 2: Jeda / Lanjut ----------
    function jedaLanjutPerekaman() {
        if (!mediaRecorder) return;

        if (mediaRecorder.state === 'recording') {
            mediaRecorder.pause();
            clearInterval(timerInterval);

            textPause.innerText = "Lanjut";
            iconPause.innerHTML = ICON_PLAY;
            btnPause.className = RESUME_BTN_CLASS;
            setStatus('pause', 'PEREKAMAN DIJEDA', false);

        } else if (mediaRecorder.state === 'paused') {
            mediaRecorder.resume();
            startTimer();

            textPause.innerText = "Jeda";
            iconPause.innerHTML = ICON_PAUSE;
            btnPause.className = PAUSE_BTN_CLASS;
            setStatus('rec', 'SEDANG MEREKAM... SEBUTKAN NAMA, INSTANSI, NO. WA, KEPERLUAN, DAN PETUGAS!', true);
        }
    }

    // ---------- Aksi 3: Berhenti & kirim ----------
    function berhentiPerekaman() {
        clearInterval(timerInterval);

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop(); // memicu onstop -> kirimKeServer
        }

        textPause.innerText = "Jeda";
        iconPause.innerHTML = ICON_PAUSE;
        btnPause.className = PAUSE_BTN_CLASS;

        recordingControls.classList.add('hidden');
        btnStart.classList.remove('hidden');
        btnStart.disabled = true;
        btnStart.innerHTML = SPINNER_HTML('Mengekstrak Vektor Wajah...');

        setStatus('busy', 'MENGEKSTRAKSI WAJAH & SUARA...', true);
    }

    // ---------- Tahap 1: kirim rekaman ke Laravel -> Python (analisis saja) ----------
    async function kirimKeServer(blob) {
        const formData = new FormData();
        formData.append('media_file', blob, 'perekaman_tamu.webm');

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const headers = { 'Accept': 'application/json', 'ngrok-skip-browser-warning': 'true' };
        if (csrfMeta) headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');

        try {
            const response = await fetch(URL_PROCESS, {
                method: 'POST',
                headers: headers,
                body: formData
            });

            const raw = await response.text();
            let data = null;
            try { data = JSON.parse(raw); } catch (e) { /* bukan JSON */ }

            btnStart.disabled = false;
            btnStart.innerHTML = START_BTN_HTML('Mulai Perekaman Baru');

            if (response.ok && data && data.status === 'success') {
                tampilkanHasil(data);
                return;
            }

            let pesan = (data && data.message) ? data.message : `Server membalas HTTP ${response.status}.`;
            if (data && data.errors) {
                pesan += '\n' + Object.values(data.errors).flat().join('\n');
            }
            if (data && data.detail) {
                pesan += '\n\nDetail: ' + data.detail;
            }
            if (!data) {
                console.error('Balasan non-JSON dari server:', raw);
                pesan += '\n(Lihat storage/logs/laravel.log untuk penyebab sebenarnya.)';
            }
            alert("Gagal memproses AI: " + pesan);
            resetUIFail();

        } catch (error) {
            btnStart.disabled = false;
            btnStart.innerHTML = START_BTN_HTML('Mulai Ulang Perekaman');
            alert("Tidak dapat menghubungi server: " + error);
            resetUIFail();
        }
    }

    // ---------- Tampilkan hasil AI sebagai formulir konfirmasi ----------
    function opsi(daftar, nilaiKey, labelKey, terpilih, placeholder) {
        return `<option value="">${esc(placeholder)}</option>` + daftar.map(o =>
            `<option value="${esc(String(o[nilaiKey]))}" ${String(o[nilaiKey]) === String(terpilih ?? '') ? 'selected' : ''}>${esc(o[labelKey])}</option>`
        ).join('');
    }

    function tampilkanHasil(res) {
        setStatus('ok', 'PERIKSA DATA, LENGKAPI YANG KOSONG, LALU SIMPAN', false);

        const d = res.data || {};
        const dbg = res.ai_debug || {};
        const lama = d.kategori === 'Tamu Lama';

        let peringatan = '';
        if (dbg.audio_ada === false || !dbg.transkrip) {
            peringatan += '<p class="md:col-span-3 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3">Suara tidak terdengar jelas. Isi data secara manual atau rekam ulang.</p>';
        }
        if (dbg.wajah_terdeteksi === false) {
            peringatan += '<p class="md:col-span-3 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">Wajah tidak terdeteksi, tamu diperlakukan sebagai tamu baru.</p>';
        }

        let infoPetugas = '';
        if (d.petugas_disebut) {
            infoPetugas = `<p>Petugas disebut: ${esc(d.petugas_disebut)} ${dbg.petugas_terdaftar ? '(cocok dengan master)' : '(tidak cocok, pilih manual)'}</p>`;
        }

        $('result-box').classList.remove('hidden');
        $('result-content').innerHTML = `
            ${peringatan}
            <div class="md:col-span-3 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1">
                <p><span class="font-bold text-slate-600">Yang didengar AI:</span> ${esc(dbg.transkrip || '(tidak ada)')}</p>
                <p>Ekstraksi: ${esc(dbg.sumber_ekstraksi || '-')}${dbg.gemini_error ? ' | Gemini: ' + esc(dbg.gemini_error) : ''}</p>
                <p class="font-bold text-slate-600">Wajah: skor terbaik ${esc(String(dbg.skor_kemiripan ?? '-'))} (ambang ${esc(String(dbg.ambang_wajah ?? '-'))}) | terdekat: ${esc(dbg.tamu_terdekat || 'belum ada')}${dbg.galeri_bertambah ? ' | galeri wajah bertambah' : ''}</p>
                ${infoPetugas}
            </div>
        `;

        $('info-kategori').textContent = lama
            ? 'TAMU LAMA dikenali lewat wajah. Pilih layanan dan petugas, periksa data lain.'
            : 'TAMU BARU. Lengkapi nama, No. WhatsApp, jenis tamu, layanan, dan petugas (email tidak wajib).';

        $('f-token').value = res.token || '';
        $('f-gmail').value = d.gmail || '';
        $('f-gmail').readOnly = lama;           // kunci utama tamu lama tidak boleh diubah
        $('f-nama').value = d.nama_tamu || '';
        $('f-wa').value = d.no_wa || '';
        $('f-jenis').value = d.jenis_tamu || '';
        $('f-instansi').value = d.nama_instansi || '';
        $('f-hadir').value = d.hadir_sebagai || '';
        $('f-alamat').value = d.alamat_kantor || '';
        $('f-keperluan').value = d.keperluan || '';
        $('f-layanan').innerHTML = opsi(res.daftar_layanan || [], 'id_layanan', 'nama', null, '- Pilih layanan -');
        $('f-petugas').innerHTML = opsi(res.daftar_petugas || [], 'id_petugas', 'nama_petugas', d.id_petugas, '- Pilih petugas yang ditemui -');

        $('form-konfirmasi').classList.remove('hidden');
        $('result-box').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ---------- Tahap 2: konfirmasi & simpan ----------
    async function simpanKonfirmasi() {
        const wajib = [
            ['f-nama', 'Nama tamu'],
            ['f-wa', 'No. WhatsApp'],
            ['f-jenis', 'Jenis tamu'],
            ['f-layanan', 'Layanan'],
            ['f-petugas', 'Petugas yang ditemui'],
        ];
        const kosong = wajib.filter(([id]) => !$(id).value.trim()).map(([, label]) => label);
        if (kosong.length) {
            alert('Lengkapi dulu isian berikut:\n- ' + kosong.join('\n- '));
            return;
        }

        const btn = $('btn-simpan');
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        try {
            const response = await fetch(URL_KONFIRMASI, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'ngrok-skip-browser-warning': 'true',
                    'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                },
                body: JSON.stringify({
                    token: $('f-token').value,
                    gmail: $('f-gmail').value.trim() || null,
                    nama_tamu: $('f-nama').value.trim(),
                    no_wa: $('f-wa').value.trim(),
                    jenis_tamu: $('f-jenis').value,
                    nama_instansi: $('f-instansi').value.trim() || null,
                    hadir_sebagai: $('f-hadir').value.trim() || null,
                    alamat_kantor: $('f-alamat').value.trim() || null,
                    id_layanan: $('f-layanan').value,
                    id_petugas: $('f-petugas').value,
                    keperluan: $('f-keperluan').value.trim() || null,
                }),
            });

            const data = await response.json().catch(() => null);

            if (response.ok && data && data.status === 'success') {
                alert('Kunjungan tersimpan.' + (data.gmail_sementara ? '\nEmail tamu dibuatkan otomatis (sementara).' : ''));
                $('form-konfirmasi').classList.add('hidden');
                $('result-box').classList.add('hidden');
                setStatus('idle', 'Status: Siap Merekam', false);
                timerDisplay.innerText = '00:00';
                btnStart.innerHTML = START_BTN_HTML('Mulai Perekaman');
                return;
            }

            let pesan = (data && data.message) ? data.message : `Server membalas HTTP ${response.status}.`;
            if (data && data.errors) pesan += '\n' + Object.values(data.errors).flat().join('\n');
            if (data && data.detail) pesan += '\n\nDetail: ' + data.detail;
            alert(pesan);
        } catch (e) {
            alert('Tidak dapat menghubungi server: ' + e);
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Konfirmasi &amp; Simpan Kunjungan';
        }
    }

    $('btn-simpan').addEventListener('click', simpanKonfirmasi);

    function resetUIFail() {
        setStatus('fail', 'GAGAL DIPROSES', false);
        timerDisplay.innerText = '00:00';
    }

    // Ekspos fungsi untuk atribut onclick di HTML
    window.mulaiPerekaman = mulaiPerekaman;
    window.jedaLanjutPerekaman = jedaLanjutPerekaman;
    window.berhentiPerekaman = berhentiPerekaman;
    window.getCameras = getCameras;

    renderDiag();
    getCameras();
})();
</script>
@endpush