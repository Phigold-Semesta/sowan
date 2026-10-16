import difflib
import json
import logging
import os
import re
import shutil
import subprocess
import tempfile
import time
from typing import Iterator, Optional

import cv2
import numpy as np
import whisper
from deepface import DeepFace
from dotenv import load_dotenv
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from google import genai
from google.genai import types
from pydantic import BaseModel
from ultralytics import YOLO

load_dotenv()
logging.basicConfig(level=logging.INFO, format="%(asctime)s | %(levelname)-7s | %(message)s")
log = logging.getLogger("sowan-ai")

# ------------------------------------------------------------------ KONFIGURASI
WHISPER_MODEL = os.getenv("WHISPER_MODEL", "turbo")   # turbo jauh lebih akurat dari small; auto-fallback bila tak tersedia
FACE_MODEL = os.getenv("DEEPFACE_MODEL", "Facenet512")
FACE_DETECTOR = os.getenv("DEEPFACE_DETECTOR", "opencv")
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")

# Model Gemini sering dipensiunkan Google. Daftar ini hanya "prioritas awal";
# bila semuanya 404, sistem otomatis mencari model flash yang tersedia untuk API key Anda.
GEMINI_MODELS = [m.strip() for m in os.getenv(
    "GEMINI_MODELS", "gemini-3.8-flash,gemini-flash-latest"
).split(",") if m.strip()]
GEMINI_TIMEOUT_MS = int(os.getenv("GEMINI_TIMEOUT_MS", "20000"))      # batas per panggilan (20 dtk)
GEMINI_TOTAL_BUDGET = float(os.getenv("GEMINI_TOTAL_BUDGET", "45"))   # batas total semua percobaan (dtk)
GEMINI_KIRIM_AUDIO = os.getenv("GEMINI_KIRIM_AUDIO", "1") == "1"      # Gemini ikut mendengar audio langsung

# Istilah yang sering disebut tamu: dipakai Whisper (initial_prompt) dan Gemini (koreksi salah dengar).
GLOSARIUM = os.getenv(
    "GLOSARIUM",
    "LPSE, LKPP, SPSE, SIRUP, SiKaP, e-Katalog, e-Purchasing, Karawang, tender, PBJ, penyedia, PPK, Pokja",
)

PETUGAS_THRESHOLD = float(os.getenv("PETUGAS_MATCH_THRESHOLD", "0.75"))
MIN_LEBAR_WAJAH = int(os.getenv("MIN_LEBAR_WAJAH", "80"))

app = FastAPI(
    title="SOWAN Sandbox AI Engine V3",
    description="Backend AI: YOLO + DeepFace (wajah), Whisper (suara), Gemini (ekstraksi)",
    version="3.6.0",
)

print(f"Memuat model OpenAI Whisper ({WHISPER_MODEL})... Mohon tunggu sebentar.")
try:
    whisper_model = whisper.load_model(WHISPER_MODEL)
except Exception as e:
    log.warning("Model Whisper '%s' gagal dimuat (%s). Memakai 'small'. "
                "Tip: pip install -U openai-whisper", WHISPER_MODEL, e)
    WHISPER_MODEL = "small"
    whisper_model = whisper.load_model(WHISPER_MODEL)

print("Memuat model YOLOv8 (Pendeteksi Orang)...")
yolo_model = YOLO("yolov8n.pt")

# Timeout WAJIB agar request tidak menggantung selamanya (penyebab cURL error 28 di PHP).
gemini_client = (
    genai.Client(
        api_key=GEMINI_API_KEY,
        http_options=types.HttpOptions(timeout=GEMINI_TIMEOUT_MS),
    )
    if GEMINI_API_KEY else None
)
if not gemini_client:
    log.warning("GEMINI_API_KEY kosong. Ekstraksi memakai regex cadangan saja.")

try:
    DeepFace.build_model(FACE_MODEL)  # unduh bobot saat startup, bukan saat request pertama
