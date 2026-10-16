def determine_petugas_tujuan(transcribed_text: str) -> str:
    """
    Menganalisis teks keperluan pengunjung dan memetakan ke petugas tujuan yang tepat.
    """
    if not transcribed_text:
        return "Administrator LPSE"

    text_lower = transcribed_text.lower()
    
    # Aturan berbasis kata kunci (Keyword-based rules)
    if any(keyword in text_lower for keyword in ["lelang", "tender", "dokumen penawaran", "pembuktian"]):
        return "Tim Layanan Lelang"
    
    elif any(keyword in text_lower for keyword in ["teknis", "sistem", "error", "akun", "lupa password", "spse"]):
        return "Helpdesk Teknis LPSE"
    
    elif any(keyword in text_lower for keyword in ["kontrak", "pembayaran", "tagihan", "keuangan"]):
        return "Bagian Keuangan & Administrasi"
        
    return "Administrator LPSE"