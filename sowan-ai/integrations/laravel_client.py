import os
import requests
from core.logger import log

class LaravelClient:
    """Klien HTTP untuk berinteraksi dengan API SOWAN Laravel 12."""
    
    def __init__(self):
        # Ambil URL dasar Laravel dari environment variable (default: localhost:8000/api)
        self.base_url = os.getenv("LARAVEL_API_URL", "http://127.0.0.1:8000/api")
        self.api_token = os.getenv("LARAVEL_API_TOKEN", "")

    def _get_headers(self):
        """Menyusun header standar untuk setiap request ke Laravel."""
        headers = {
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
        if self.api_token:
            headers["Authorization"] = f"Bearer {self.api_token}"
        return headers

    def fetch_master_tamu(self):
        """
        Mengambil daftar data tamu dari Laravel.
        Berguna jika AI perlu membandingkan wajah dengan data di database.
        """
        try:
            url = f"{self.base_url}/tamu/master"
            log.info(f"Mengambil data master tamu dari: {url}")
            
            response = requests.get(url, headers=self._get_headers(), timeout=10)
            response.raise_for_status()
            return response.json()
            
        except requests.exceptions.RequestException as e:
            log.error(f"Gagal terhubung ke Laravel API (fetch_master_tamu): {str(e)}")
            return None

    def send_webhook_result(self, payload: dict):
        """
        Mengirimkan hasil analisis AI (DeepFace & Whisper) ke endpoint webhook Laravel.
        """
        try:
            url = f"{self.base_url}/webhook/ai-result"
            log.info(f"Mengirim hasil AI ke Laravel: {url}")
            
            response = requests.post(url, json=payload, headers=self._get_headers(), timeout=15)
            response.raise_for_status()
            return response.json()
            
        except requests.exceptions.RequestException as e:
            log.error(f"Gagal mengirim data ke Laravel API (send_webhook_result): {str(e)}")
            return None

# Instansiasi objek secara global agar langsung bisa di-import oleh main.py
laravel_api = LaravelClient()