except Exception as e:
    log.warning("Gagal pra-muat model wajah: %s", e)

_gemini_model_ok: Optional[str] = None
_gemini_last_error: Optional[str] = None
_gemini_mati: set = set()                 # model yang sudah terbukti 404 (dilewati)
_gemini_otomatis: Optional[list] = None   # cache hasil pencarian model otomatis
_gemini_otomatis_ts: float = 0.0


# ------------------------------------------------------------------ WAJAH
def ambil_frame_kandidat(video_path: str, maks: int = 10, maks_scan: int = 400):
    """Ambil frame paling tajam (anti-blur) dari video."""
    cap = cv2.VideoCapture(video_path)
    kandidat, idx = [], 0
    while idx < maks_scan:
        if not cap.grab():
            break
        if idx % 5 == 0:
            ok, frame = cap.retrieve()
            if ok and frame is not None:
                gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
                kandidat.append((cv2.Laplacian(gray, cv2.CV_64F).var(), frame))
        idx += 1
    cap.release()
    kandidat.sort(key=lambda x: x[0], reverse=True)
    return [f for _, f in kandidat[:maks]]


def crop_kepala(frame):
    """YOLO cari orang terbesar, lalu ambil separuh bagian atas (kepala + bahu)."""
    hasil = yolo_model(frame, classes=[0], verbose=False)[0]
    if hasil.boxes is None or len(hasil.boxes) == 0:
        return None
    boxes = hasil.boxes.xyxy.cpu().numpy()
    areas = (boxes[:, 2] - boxes[:, 0]) * (boxes[:, 3] - boxes[:, 1])
    x1, y1, x2, y2 = boxes[int(np.argmax(areas))].astype(int)
    y2 = y1 + int((y2 - y1) * 0.5)
    mx = int((x2 - x1) * 0.1)
    h, w = frame.shape[:2]
    crop = frame[max(0, y1):min(h, y2), max(0, x1 - mx):min(w, x2 + mx)]
    return crop if crop.size else None


def _represent(gambar) -> Optional[np.ndarray]:
    try:
        rep = DeepFace.represent(
            img_path=gambar,
            model_name=FACE_MODEL,
            detector_backend=FACE_DETECTOR,
            enforce_detection=True,  # wajib benar-benar ada wajah
            align=True,
        )
        if not rep:
            return None
        # Ambil wajah terbesar bila ada lebih dari satu
        rep.sort(
            key=lambda r: r.get("facial_area", {}).get("w", 0) * r.get("facial_area", {}).get("h", 0),
            reverse=True,
        )
        area = rep[0].get("facial_area", {})
        if area.get("w", 999) < MIN_LEBAR_WAJAH:  # wajah terlalu kecil = tidak andal
            return None
        return np.array(rep[0]["embedding"], dtype=np.float64)
    except Exception:
        return None


def hitung_embedding(video_path: str) -> Optional[list]:
    vecs = []
    for frame in ambil_frame_kandidat(video_path, maks=10):
        for gambar in (crop_kepala(frame), frame):
            if gambar is None:
                continue
            vec = _represent(gambar)
            if vec is not None:
                vecs.append(vec / (np.linalg.norm(vec) or 1.0))
                break
        if len(vecs) >= 5:
            break
    if not vecs:
        return None

    # Buang embedding yang menyimpang dari mayoritas frame
    if len(vecs) >= 3:
        pusat = np.mean(vecs, axis=0)
        pusat = pusat / (np.linalg.norm(pusat) or 1.0)
        vecs = [v for v in vecs if float(np.dot(v, pusat)) >= 0.80] or vecs

    rata = np.mean(vecs, axis=0)
    rata = rata / (np.linalg.norm(rata) or 1.0)
    log.info("Embedding wajah dibuat dari %d frame.", len(vecs))
    return rata.tolist()


# ------------------------------------------------------------------ SUARA
HALUSINASI = (
    "terima kasih telah menonton", "terima kasih sudah menonton", "subtitle",
    "amara.org", "subscribe", "jangan lupa like",
)


