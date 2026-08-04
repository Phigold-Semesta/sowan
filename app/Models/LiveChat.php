<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChat extends Model
{
    // 1. Definisikan nama tabel secara eksplisit sesuai dengan database
    protected $table = 'live_chats';

    // 2. Izinkan pengisian data massal (Mass Assignment) untuk kolom ini
    protected $fillable = [
        'user_id',
        'sender_type',
        'message',
    ];

    // 3. (Opsional) Relasi ke model Tamu jika suatu saat butuh menampilkan data tamu
    // Sesuaikan 'id_tamu' dengan nama primary key di tabel tamu Anda
    public function tamu()
    {
        return $this->belongsTo(Tamu::class, 'user_id', 'id_tamu');
    }
}