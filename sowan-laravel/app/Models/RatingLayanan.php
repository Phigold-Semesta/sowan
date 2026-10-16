<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingLayanan extends Model
{
    use HasFactory;

    /**
     * Nama tabel sesuai skema SOWAN V2.
     */
    protected $table = 'rating_layanan';

    /**
     * PRIMARY KEY: Menggunakan id_rating agar selaras dengan routing dan view pimpinan ($rating->id_rating).
     */
    protected $primaryKey = 'id_rating';

    /**
     * Auto-increment diaktifkan karena id_rating bersifat increment.
     */
    public $incrementing = true;

    /**
     * Tipe data Primary Key.
     */
    protected $keyType = 'int';

    /**
     * Atribut yang dapat diisi massal.
     * Diselaraskan menggunakan 'skor' agar cocok dengan database dan controller.
     */
    protected $fillable = [
        'id_kunjungan', 
        'skor', 
        'komentar', 
        'tanggapan',
        'id_user' 
    ];

    /**
     * RELASI: Ke Kunjungan (Setiap rating terikat ke satu kunjungan).
     */
    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    /**
     * RELASI: Ke User (Petugas/Pimpinan yang memberikan tanggapan).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}