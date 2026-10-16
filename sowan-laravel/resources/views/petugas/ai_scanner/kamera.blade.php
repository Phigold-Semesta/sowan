@extends('layouts.app')

@section('title', 'Kamera AI Scanner')

@section('content')
<div class="max-w-7xl mx-auto space-y-8 px-4">

    <!-- Header Judul -->
    <div class="bg-white dark:bg-emerald-900 border border-slate-200 dark:border-emerald-800 px-8 py-6 rounded-3xl shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4 transition-colors">
        <div>
            <h1 class="text-2xl font-black italic tracking-tight text-slate-900 dark:text-emerald-50">KAMERA AI DETECTOR</h1>
            <p class="text-sm font-medium text-slate-500 dark:text-emerald-200/70 mt-0.5">Pindai wajah (YOLO-style) & suarakan keperluan Anda (Whisper AI).</p>
        </div>
        <div class="flex items-center space-x-2 bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-700 px-4 py-2 rounded-full text-xs font-bold text-emerald-700 dark:text-emerald-400">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
            <span>Webcam Controller Ready</span>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

        <!-- Sisi Kiri: Viewport Webcam -->
        <div class="xl:col-span-2 bg-zinc-900 border border-zinc-800 p-6 rounded-3xl shadow-2xl flex flex-col space-y-4">

            <!-- Pilihan Kamera -->
            <div class="flex flex-col space-y-1">
                <label class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Pilih Perangkat Kamera:</label>
                <select id="camera-select" class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-xl text-white text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors shadow-inner appearance-none cursor-pointer">
                    <option value="">Memuat daftar kamera...</option>
                </select>
            </div>

            <!-- Area Video & YOLO Bounding Box -->
            <div class="relative w-full aspect-video rounded-2xl overflow-hidden bg-black shadow-inner border-2 border-zinc-800">
                <video id="webcam" autoplay playsinline muted class="w-full h-full object-cover transform -scale-x-100"></video>

                <!-- YOLO Style Bounding Box Overlay -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <!-- Kotak Pembatas Utama -->
                    <div class="relative w-56 h-72 sm:w-64 sm:h-80 border-4 border-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                        <!-- Label ala YOLO -->
                        <div class="absolute -top-[26px] -left-1 bg-emerald-500 text-black font-black text-xs px-3 py-1 uppercase tracking-widest flex items-center gap-2 shadow-sm">
                            <span class="w-1.5 h-1.5 bg-black rounded-full animate-pulse"></span>
                            person
                        </div>
                        
                        <!-- Garis Bidik Tengah -->
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-30 text-emerald-500">
                            <i class="fas fa-crosshairs text-4xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs font-semibold text-emerald-400 text-center">Pastikan wajah berada di dalam kotak hijau <span class="font-black">"person"</span>, menghadap cahaya, lalu berbicaralah dengan jelas saat merekam.</p>
        </div>

        <!-- Sisi Kanan: Panel Kontrol -->
        <div class="xl:col-span-1 bg-white dark:bg-emerald-900 border border-slate-200 dark:border-emerald-800 p-8 rounded-3xl shadow-sm flex flex-col justify-between space-y-6 transition-colors">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-emerald-50 mb-2">Panel Kontrol AI</h3>
                <p class="text-xs text-slate-500 dark:text-emerald-200/70 mb-6">Kendalikan perekaman manual. Pastikan izin kamera dan mikrofon (SSL/HTTPS) aktif.</p>

                <div id="status-box" class="p-6 bg-slate-50 dark:bg-emerald-950/50 border border-slate-200 dark:border-emerald-800 rounded-2xl text-center space-y-2 transition-colors duration-300">
                    <p id="status-text" class="text-xs font-bold text-slate-600 dark:text-emerald-400 uppercase tracking-widest">Status: Siap Merekam</p>
                    <div id="timer" class="text-4xl font-black text-slate-400 dark:text-emerald-600/50 font-mono">00:00</div>
                </div>
            </div>

            <div class="space-y-3" id="control-panel">
                <button id="btn-start" onclick="mulaiPerekaman()" class="w-full py-4 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-[0_8px_20px_rgba(16,185,129,0.3)] transition-all flex items-center justify-center space-x-2 disabled:opacity-70 active:scale-95">
                    <i class="fas fa-play w-5 text-center"></i>
                    <span class="tracking-wide">Mulai Perekaman</span>
                </button>

                <div id="recording-controls" class="hidden grid grid-cols-2 gap-3">
                    <button id="btn-pause" onclick="jedaLanjutPerekaman()" class="py-4 px-4 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-2xl shadow-[0_8px_20px_rgba(245,158,11,0.3)] transition-all flex items-center justify-center space-x-2 active:scale-95">
                        <i id="icon-pause" class="fas fa-pause w-5 text-center"></i>
                        <span id="text-pause" class="tracking-wide">Jeda</span>
                    </button>

                    <button id="btn-stop" onclick="berhentiPerekaman()" class="py-4 px-4 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-2xl shadow-[0_8px_20px_rgba(225,29,72,0.3)] transition-all flex items-center justify-center space-x-2 active:scale-95">
                        <i class="fas fa-stop w-5 text-center"></i>
                        <span class="tracking-wide">Berhenti</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Analisis AI -->
    <div id="result-box" class="hidden bg-white dark:bg-emerald-900 border border-emerald-200 dark:border-emerald-700 p-8 rounded-3xl shadow-xl space-y-6 transition-colors mt-8">
        <h3 class="text-lg font-black text-emerald-900 dark:text-emerald-400 flex items-center gap-2">
            <i class="fas fa-robot text-emerald-600 dark:text-emerald-400"></i>
            Hasil Identifikasi YOLO & Whisper
        </h3>
        <div id="result-content" class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm"></div>
    </div>

