<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KnowledgeScraperService
{
    /**
     * Mengambil dan mengekstrak teks bersih dari URL Website
     */
    public function scrapeUrl(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(15)->get($url);

            if (!$response->successful()) {
                Log::error("[Scraper Error] Gagal fetch URL: {$url}, Status: " . $response->status());
                return null;
            }

            $html = $response->body();

            // 1. Buang tag script, style, head, dan komentar HTML
            $cleanHtml = preg_replace('/<(script|style|head).*?>.*?<\/(script|style|head)>/is', '', $html);
            $cleanHtml = preg_replace('/<!--.*?-->/s', '', $cleanHtml);

            // 2. Ubah tag HTML menjadi teks biasa
            $text = strip_tags($cleanHtml);

            // 3. Bersihkan spasi dan enter yang berlebihan
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            // Batasi teks maksimal 100.000 karakter agar tetap efisien
            return substr($text, 0, 100000);
        } catch (\Exception $e) {
            Log::error("[Scraper Exception] Error scraping {$url}: " . $e->getMessage());
            return null;
        }
    }
}