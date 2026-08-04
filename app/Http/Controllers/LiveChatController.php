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
    // SISI ADMINISTRATOR (CUSTOMER SERVICE MANAGEMENT)
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
        }

        $messages = $query->get();

        return response()->json(['messages' => $messages]);
    }

    public function sendAdmin(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'target_tamu_id' => 'required' // Wajib ada agar tidak salah kirim ke tamu lain
        ]);

        LiveChat::create([
            'user_id' => $request->target_tamu_id, // Masukkan ke ruang obrolan target tamu
            'sender_type' => 'administrator', // Penanda bahwa ini balasan dari Admin CS
            'message' => $request->message,
        ]);

        return response()->json(['status' => 'success']);
    }
}