</div>
@endsection

@push('scripts')
<!-- Masukkan SweetAlert2 jika belum ada di layout utama (Hanya untuk jaga-jaga) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const MAX_SECONDS = 60;

    const AUDIO_CONSTRAINTS = {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
        channelCount: 1,
    };

    let mediaRecorder = null;
    let recordedChunks = [];
    let timerInterval = null;
    let secondsElapsed = 0;

    const videoElement      = document.getElementById('webcam');
    const cameraSelect      = document.getElementById('camera-select');
    const btnStart          = document.getElementById('btn-start');
    const recordingControls = document.getElementById('recording-controls');
    const btnPause          = document.getElementById('btn-pause');
    const textPause         = document.getElementById('text-pause');
    const iconPause         = document.getElementById('icon-pause');
    const statusText        = document.getElementById('status-text');
    const timerDisplay      = document.getElementById('timer');
    const statusBox         = document.getElementById('status-box');

    const CLASS_PAUSE_BTN   = "py-4 px-4 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-2xl shadow-[0_8px_20px_rgba(245,158,11,0.3)] transition-all flex items-center justify-center space-x-2 active:scale-95";
    const CLASS_RESUME_BTN  = "py-4 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-[0_8px_20px_rgba(37,99,235,0.3)] transition-all flex items-center justify-center space-x-2 active:scale-95";

    const START_BTN_HTML = (label) =>
        `<i class="fas fa-play w-5 text-center"></i><span class="tracking-wide">${label}</span>`;
    const SPINNER_HTML = (label) =>
        `<i class="fas fa-spinner fa-spin w-5 text-center"></i><span class="tracking-wide">${label}</span>`;

    const THEMES = {
        idle:  { box: 'bg-slate-50 dark:bg-emerald-950/50 border-slate-200 dark:border-emerald-800',     text: 'text-slate-600 dark:text-emerald-400',   timer: 'text-slate-400 dark:text-emerald-600/50' },
        rec:   { box: 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900/50',             text: 'text-red-600 dark:text-red-400',         timer: 'text-red-600 dark:text-red-500' },
        pause: { box: 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900/50',     text: 'text-amber-600 dark:text-amber-400',     timer: 'text-amber-600 dark:text-amber-500' },
        busy:  { box: 'bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900/50',         text: 'text-blue-700 dark:text-blue-400',       timer: 'text-blue-600 dark:text-blue-500' },
        ok:    { box: 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800',text: 'text-emerald-700 dark:text-emerald-400', timer: 'text-emerald-600 dark:text-emerald-500' },
        fail:  { box: 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-900/50',         text: 'text-rose-700 dark:text-rose-400',       timer: 'text-rose-500 dark:text-rose-600' },
    };

    function setStatus(theme, message, pulse = false) {
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

    // ==========================================
    // LOGIKA PERANGKAT KAMERA
    // ==========================================
    async function getCameras() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            Swal.fire({ 
                icon: 'error', 
                title: 'Akses Kamera Diblokir', 
                text: "Browser memblokir kamera. Gunakan HTTPS atau akses via http://localhost / http://127.0.0.1" 
            });
            cameraSelect.innerHTML = '<option value="">Kamera diblokir (Butuh HTTPS/Localhost)</option>';
            return;
        }

        try {
            const tmp = await navigator.mediaDevices.getUserMedia({ video: true, audio: AUDIO_CONSTRAINTS });
            tmp.getTracks().forEach(t => t.stop()); 

            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(d => d.kind === 'videoinput');

            cameraSelect.innerHTML = '';
            let targetDeviceId = null;

            if (videoDevices.length === 0) {
                 cameraSelect.innerHTML = '<option value="">Tidak ada perangkat kamera terdeteksi</option>';
                 return;
            }

            videoDevices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Kamera ${index + 1}`;

                // PENYESUAIAN JENIUS: Cari Logitech ATAU C505e
                const label = (device.label || '').toLowerCase();
                if (label.includes('logitech') || label.includes('c505e')) {
                    option.selected = true;
                    targetDeviceId = device.deviceId;
                }
                cameraSelect.appendChild(option);
            });

            if (!targetDeviceId && videoDevices.length > 0) {
                targetDeviceId = videoDevices[0].deviceId;
                cameraSelect.selectedIndex = 0;
            }

            if (targetDeviceId) {
                await startStream(targetDeviceId);
            }

        } catch (error) {
            console.error("Camera Error: ", error);
            cameraSelect.innerHTML = '<option value="">Gagal memuat perangkat kamera</option>';
            
            let msg = error.message;
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                msg = "Izin akses kamera/mikrofon ditolak. Klik ikon gembok di sebelah URL browser, lalu izinkan (allow) akses.";
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                msg = "Kamera tidak terdeteksi. Pastikan kabel USB kamera terpasang dengan benar.";
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                msg = "Kamera sedang digunakan oleh aplikasi lain (Zoom, OBS, dll). Tutup aplikasi tersebut terlebih dahulu.";
            }

            Swal.fire({ icon: 'error', title: 'Kamera Gagal Dimuat', text: msg });
        }
    }

    async function startStream(deviceId) {
        if (window.localStream) {
            window.localStream.getTracks().forEach(track => track.stop());
        }
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    deviceId: { exact: deviceId }, 
                    width: { ideal: 1280 }, 
                    height: { ideal: 720 } 
                },
                audio: AUDIO_CONSTRAINTS
            });
            videoElement.srcObject = stream;
            window.localStream = stream;
        } catch (error) {
            console.warn("Resolusi 720p ditolak, beralih ke resolusi bawaan kamera.", error);
            try {
                const fallbackStream = await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: { exact: deviceId } }, 
                    audio: AUDIO_CONSTRAINTS
                });
                videoElement.srcObject = fallbackStream;
                window.localStream = fallbackStream;
            } catch (fallbackError) {
                Swal.fire({ icon: 'error', title: 'Gagal Menampilkan Video', text: fallbackError.message });
                cameraSelect.innerHTML = '<option value="">Kamera tidak merespons</option>';
            }
        }
    }

    cameraSelect.addEventListener('change', function () { 
        if(this.value) startStream(this.value); 
    });
    
    getCameras();

    // ==========================================
    // KONTROL PEREKAMAN & TIMER
    // ==========================================
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

    function mulaiPerekaman() {
        recordedChunks = [];
        const stream = window.localStream;
        if (!stream) {
            Swal.fire({ icon: 'warning', title: 'Kamera Belum Aktif', text: "Mohon tunggu hingga kamera menyala atau izinkan akses perangkat." });
            return;
        }

        const candidates = ['video/webm;codecs=vp8,opus', 'video/webm;codecs=vp9,opus', 'video/webm'];
        const mimeType = candidates.find(t => window.MediaRecorder && MediaRecorder.isTypeSupported(t));
        const options = { videoBitsPerSecond: 1500000, audioBitsPerSecond: 128000 };
        if (mimeType) options.mimeType = mimeType;

        try {
            mediaRecorder = new MediaRecorder(stream, options);
        } catch (e) {
            mediaRecorder = new MediaRecorder(stream);
        }

        mediaRecorder.ondataavailable = event => {
            if (event.data && event.data.size > 0) recordedChunks.push(event.data);
        };

        mediaRecorder.onstop = () => {
            const blob = new Blob(recordedChunks, { type: 'video/webm' });
            kirimKeServer(blob);
        };

        mediaRecorder.start();

        btnStart.classList.add('hidden');
        recordingControls.classList.remove('hidden');
        document.getElementById('result-box').classList.add('hidden');

        setStatus('rec', 'SEDANG MEREKAM... SEBUTKAN KEPERLUAN ANDA!', true);

        secondsElapsed = 0;
        updateTimer();
        startTimer();
    }

    function jedaLanjutPerekaman() {
        if (!mediaRecorder) return;

        if (mediaRecorder.state === 'recording') {
            mediaRecorder.pause();
            clearInterval(timerInterval);

            textPause.innerText = "Lanjut";
            iconPause.className = "fas fa-play w-5 text-center";
            btnPause.className = CLASS_RESUME_BTN;
            setStatus('pause', 'PEREKAMAN DIJEDA');

        } else if (mediaRecorder.state === 'paused') {
            mediaRecorder.resume();
            startTimer();

            textPause.innerText = "Jeda";
            iconPause.className = "fas fa-pause w-5 text-center";
            btnPause.className = CLASS_PAUSE_BTN;
            setStatus('rec', 'SEDANG MEREKAM... SEBUTKAN KEPERLUAN ANDA!', true);
        }
    }

    function berhentiPerekaman() {
        clearInterval(timerInterval);

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop(); 
        }

        textPause.innerText = "Jeda";
        iconPause.className = "fas fa-pause w-5 text-center";
        btnPause.className = CLASS_PAUSE_BTN;

        recordingControls.classList.add('hidden');
        btnStart.classList.remove('hidden');
        btnStart.disabled = true;
        btnStart.innerHTML = SPINNER_HTML('Mengekstrak Wajah & Suara...');

        setStatus('busy', 'MENGEKSTRAKSI WAJAH & SUARA...', true);
    }

    // ==========================================
    // API REQUEST KE SOWAN UTAMA 
    // PERBAIKAN: MENGGUNAKAN ROUTE PETUGAS
    // ==========================================
    async function kirimKeServer(blob) {
        const formData = new FormData();
        formData.append('media_file', blob, 'perekaman_tamu.webm');

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const headers = { 'Accept': 'application/json' };
        if (csrfMeta) headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');

        try {
            // PERUBAHAN JENIUS: Menggunakan route yang benar (petugas.ai_scanner.process)
            const response = await fetch("{{ route('petugas.ai_scanner.process') }}", {
                method: 'POST',
                headers: headers,
                body: formData
            });

            const raw = await response.text();
            let data = null;
            try { data = JSON.parse(raw); } catch (e) { /* Bukan JSON valid */ }

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
            
            Swal.fire({ icon: 'error', title: 'Pemrosesan Gagal', text: pesan });
            resetUIFail();

        } catch (error) {
            btnStart.disabled = false;
            btnStart.innerHTML = START_BTN_HTML('Mulai Ulang Perekaman');
            Swal.fire({ icon: 'error', title: 'Koneksi Terputus', text: "Tidak dapat menghubungi server: " + error });
            resetUIFail();
        }
    }

    function tampilkanHasil(data) {
        setStatus('ok', 'ANALISIS SELESAI & TERSIMPAN!');

        const tamu = data.data.tamu || {};
        const kunjungan = data.data.kunjungan || {};
        const dbg = data.data.ai_debug || {};

        let peringatan = '';
        if (dbg.audio_ada === false || !dbg.transkrip) {
            peringatan += '<p class="md:col-span-3 text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 rounded-xl p-3 shadow-sm">Suara tidak terdengar jelas. Dekatkan tamu ke mikrofon lalu coba lagi.</p>';
        }
        if (dbg.wajah_terdeteksi === false) {
            peringatan += '<p class="md:col-span-3 text-xs font-bold text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-xl p-3 shadow-sm">Wajah tidak terdeteksi. Pastikan pencahayaan cukup dan wajah berada di dalam kotak YOLO.</p>';
        }

        const petugasTarget = kunjungan.petugas_tujuan || '-';
        const labelPetugas = petugasTarget !== '-' 
            ? (dbg.petugas_terdaftar ? '(Terdaftar di Master)' : '(Belum Terdaftar)')
            : '';
        const warnaPetugas = dbg.petugas_terdaftar ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';

        document.getElementById('result-box').classList.remove('hidden');
        document.getElementById('result-content').innerHTML = `
            ${peringatan}
            <div class="bg-emerald-50 dark:bg-emerald-950/50 p-5 rounded-2xl border border-emerald-200 dark:border-emerald-800 shadow-inner">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">Identifikasi Wajah</p>
                <p class="text-lg font-black text-slate-900 dark:text-emerald-50">${esc(tamu.nama_tamu)}</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-950/50 p-5 rounded-2xl border border-emerald-200 dark:border-emerald-800 shadow-inner">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">Status Kunjungan</p>
                <p class="text-lg font-black text-slate-900 dark:text-emerald-50">${esc(kunjungan.kategori_tamu)}
                    <span class="text-sm font-medium text-slate-600 dark:text-emerald-200/70 block">${esc(tamu.nama_instansi || '-')}</span>
                </p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-950/50 p-5 rounded-2xl border border-emerald-200 dark:border-emerald-800 shadow-inner">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">Tujuan & Keperluan</p>
                <p class="text-sm font-bold text-slate-900 dark:text-emerald-50 line-clamp-3">${esc(kunjungan.keterangan_keperluan)}</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-950/50 p-5 rounded-2xl border border-emerald-200 dark:border-emerald-800 shadow-inner md:col-span-3">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">Ingin Bertemu</p>
                <p class="text-lg font-black text-slate-900 dark:text-emerald-50">${esc(petugasTarget)}
                    <span class="text-xs font-bold ${warnaPetugas} ml-1">${esc(labelPetugas)}</span>
                </p>
            </div>
            <div class="md:col-span-3 text-xs text-slate-500 dark:text-emerald-200/70 bg-slate-50 dark:bg-emerald-950/30 border border-slate-200 dark:border-emerald-800 rounded-xl p-4 space-y-1.5">
                <p><span class="font-bold text-slate-600 dark:text-emerald-400">Teks Didengar:</span> ${esc(dbg.transkrip || '(Tidak terdeteksi)')}</p>
                <p><span class="font-bold text-slate-600 dark:text-emerald-400">Ekstraksi NLP:</span> ${esc(dbg.sumber_ekstraksi || '-')}${dbg.gemini_error ? ' | Gemini Error: ' + esc(dbg.gemini_error) : ''}</p>
                <p><span class="font-bold text-slate-600 dark:text-emerald-400">Metrik Wajah:</span> Skor Tertinggi ${esc(String(dbg.skor_kemiripan ?? '-'))} (Ambang Threshold: ${esc(String(dbg.ambang_wajah ?? '-'))}) | Terdekat: ${esc(dbg.tamu_terdekat || 'Belum ada data cocok')}</p>
            </div>
        `;
    }

    function resetUIFail() {
        setStatus('fail', 'GAGAL DIPROSES');
        timerDisplay.innerText = '00:00';
    }
</script>
@endpush