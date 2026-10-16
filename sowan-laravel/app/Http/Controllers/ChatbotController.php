<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class ChatbotController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = $request->input('message');
        $apiKey = config('services.groq.key');

        if (!$apiKey) {
            return response()->json([
                'reply' => 'Error: GROQ_API_KEY belum diatur di file .env atau config/services.php server Anda.'
            ], 500);
        }

        // Endpoint resmi Groq dengan format kompatibel OpenAI
        $url = "https://api.groq.com/openai/v1/chat/completions";

        $systemInstruction = "Anda adalah SOWAN AI Assistant, asisten virtual resmi untuk sistem buku tamu digital SOWAN di LPSE Kabupaten Karawang. Tugas Anda adalah membantu masyarakat dan pelaku usaha terkait informasi layanan konsultasi, jam operasional (Senin-Kamis 08.00-15.00 WIB, Jumat 08.00-15.30 WIB), pengadaan barang dan jasa, serta akses dokumen seperti E-Katalog dan SIKAP. Jawablah dengan ramah, profesional, menggunakan bahasa Indonesia yang baik dan sopan.";

        try {
    // Menggunakan withToken() untuk autentikasi Bearer khas Groq
    $response = Http::withoutVerifying()->withToken($apiKey)->post($url, [
        "model" => env('GROQ_MODEL', 'openai/gpt-oss-120b'), // Sudah dinamis dan aman
        "messages" => [
            [
                "role" => "system",
                "content" => $systemInstruction
            ],
            [
                "role" => "user",
                "content" => $userMessage
            ]
        ],
        "temperature" => 0.7,
    ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiReply = $data['choices'][0]['message']['content'] ?? 'Maaf, saya tidak dapat memproses respons saat ini.';
                
                return response()->json([
                    'reply' => trim($aiReply)
                ]);
            } else {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? 'Kesalahan tidak diketahui dari server Groq.';
                
                return response()->json([
                    'reply' => 'Gagal dari API Groq: ' . $errorMsg
                ], 500);
            }

        } catch (Exception $e) {
            return response()->json([
                'reply' => 'Koneksi Error: ' . $e->getMessage()
            ], 500);
        }
    }
}