def ekstrak_audio(src: str, dst: str) -> bool:
    """Video -> WAV 16kHz mono, dibersihkan & dinormalkan volumenya."""
    base = ["ffmpeg", "-y", "-i", src, "-vn", "-ac", "1", "-ar", "16000"]
    for filt in (["-af", "highpass=f=100,afftdn=nf=-25,dynaudnorm=f=150:g=15"], []):
        p = subprocess.run(base + filt + [dst], capture_output=True, text=True)
        if p.returncode == 0 and os.path.exists(dst) and os.path.getsize(dst) > 2000:
            return True
        log.warning("ffmpeg gagal/tanpa audio: %s", (p.stderr or "")[-300:])
    return False


def audio_berisi_suara(wav_path: str) -> bool:
    """Cek cepat: audio cukup panjang & tidak senyap (mencegah kirim audio kosong ke Gemini)."""
    try:
        audio = whisper.load_audio(wav_path)
        return audio.size >= 16000 and float(np.abs(audio).max()) >= 0.01
    except Exception:
        return False


def transkrip_suara(wav_path: str, nama_petugas: Optional[list] = None) -> str:
    audio = whisper.load_audio(wav_path)
    if audio.size < 16000 or float(np.abs(audio).max()) < 0.01:
        return ""

    prompt = ("Percakapan tamu dengan resepsionis kantor LPSE Karawang. "
              "Tamu menyebutkan nama, asal instansi, petugas yang ingin ditemui, dan keperluan kunjungan.")
    prompt += f" Istilah yang mungkin disebut: {GLOSARIUM}."
    if nama_petugas:
        # Membantu Whisper mengeja nama petugas dengan benar
        prompt += " Nama petugas: " + ", ".join(nama_petugas[:30]) + "."

    hasil = whisper_model.transcribe(
        audio,
        language="id",
        task="transcribe",
        temperature=0.0,
        fp16=False,
        condition_on_previous_text=False,
        no_speech_threshold=0.5,
        initial_prompt=prompt,
    )
    segmen = [
        s["text"].strip() for s in hasil.get("segments", [])
        if s.get("no_speech_prob", 0) < 0.6 and s.get("avg_logprob", 0) > -1.5
    ]
    teks = " ".join(segmen).strip()
    if len(teks) < 3 or any(h in teks.lower() for h in HALUSINASI):
        return ""
    return teks


# ------------------------------------------------------------------ EKSTRAKSI INFO (GEMINI)
class Ekstraksi(BaseModel):
    nama_tamu: Optional[str] = None
    nama_instansi: Optional[str] = None
    no_wa: Optional[str] = None
    petugas_disebut: Optional[str] = None
    keterangan_keperluan: Optional[str] = None


PROMPT = """Kamu asisten buku tamu LPSE Karawang. Kamu menerima ucapan seorang tamu dalam bentuk
transkrip speech-to-text (sering salah dengar, terutama nama orang, nama instansi, dan istilah teknis).
{instruksi_audio}

Ekstrak HANYA informasi penting:
- nama_tamu: nama pribadi tamu saja, tanpa sapaan (Pak/Bu/Mas/Mbak) dan tanpa basa-basi.
- nama_instansi: nama perusahaan/instansi/organisasi asal tamu.
- no_wa: nomor telepon/WhatsApp (angka saja) jika disebut.
- petugas_disebut: nama ORANG (petugas/pegawai) yang ingin ditemui tamu (misalnya "ingin bertemu pak Tolib"
  -> "Tolib"), tanpa sapaan. Ini BUKAN nama tamu dan BUKAN nama aplikasi/layanan. Jika nama itu mirip
  salah satu nama pada DAFTAR PETUGAS di bawah, tulis persis sesuai ejaan di daftar. Jika tidak ada, isi null.
- keterangan_keperluan: inti tujuan kunjungan, satu kalimat singkat dan baku.

DAFTAR PETUGAS:
{daftar}

ISTILAH/APLIKASI yang sering disebut tamu: {glosarium}.
Koreksi kata yang bunyinya mirip istilah ini (contoh: "eikatalup" -> "e-Katalog", "sirup"/"sirop" -> "SIRUP").

Aturan:
- Buang salam dan basa-basi (selamat pagi, permisi, terima kasih, dan sejenisnya).
- Jangan mengarang. Jika tidak disebut atau tidak terdengar jelas, isi null.
- Nama orang: tulis ejaan yang paling wajar untuk nama Indonesia.
- Perbaiki kapitalisasi: siti -> Siti, pt jaya abadi -> PT Jaya Abadi, cv maju -> CV Maju.

Transkrip Whisper: ```{teks}```
"""

