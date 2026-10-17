<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetugasTujuan extends Model
{
    protected $table = 'petugas_tujuan';
    protected $primaryKey = 'id_petugas';
    protected $keyType = 'int';

    /**
     * PERIKSA: jika kolom id_petugas di phpMyAdmin bertanda AUTO_INCREMENT,
     * ubah menjadi true (atau hapus baris ini). Nilai false hanya benar
     * bila id_petugas selalu Anda isi manual.
     */
    public $incrementing = false;

    /**
     * Perbaikan: 'alias' ditambahkan (ejaan lain nama petugas untuk
     * pencocokan suara). $guarded = [] dihapus karena bentrok dengan $fillable.
     */
    protected $fillable = [
        'id_petugas',
        'nama_petugas',
        'jabatan',
        'alias',
    ];

    /**
     * Satu petugas dapat ditemui dalam banyak kunjungan.
     */
    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'id_petugas', 'id_petugas');
    }
}