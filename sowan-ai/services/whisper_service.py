import whisper
from core.logger import log
from core.config import settings

# Memuat model Whisper ke dalam memori RAM saat aplikasi pertama kali dijalankan
log.info(f"Memuat model OpenAI Whisper ({settings.WHISPER_MODEL})... Mohon tunggu.")
try:
    _whisper_model = whisper.load_model(settings.WHISPER_MODEL)
    log.info("Model Whisper berhasil dimuat dan siap digunakan.")
except Exception as e:
    log.error(f"Gagal memuat model Whisper: {str(e)}")
    _whisper_model = None

def transcribe_audio(video_path: str) -> str:
    """
    Mengekstrak audio dari video dan mengubahnya menjadi teks menggunakan Whisper.
    """
    if _whisper_model is None:
        log.error("Model Whisper belum siap atau gagal dimuat.")
        return ""

    try:
        log.info("Mengekstrak transkrip audio dari video...")
        result = _whisper_model.transcribe(video_path, language="id")
        text = result.get("text", "").strip()
        log.info(f"Transkripsi selesai. Teks: '{text}'")
        return text
    except Exception as e:
        log.error(f"Gagal melakukan transkripsi audio: {str(e)}")
        return ""