_INSTRUKSI_ADA_AUDIO = ("Rekaman audio aslinya juga terlampir. DENGARKAN audionya dan utamakan apa yang "
                        "kamu dengar di audio bila berbeda dengan transkrip.")
_INSTRUKSI_TANPA_AUDIO = "Tidak ada audio terlampir; andalkan transkrip."

_POLA_MODEL_DIBUANG = ("image", "tts", "live", "audio", "embedding", "robotics",
                       "computer-use", "native", "vision", "thinking-exp", "-exp")


def _versi_model(nama: str) -> tuple:
    """'gemini-3.8-flash' -> (3, 8). Dipakai agar versi terbaru dicoba lebih dulu."""
    m = re.search(r"gemini-(\d+)(?:\.(\d+))?", nama)
    return (int(m.group(1)), int(m.group(2) or 0)) if m else (0, 0)


def temukan_model_gemini(paksa: bool = False) -> list:
    """Tanya Google model apa yang BENAR-BENAR tersedia untuk API key ini (di-cache 1 jam)."""
    global _gemini_otomatis, _gemini_otomatis_ts
    if not gemini_client:
        return []
    if not paksa and _gemini_otomatis is not None and time.time() - _gemini_otomatis_ts < 3600:
        return _gemini_otomatis
    try:
        kandidat = []
        for m in gemini_client.models.list():
            nama = (getattr(m, "name", "") or "").replace("models/", "")
            aksi = getattr(m, "supported_actions", None) or []
            if "flash" not in nama or "generateContent" not in aksi:
                continue
            if any(x in nama for x in _POLA_MODEL_DIBUANG):
                continue
            kandidat.append(nama)
        # Urut: versi terbaru dulu; stabil (tanpa 'preview') sebelum preview; non-lite sebelum lite
        kandidat.sort(key=lambda n: (_versi_model(n), "preview" not in n, "lite" not in n), reverse=True)
        _gemini_otomatis, _gemini_otomatis_ts = kandidat, time.time()
        log.info("Model Gemini tersedia (otomatis): %s", kandidat[:6])
        return kandidat
    except Exception as e:
        log.error("Gagal mengambil daftar model Gemini: %s", e)
        return _gemini_otomatis or []


def _urutan_model() -> Iterator[str]:
    """Tahap 1: model yang pernah berhasil + konfigurasi. Tahap 2: hasil pencarian otomatis."""
    sudah = set()
    tahap1 = ([_gemini_model_ok] if _gemini_model_ok else []) + GEMINI_MODELS
    for m in tahap1:
        if m and m not in sudah and m not in _gemini_mati:
            sudah.add(m)
            yield m
    for m in temukan_model_gemini():
        if m not in sudah and m not in _gemini_mati:
            sudah.add(m)
            yield m


def _panggil_gemini(model: str, prompt: str, audio_bytes: Optional[bytes] = None) -> Optional[dict]:
    contents = prompt
    if audio_bytes:
        contents = [types.Part.from_bytes(data=audio_bytes, mime_type="audio/wav"), prompt]
    resp = gemini_client.models.generate_content(
        model=model,
        contents=contents,
        config=types.GenerateContentConfig(
            response_mime_type="application/json",
            response_schema=Ekstraksi,
            temperature=0.0,
            automatic_function_calling=types.AutomaticFunctionCallingConfig(disable=True),
        ),
    )
    data = json.loads(resp.text)
    if isinstance(data, list) and data:
        data = data[0]
    return data if isinstance(data, dict) else None


