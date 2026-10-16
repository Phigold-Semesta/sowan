import os
import shutil
from fastapi import UploadFile
from core.logger import log

def save_temp_file(upload_file: UploadFile, destination_path: str) -> bool:
    """
    Menyimpan berkas unggahan dari FastAPI ke lokasi sementara di dalam direktori.
    """
    try:
        with open(destination_path, "wb") as buffer:
            shutil.copyfileobj(upload_file.file, buffer)
        log.info(f"Berkas berhasil disimpan sementara di: {destination_path}")
        return True
    except Exception as e:
        log.error(f"Gagal menyimpan berkas sementara {destination_path}: {str(e)}")
        return False

def remove_temp_file(file_path: str) -> bool:
    """
    Menghapus berkas sementara dari sistem jika berkas tersebut ada.
    """
    try:
        if os.path.exists(file_path):
            os.remove(file_path)
            log.info(f"Berkas berhasil dibersihkan: {file_path}")
            return True
        return False
    except Exception as e:
        log.error(f"Gagal membersihkan berkas {file_path}: {str(e)}")
        return False