<?php
/**
 * Konfigurasi Agen AI Asisten (Google Gemini Generative AI)
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 *
 * UPGRADED: Model chain, temperature, timeout, topP/topK untuk presisi maksimal
 */

// Cegah akses langsung
if (!defined('BASE_URL') && !defined('ROOT_PATH')) {
    @require_once __DIR__ . '/helpers.php';
}

return [
    // Kunci API Google Gemini (Free Tier dari Google AI Studio)
    'api_key' => getenv('GEMINI_API_KEY') ?: '',

    // Nama Asisten Virtual
    'assistant_name' => 'Amil Virtual BAZNAS Sumbawa',

    // Urutan Rantai Model Fallback (Model Terbaik â†’ Tercepat â†’ Paling Stabil)
    'model_chain' => [
        'gemini-3.6-flash',       // Model utama: reasoning cerdas, mutakhir, presisi tinggi
        'gemini-3.5-flash-lite',  // Fallback cepat jika model utama timeout/rate-limited
    ],

    // Konfigurasi cURL & Generasi
    'timeout_seconds' => 15,        // Dinaikkan: 8 â†’ 15 detik (mencegah timeout premature saat query kompleks)
    'temperature' => 0.08,          // Diturunkan: 0.2 â†’ 0.08 (maximum precision, zero creative drift)
    'max_output_tokens' => 2800,    // Dinaikkan: 1800 â†’ 2800 (mencegah JSON terpotong pada query kompleks)
    'top_p' => 0.90,                // Nucleus sampling: hanya token top 90% probability
    'top_k' => 30,                  // Batasi ke 30 token teratas per langkah generasi

    // Batas Maksimal Baris Hasil Pencarian Transaksi
    'max_search_results' => 15,

    // Rate Limiting (Maksimal request per menit per user/session)
    'rate_limit_per_minute' => 25,

    // Proteksi Keamanan Database: Kata kunci terlarang (Strict Read-Only)
    'forbidden_sql_keywords' => [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE',
        'RENAME', 'REPLACE', 'GRANT', 'REVOKE', 'LOCK', 'UNLOCK',
        'CREATE', 'EXEC', 'EXECUTE', 'INFORMATION_SCHEMA', 'BENCHMARK', 'SLEEP'
    ]
];
