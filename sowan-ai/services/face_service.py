import os
import cv2
from deepface import DeepFace
from core.logger import log
from core.config import settings

def extract_face_embedding(video_path: str) -> list | None:
    """
    Mengambil frame pertama dari video dan memprosesnya dengan DeepFace
    untuk menghasilkan vektor wajah (embedding).
    """
    cap = cv2.VideoCapture(video_path)
    success, frame = cap.read()
    embedding_result = None

    if success:
        temp_image_path = "temp_face_process.jpg"
        cv2.imwrite(temp_image_path, frame)
        
        try:
            log.info(f"Menganalisis wajah dengan model {settings.DEEPFACE_MODEL}...")
            embedding_objs = DeepFace.represent(
                img_path=temp_image_path,
                model_name=settings.DEEPFACE_MODEL,
                enforce_detection=False # Tetap proses meskipun wajah kurang fokus
            )
            
            if embedding_objs and len(embedding_objs) > 0:
                embedding_result = embedding_objs[0].get("embedding", [])
                log.info("Ekstraksi vektor wajah berhasil.")
                
        except Exception as e:
            log.error(f"Gagal memproses wajah (DeepFace): {str(e)}")
            
        finally:
            if os.path.exists(temp_image_path):
                os.remove(temp_image_path)
                
    cap.release()
    return embedding_result