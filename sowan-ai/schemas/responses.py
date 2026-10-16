from pydantic import BaseModel, Field
from typing import List, Optional

class TamuSchema(BaseModel):
    """Skema data profil tamu."""
    nama_tamu: str = Field(..., example="Tamu Otomatis AI")
    nama_instansi: Optional[str] = Field(None, example="Instansi Umum (AI Sandbox)")

class KunjunganSchema(BaseModel):
    """Skema data detail kunjungan."""
    kategori_tamu: str = Field(..., example="Tamu Baru")
    petugas_tujuan: str = Field(..., example="Administrator LPSE")
    keterangan_keperluan: str = Field(..., example="Konsultasi sistem pengadaan secara elektronik")

class AnalysisDataSchema(BaseModel):
    """Skema pembungkus data hasil analisis AI."""
    tamu: TamuSchema
    kunjungan: KunjunganSchema
    # face_embedding berupa array angka float (vektor wajah)
    face_embedding: Optional[List[float]] = Field(None, description="Vektor matriks wajah dari DeepFace")

class AnalysisResponseSchema(BaseModel):
    """Skema respons utama yang akan dikembalikan ke Laravel."""
    status: str = Field(..., example="success")
    message: str = Field(..., example="Analisis AI berhasil diselesaikan.")
    data: AnalysisDataSchema