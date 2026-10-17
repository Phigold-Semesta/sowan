<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kunjungan extends Model
{
    use HasFactory;

    /**
     * Tabel transaksi kunjungan (skema SOWAN V2).
     */
    protected $table = 'kunjungan';
    protected $primaryKey = 'id_kunjungan';

    /**
     * Perbaikan: $guarded = [] dihapus karena bentrok dengan $fillable.
     * Cukup satu mekanisme mass assignment, yaitu $fillable.
     *
     * Perbaikan: 'nomor_antrean' dihapus karena kolom itu tidak ada
     * di tabel kunjungan (db_sowan_v2). Jika nanti Anda menambahkan
     * kolomnya, tambahkan kembali di sini.
     */
    protected $fillable = [
        'gmail',
        'id_layanan',
        'id_petugas',
        'waktu_masuk',
        'waktu_keluar',
        'keterangan_keperluan',
        'status',
    ];

    protected $casts = [
        'waktu_masuk'  => 'datetime',
        'waktu_keluar' => 'datetime',
    ];

    /**
     * Satu kunjungan punya satu rating.
     */
    public function ratingLayanan(): HasOne
    {
        return $this->hasOne(RatingLayanan::class, 'id_kunjungan', 'id_kunjungan');
    }

    /**
     * Banyak kunjungan dimiliki satu tamu (FK: gmail).
     */
    public function tamu(): BelongsTo
    {
        return $this->belongsTo(Tamu::class, 'gmail', 'gmail')->withDefault([
            'nama_tamu'     => 'Tamu Tidak Terdaftar',
            'nama_instansi' => '-',
        ]);
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class, 'id_layanan', 'id_layanan')->withDefault([
            'nama_layanan' => 'Layanan Umum',
        ]);
    }

    /**
     * Relasi ke petugas yang ditemui (tabel petugas_tujuan).
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(PetugasTujuan::class, 'id_petugas', 'id_petugas')->withDefault([
            'nama_petugas' => 'Petugas Piket',
        ]);
    }

    /**
     * Alias agar kode lama (PetugasController) yang memanggil
     * petugasTujuan() tetap berfungsi.
     */
    public function petugasTujuan(): BelongsTo
    {
        return $this->petugas();
    }
}