def ekstrak_gemini(
    teks: str,
    daftar_nama: Optional[list] = None,
    wav_path: Optional[str] = None,
) -> Optional[dict]:
    """Gemini membaca transkrip (+ mendengar audio bila ada). Batas waktu ketat.
    Gagal = None, pemanggil jatuh ke regex."""
    global _gemini_model_ok, _gemini_last_error
    if not gemini_client:
        _gemini_last_error = "GEMINI_API_KEY kosong"
        return None

    audio_bytes = None
    if GEMINI_KIRIM_AUDIO and wav_path and os.path.exists(wav_path) and os.path.getsize(wav_path) < 15_000_000:
        with open(wav_path, "rb") as f:
            audio_bytes = f.read()

    daftar_teks = "\n".join(f"- {n}" for n in (daftar_nama or [])) or "(kosong)"
    prompt = PROMPT.format(
        teks=teks or "(tidak terdeteksi)",
        daftar=daftar_teks,
        glosarium=GLOSARIUM,
        instruksi_audio=_INSTRUKSI_ADA_AUDIO if audio_bytes else _INSTRUKSI_TANPA_AUDIO,
    )
    mulai, percobaan = time.time(), 0

    for model in _urutan_model():
        if time.time() - mulai > GEMINI_TOTAL_BUDGET:
            _gemini_last_error = f"Anggaran waktu Gemini habis ({GEMINI_TOTAL_BUDGET:.0f} dtk)"
            log.error(_gemini_last_error)
            break
        if percobaan >= 5:  # jangan mencoba tanpa batas
            break
        percobaan += 1
        try:
            data = _panggil_gemini(model, prompt, audio_bytes)
            if data:
                _gemini_model_ok, _gemini_last_error = model, None
                log.info("Gemini berhasil (%s, audio=%s, %.1f dtk)", model, bool(audio_bytes), time.time() - mulai)
                return data
            _gemini_last_error = f"{model}: respons kosong/tidak valid"
        except Exception as e:
            pesan = str(e)
            _gemini_last_error = f"{model}: {pesan[:300]}"
            log.error("Gemini gagal (%s): %s", model, pesan[:300])
            if "404" in pesan or "NOT_FOUND" in pesan:
                _gemini_mati.add(model)          # model pensiun -> jangan dicoba lagi
                if model == _gemini_model_ok:
                    _gemini_model_ok = None
    return None


# ------------------------------------------------------------------ EKSTRAKSI CADANGAN (REGEX)
STOP_NAMA = {"dari", "asal", "dan", "dengan", "saya", "kami", "ingin", "mau", "untuk",
             "bermaksud", "bekerja", "di", "ke", "yang", "mewakili", "pak", "bu", "bapak", "ibu"}
STOP_INSTANSI = {"ingin", "mau", "untuk", "bermaksud", "saya", "kami", "dan", "ke", "yang",
                 "datang", "kesini", "kemari", "bertemu", "menemui", "ketemu"}
STOP_PETUGAS = {"untuk", "dan", "karena", "soal", "tentang", "mengenai", "terkait",
                "ingin", "mau", "saya", "kami", "yang", "guna", "perihal",
                "bertemu", "ketemu", "menemui", "berkonsultasi", "konsultasi",
                # kata benda aplikasi/layanan: bukan nama orang
                "aplikasi", "sirup", "sikap", "katalog", "spse", "sistem", "layanan"}
SINGKATAN = {"pt", "cv", "ud", "pd", "kpu", "bpjs"}
GELAR = {"pak", "bapak", "bu", "ibu", "mas", "mbak", "kak", "dr", "ir", "drs", "bpk", "saudara"}


