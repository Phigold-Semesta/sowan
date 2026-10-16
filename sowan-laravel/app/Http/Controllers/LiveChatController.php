<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LiveChat;
use App\Models\Tamu; // Wajib ditambahkan untuk memanggil data kontak Tamu
use Illuminate\Support\Facades\Auth;

class LiveChatController extends Controller
{
    // =========================================================================
    // SISI TAMU (WIDGET FRONTEND)
    // =========================================================================
    
    public function fetchTamu()
    {
        // Mengambil ID tamu yang sedang login menggunakan guard 'tamu'
        $tamuId = Auth::guard('tamu')->id(); 
        
        // Ambil riwayat pesan khusus untuk tamu ini, urutkan dari yang terlama ke terbaru
        $messages = LiveChat::where('user_id', $tamuId)
                            ->orderBy('created_at', 'asc')
                            ->get();

        return response()->json(['messages' => $messages]);
    }

    public function sendTamu(Request $request)
    {
        $request->validate([
            'message' => 'required|string'
        ]);
        
        LiveChat::create([
            'user_id' => Auth::guard('tamu')->id(), // Identitas ruang obrolan (Pemilik Obrolan)
            'sender_type' => 'tamu', // Penanda bahwa pengirimnya adalah tamu itu sendiri
            'message' => $request->message,
        ]);

        return response()->json(['status' => 'success']);
    }

    // =========================================================================
    // SISI INTERNAL / ADMINISTRATOR (WRAPPER UNTUK ROUTE WEB.PHP)
    // =========================================================================

    public function fetchMessages(Request $request)
    {
        // Wrapper method untuk rute chat-fetch internal
        return $this->fetchAdmin($request);
    }

    public function sendMessage(Request $request)
    {
        // Wrapper method untuk rute chat-send internal (mendukung floating widget & panel utama)
        // PERBAIKAN: target_tamu_id sekarang WAJIB divalidasi di sini, bukan ditebak.
        // Sebelumnya, jika target_tamu_id tidak dikirim frontend, sistem menebak
        // "tamu terakhir yang chat secara global" — ini berbahaya karena bisa salah
        // kirim balasan ke tamu yang berbeda saat ada lebih dari 1 tamu aktif chat.
        // Sekarang: frontend WAJIB mengirim target_tamu_id (dipilih dari daftar kontak),
        // kalau tidak ada, request akan gagal validasi dengan pesan yang jelas.
        $request->validate([
            'message' => 'required|string',
            'target_tamu_id' => 'required|exists:tamu,id_tamu',
        ]);

        // Dapatkan role user internal yang sedang login secara dinamis
        $senderType = Auth::user()->role ?? 'administrator';

        LiveChat::create([
            'user_id' => $request->target_tamu_id,
            'sender_type' => $senderType,
            'message' => $request->message,
        ]);

        return response()->json(['status' => 'success']);
    }

    // BARU: Daftar tamu yang pernah mengirim live chat, dipakai oleh widget
    // floating staff (petugas/pimpinan/administrator) untuk memilih
    // "sedang berbicara dengan tamu yang mana". Tanpa ini, frontend tidak
    // pernah tahu target_tamu_id yang harus dikirim ke sendMessage()/sendAdmin().
    public function getContacts()
    {
        $tamuIds = LiveChat::select('user_id')->distinct()->pluck('user_id');

        $contacts = Tamu::whereIn('id_tamu', $tamuIds)->get()->map(function ($tamu) {
            $last = LiveChat::where('user_id', $tamu->id_tamu)
                             ->latest('created_at')
                             ->first();

            return [
                'id_tamu'      => $tamu->id_tamu,
                'nama_tamu'    => $tamu->nama_tamu,
                'last_message' => $last->message ?? '',
            ];
        })->values();

        return response()->json(['contacts' => $contacts]);
    }

    // =========================================================================
    // SISI ADMINISTRATOR (CUSTOMER SERVICE MANAGEMENT PANEL)
    // =========================================================================

    public function indexAdmin()
    {
        // Mengambil daftar unik tamu yang pernah mengirim pesan ke Live Chat
        // id_tamu dicocokkan dengan user_id yang ada di tabel live_chats
        $contacts = Tamu::whereIn('id_tamu', function($query) {
            $query->select('user_id')->from('live_chats');
        })->get();

        // Mengarahkan ke halaman UI Live Chat Administrator yang Mewah
        return view('admin.chat.index', compact('contacts'));
    }

    public function fetchAdmin(Request $request)
    {
        // Admin butuh parameter ID Tamu dari frontend untuk tahu ruang obrolan siapa yang dibuka
        $targetTamuId = $request->query('target_tamu_id');

        $query = LiveChat::orderBy('created_at', 'asc');
        
        // Jika admin sudah mengklik salah satu nama tamu, filter chatnya
        if ($targetTamuId) {
            $query->where('user_id', $targetTamuId);
        } else {
            // PERBAIKAN: sebelumnya jika target_tamu_id kosong, query akan
            // mengembalikan SEMUA pesan dari SEMUA tamu tercampur jadi satu.
            // Sekarang: kalau belum ada tamu yang dipilih, kembalikan kosong
            // saja agar tidak membingungkan/salah tampil.
            return response()->json(['messages' => []]);
        }

        $messages = $query->get();

        return response()->json(['messages' => $messages]);
    }

    public function sendAdmin(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'target_tamu_id' => 'required|exists:tamu,id_tamu' // Wajib ada agar tidak salah kirim ke tamu lain di panel utama
        ]);

        $senderType = Auth::user()->role ?? 'administrator';

        LiveChat::create([
            'user_id' => $request->target_tamu_id, // Masukkan ke ruang obrolan target tamu
            'sender_type' => $senderType, // Penanda pengirim internal (administrator / petugas / pimpinan)
            'message' => $request->message,
        ]);

        return response()->json(['status' => 'success']);
    }
}