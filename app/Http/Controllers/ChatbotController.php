<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class ChatbotController extends Controller
{
    /**
     * Menampilkan halaman utama chatbot.
     */
    public function index()
    {
        return view('chatbot');
    }

    /**
     * Fungsi utama yang menangani semua permintaan (generate & edit).
     */
    public function handleMessage(Request $request)
    {
        $prompt = $request->input('prompt');

        // Cek jika ada file gambar dalam request. Jika ada, berarti ini mode EDIT.
        if ($request->hasFile('image')) {
            return $this->handleImageEditing($prompt, $request->file('image'));
        } else {
            // Jika tidak ada file, berarti ini mode GENERATE.
            return $this->handleImageGeneration($prompt);
        }
    }

    /**
     * Menangani pembuatan gambar baru dari teks menggunakan Stability AI.
     */
    private function handleImageGeneration($prompt)
    {
        $apiKey = env('STABILITY_API_KEY');
        if (!$apiKey) {
            return response()->json(['text' => 'Error: STABILITY_API_KEY belum diatur.'], 500);
        }

        $engineId = 'stable-diffusion-xl-1024-v1-0';
        $url = "https://api.stability.ai/v1/generation/{$engineId}/text-to-image";

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey, 'Accept' => 'application/json'])
                ->timeout(120)->post($url, [
                    'text_prompts' => [['text' => $prompt]],
                    'cfg_scale' => 7, 'height' => 1024, 'width' => 1024, 'samples' => 1, 'steps' => 30,
                ]);

            if ($response->failed()) {
                return response()->json(['text' => 'Gagal membuat gambar: ' . $response->body()], 500);
            }

            $imageData = $response->json('artifacts.0.base64');
            if ($imageData) {
                $imageUrl = 'data:image/png;base64,' . $imageData;
                return response()->json(['text' => 'Ini gambar yang kamu minta! 🎨', 'image' => $imageUrl]);
            }
            return response()->json(['text' => 'Maaf, data gambar tidak ditemukan.']);

        } catch (\Exception $e) {
            return response()->json(['text' => 'Terjadi kesalahan internal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menangani pengeditan gambar yang ada menggunakan Stability AI (Image-to-Image).
     */
    private function handleImageEditing($prompt, $imageFile)
    {
        $apiKey = env('STABILITY_API_KEY');
        if (!$apiKey) {
            return response()->json(['text' => 'Error: STABILITY_API_KEY belum diatur.'], 500);
        }

        $engineId = 'stable-diffusion-xl-1024-v1-0';
        $url = "https://api.stability.ai/v1/generation/{$engineId}/image-to-image";

        try {
            // Ubah ukuran gambar yang diterima langsung dari request
            $resizedImage = Image::make($imageFile)->fit(1024, 1024)->encode('png');

            $response = Http::asMultipart()
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey, 'Accept' => 'application/json'])
                ->timeout(120)
                ->attach('init_image', $resizedImage, 'resized-image.png')
                ->post($url, [
                    ['name' => 'text_prompts[0][text]', 'contents' => $prompt],
                    ['name' => 'image_strength', 'contents' => '0.5'],
                    ['name' => 'cfg_scale', 'contents' => '7'],
                    ['name' => 'steps', 'contents' => '30'],
                    ['name' => 'samples', 'contents' => '1'],
                ]);

            if ($response->failed()) {
                return response()->json(['text' => 'Gagal mengedit gambar: ' . $response->body()], 500);
            }

            $imageData = $response->json('artifacts.0.base64');
            if ($imageData) {
                $imageUrl = 'data:image/png;base64,' . $imageData;
                return response()->json(['text' => 'Ini hasil editan gambarmu! ✨', 'image' => $imageUrl]);
            }
            return response()->json(['text' => 'Maaf, data gambar editan tidak ditemukan.']);

        } catch (\Exception $e) {
            return response()->json(['text' => 'Terjadi kesalahan saat mengedit: ' . $e->getMessage()], 500);
        }
    }
}