def _ambil_kata(teks: str, stop: set, maks: int) -> str:
    out = []
    for w in re.findall(r"[A-Za-z'.-]+", teks):
        if w.lower() in stop:
            break
        out.append(w)
        if len(out) >= maks:
            break
    return " ".join(out)


def _kapital(teks: str) -> str:
    return " ".join(w.upper() if w.lower() in SINGKATAN else w.capitalize() for w in teks.split())


def ekstrak_regex(teks: str) -> dict:
    """Cadangan sederhana bila Gemini tidak tersedia."""
    nama = instansi = petugas = None
    akhir_nama = 0

    m = re.search(r"(?:nama\s+(?:saya|aku|kami)|saya\s+bernama|perkenalkan(?:\s+nama)?\s+saya|perkenalkan)\s+", teks, re.I)
    if m:
        nama = _ambil_kata(teks[m.end():], STOP_NAMA, 3) or None
        akhir_nama = m.end()

    m = re.search(r"(?:\bdari\b|\basal(?:\s+dari)?\b|\bmewakili\b|bekerja\s+(?:di|pada))\s+", teks[akhir_nama:], re.I)
    if m:
        instansi = _ambil_kata(teks[akhir_nama + m.end():], STOP_INSTANSI, 5) or None

    # Hanya "bertemu/menemui/ketemu": kata "konsultasi" sering diikuti nama aplikasi, bukan nama orang.
    m = re.search(r"(?:bertemu|menemui|ketemu)\s+(?:dengan\s+|sama\s+)?(?:pak|bapak|bu|ibu|mas|mbak)?\s*", teks, re.I)
    if m:
        petugas = _ambil_kata(teks[m.end():], STOP_PETUGAS, 2) or None

    m = re.search(r"(?:ingin|mau|bermaksud|keperluan(?:\s+saya)?)\s+(.+)", teks, re.I)
    keperluan = m.group(1).strip(" .,") if m else teks

    return {
        "nama_tamu": _kapital(nama) if nama else None,
        "nama_instansi": _kapital(instansi) if instansi else None,
        "no_wa": None,
        "petugas_disebut": _kapital(petugas) if petugas else None,
        "keterangan_keperluan": keperluan or None,
    }


def _bersih(nilai) -> Optional[str]:
    if nilai is None:
        return None
    s = str(nilai).strip(" \t\n.,\"'")
    if s.lower() in {"", "null", "none", "-", "tidak ada", "tidak disebutkan", "tidak diketahui"}:
        return None
    return s


def bersihkan_nama(nilai) -> Optional[str]:
    s = _bersih(nilai)
    if not s:
        return None
    s = re.sub(r"^(?:bapak|ibu|pak|bu|mas|mbak|saudara|saudari)\s+", "", s, flags=re.I)
    if re.search(r"\d", s) or len(s.split()) > 5:
        return None
    return _kapital(s)


def bersihkan_wa(no) -> Optional[str]:
    s = _bersih(no)
    if not s:
        return None
    digit = re.sub(r"\D", "", s)
    return digit if 9 <= len(digit) <= 15 else None


# ------------------------------------------------------------------ PENCOCOKAN PETUGAS (master)
def _norm_nama(s: str) -> str:
    kata = [k for k in re.sub(r"[^a-z\s]", " ", (s or "").lower()).split() if k not in GELAR]
    return " ".join(kata)


