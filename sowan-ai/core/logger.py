import logging
import sys

def setup_logger(name: str = "sowan_ai_engine") -> logging.Logger:
    """Mengonfigurasi dan mengembalikan instance logger khusus."""
    
    # Membuat logger dengan nama spesifik
    logger = logging.getLogger(name)
    
    # Mencegah penambahan handler berulang kali jika dipanggil berkali-kali
    if not logger.handlers:
        logger.setLevel(logging.INFO)
        
        # Mengatur format output pesan log
        formatter = logging.Formatter(
            fmt="%(asctime)s | %(levelname)-8s | %(message)s",
            datefmt="%Y-%m-%d %H:%M:%S"
        )
        
        # Mengarahkan output log ke terminal (stdout)
        console_handler = logging.StreamHandler(sys.stdout)
        console_handler.setFormatter(formatter)
        
        # Memasang handler ke logger
        logger.addHandler(console_handler)
        
    return logger

# Instansiasi logger agar siap digunakan di seluruh aplikasi
log = setup_logger()