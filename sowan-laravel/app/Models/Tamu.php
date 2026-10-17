<?php

namespace App\Models;

// Trait Authenticatable dipertahankan agar model tetap bisa dipakai untuk login
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Tamu extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Tabel tamu memakai gmail (string) sebagai primary key, tanpa auto-increment.
     */
    protected $table = 'tamu';
    protected $primaryKey = 'gmail';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang boleh diisi massal.
     * face_embedding ditambahkan untuk fitur AI Scanner.
     * Catatan: kolom 'password' TIDAK ada di tabel tamu (db_sowan_v2).
     * Dibiarkan di sini hanya agar kode login lama tidak error bila dipakai.
     */
    protected $fillable = [
        'gmail',
        'password',
        'no_wa',
        'nama_tamu',
        'jenis_tamu',
        'nama_instansi',
        'alamat_kantor',
        'hadir_sebagai',
        'face_embedding',
    ];

    /**
     * Disembunyikan dari JSON/array supaya vektor wajah (data biometrik)
     * tidak ikut terkirim ke browser. Tetap bisa dibaca di server
     * lewat $tamu->face_embedding.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'face_embedding',
    ];

    /**
     * Sengaja TIDAK ada cast untuk face_embedding:
     * AiController menyimpannya sebagai JSON string dan membacanya
     * lewat ambilVektor().
     */
    protected $casts = [
        'gmail'    => 'string',
        'password' => 'hashed',
    ];

    public function getAuthIdentifierName()
    {
        return 'gmail';
    }

    public function getAuthIdentifier()
    {
        return $this->gmail;
    }

    /**
     * Perbaikan: sebelumnya memakai return type string tanpa fallback,
     * sehingga error TypeError bila kolom password tidak ada / NULL.
     */
    public function getAuthPassword(): string
    {
        return (string) ($this->password ?? '');
    }

    /**
     * Satu tamu memiliki banyak riwayat kunjungan.
     */
    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'gmail', 'gmail');
    }

    /**
     * Satu tamu dapat memberikan rating berkali-kali.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(RatingLayanan::class, 'gmail', 'gmail');
    }
}