def cocokkan_petugas(disebut: Optional[str], daftar: list, ambang: float = PETUGAS_THRESHOLD) -> Optional[str]:
    """Cocokkan nama yang disebut tamu dengan master. Kembalikan nama persis di master, atau None.
    Jika dua petugas sama kuat (ambigu), kembalikan None agar tidak menebak."""
    a = _norm_nama(disebut or "")
    if not a:
        return None

    skor_per_petugas = []
    for p in daftar:
        namas = [p.get("nama_petugas", "")] + [x.strip() for x in (p.get("alias") or "").split(",") if x.strip()]
        terbaik = 0.0
        for n in namas:
            b = _norm_nama(n)
            if not b:
                continue
            skor = 1.0 if a == b else difflib.SequenceMatcher(None, a, b).ratio()
            if any(len(k) >= 3 and k in b.split() for k in a.split()):
                skor = max(skor, 0.9)
            for ka in a.split():
                for kb in b.split():
                    if len(ka) >= 3 and len(kb) >= 3:
                        skor = max(skor, difflib.SequenceMatcher(None, ka, kb).ratio() * 0.95)
            terbaik = max(terbaik, skor)
        skor_per_petugas.append((terbaik, p.get("nama_petugas")))

    if not skor_per_petugas:
        return None
    skor_per_petugas.sort(key=lambda x: x[0], reverse=True)
    if skor_per_petugas[0][0] < ambang:
        return None
    if len(skor_per_petugas) > 1 and abs(skor_per_petugas[0][0] - skor_per_petugas[1][0]) < 0.001:
        return None  # ambigu
    return skor_per_petugas[0][1]


def baca_master(daftar_petugas: str) -> list:
    try:
        master = json.loads(daftar_petugas)
        return [p for p in master if isinstance(p, dict) and p.get("nama_petugas")]
    except Exception:
        return []


# ------------------------------------------------------------------ API
@app.get("/")
def read_root():
    return {
        "status": "online",
        "message": f"SOWAN Sandbox AI Engine V3 aktif di port {os.getenv('APP_PORT', '8001')}",
        "whisper": WHISPER_MODEL,
        "face_model": FACE_MODEL,
        "face_detector": FACE_DETECTOR,
        "gemini_siap": bool(gemini_client),
        "gemini_dengar_audio": GEMINI_KIRIM_AUDIO,
        "gemini_model_dikonfigurasi": GEMINI_MODELS,
        "gemini_model_terakhir_berhasil": _gemini_model_ok,
        "gemini_model_pensiun_terdeteksi": sorted(_gemini_mati),
    }


@app.get("/api/v1/debug/models")
def debug_models():
    """Daftar model Gemini yang benar-benar tersedia untuk API key ini."""
    return {
        "gemini_siap": bool(gemini_client),
        "tersedia": temukan_model_gemini(paksa=True),
        "dikonfigurasi": GEMINI_MODELS,
        "pensiun_terdeteksi": sorted(_gemini_mati),
    }


@app.get("/api/v1/debug/gemini")
def debug_gemini():
    """Uji Gemini + ekstraksi petugas terpisah dari audio/video (teks saja)."""
    contoh = ("Selamat pagi pak, perkenalkan nama saya siti dari pt jaya abadi, "
              "ingin bertemu dengan pak tholib untuk konsultasi aplikasi sirup.")
    master = [{"nama_petugas": "Tolib", "alias": "Tholib,Tolip"}, {"nama_petugas": "Budi", "alias": ""}]
    data = ekstrak_gemini(contoh, [p["nama_petugas"] for p in master])
    disebut = _bersih((data or {}).get("petugas_disebut"))
    return {
        "berhasil": data is not None,
        "model": _gemini_model_ok,
        "hasil": data,
        "petugas_disebut": disebut,
        "petugas_cocok": cocokkan_petugas(disebut, master),
        "error_terakhir": _gemini_last_error,
    }


@app.post("/api/v1/debug/transcribe")
def debug_transcribe(media_file: UploadFile = File(...)):
    """Uji suara saja: kembalikan apa yang didengar Whisper."""
    tmpdir = tempfile.mkdtemp(prefix="sowan_")
    try:
        src, wav = os.path.join(tmpdir, "in.webm"), os.path.join(tmpdir, "a.wav")
        with open(src, "wb") as f:
            shutil.copyfileobj(media_file.file, f)
        ada_audio = ekstrak_audio(src, wav)
        teks = transkrip_suara(wav) if ada_audio else ""
        return {"audio_ada": ada_audio, "transkrip": teks}
    finally:
        shutil.rmtree(tmpdir, ignore_errors=True)


