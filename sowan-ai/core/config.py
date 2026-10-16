import os
from dotenv import load_dotenv

# Memuat variabel dari file .env yang berada di root direktori
load_dotenv()

class Settings:
    """Konfigurasi utama untuk SOWAN Sandbox AI Engine."""
    
    # Konfigurasi Aplikasi
    APP_NAME: str = "SOWAN Sandbox AI Engine"
    APP_VERSION: str = "1.0.0"
    APP_HOST: str = os.getenv("APP_HOST", "127.0.0.1")
    APP_PORT: int = int(os.getenv("APP_PORT", 8001))
    
    # Konfigurasi Model AI
    WHISPER_MODEL: str = os.getenv("WHISPER_MODEL", "base")
    DEEPFACE_MODEL: str = os.getenv("DEEPFACE_MODEL", "Facenet512")

# Instansiasi objek settings agar bisa di-import oleh file lain (misal: main.py)
settings = Settings()