@app.post("/api/v1/analyze")
def analyze_media(
    media_file: UploadFile = File(...),
    daftar_petugas: str = Form("[]"),  # JSON master petugas dari Laravel
):  # sengaja `def`, bukan `async def`
    tmpdir = tempfile.mkdtemp(prefix="sowan_")  # di luar folder proyek, aman dari --reload
    t0 = time.time()
    try:
        master = baca_master(daftar_petugas)
        daftar_nama = [p["nama_petugas"] for p in master]

        video_path = os.path.join(tmpdir, "input.webm")
        wav_path = os.path.join(tmpdir, "audio.wav")
        with open(video_path, "wb") as f:
            shutil.copyfileobj(media_file.file, f)

        # 1) Wajah
        embedding = hitung_embedding(video_path)
        t_wajah = time.time() - t0

        # 2) Suara -> teks
        if not shutil.which("ffmpeg"):
            raise HTTPException(status_code=500, detail="ffmpeg belum terpasang (winget install ffmpeg).")
        ada_audio = ekstrak_audio(video_path, wav_path)
        teks = transkrip_suara(wav_path, daftar_nama) if ada_audio else ""
        t_suara = time.time() - t0 - t_wajah
        log.info("Transkrip: %r", teks)

        # 3) Ekstraksi informasi penting: Gemini (mendengar audio + baca transkrip) -> regex sebagai cadangan
        data, sumber = {}, "tidak_ada_suara"
        suara_nyata = ada_audio and audio_berisi_suara(wav_path)
        if teks or suara_nyata:
            data = ekstrak_gemini(teks, daftar_nama, wav_path if suara_nyata else None)
            sumber = "gemini"
            if not data:
                if teks:
                    data, sumber = ekstrak_regex(teks), "regex"
                else:
                    data, sumber = {}, "tidak_ada_suara"
        t_ekstrak = time.time() - t0 - t_wajah - t_suara

        keperluan = _bersih(data.get("keterangan_keperluan")) or teks or "-"
        petugas_disebut = _bersih(data.get("petugas_disebut"))
        petugas_cocok = cocokkan_petugas(petugas_disebut, master)
        if sumber == "regex" and not petugas_cocok:
            petugas_disebut = None  # regex sering salah tangkap; lebih baik kosong daripada data sampah
        log.info("Petugas disebut=%r cocok=%r | sumber=%s | wajah=%.1fs suara=%.1fs ekstraksi=%.1fs",
                 petugas_disebut, petugas_cocok, sumber, t_wajah, t_suara, t_ekstrak)

        return {
            "status": "success",
            "message": "Analisis AI berhasil diselesaikan.",
            "data": {
                "tamu": {
                    "nama_tamu": bersihkan_nama(data.get("nama_tamu")),
                    "nama_instansi": _bersih(data.get("nama_instansi")),
                    "no_wa": bersihkan_wa(data.get("no_wa")),
                },
                "kunjungan": {
                    "petugas_disebut": petugas_disebut,
                    "petugas_cocok": petugas_cocok,
                    "keterangan_keperluan": keperluan,
                },
                "face_embedding": embedding,
            },
            "debug": {
                "audio_ada": ada_audio,
                "transkrip": teks,
                "sumber_ekstraksi": sumber,
                "gemini_dengar_audio": bool(suara_nyata and GEMINI_KIRIM_AUDIO),
                "whisper_model": WHISPER_MODEL,
                "wajah_terdeteksi": embedding is not None,
                "gemini_error": _gemini_last_error if sumber != "gemini" else None,
                "durasi_detik": {
                    "wajah": round(t_wajah, 1),
                    "suara": round(t_suara, 1),
                    "ekstraksi": round(t_ekstrak, 1),
                    "total": round(time.time() - t0, 1),
                },
            },
        }
    except HTTPException:
        raise
    except Exception as e:
        log.exception("analyze gagal")
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        shutil.rmtree(tmpdir, ignore_errors=True)