<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retrieval & Threshold
    |--------------------------------------------------------------------------
    | Ambang cosine similarity untuk menjawab dari database (RAG).
    | Jika skor < threshold → fallback ke LLM (Gemini).
    */
    'threshold' => (float) env('CHATBOT_THRESHOLD', 0.10),
    'top_k'     => (int)   env('CHATBOT_TOPK', 5),   // ambil top-K snippet sebelum rerank/konteks

    // Versi Api
    'api_version' => env('GEMINI_API_VERSION', 'v1'),
    
    /*
    |--------------------------------------------------------------------------
    | Model Names (Gemini)
    |--------------------------------------------------------------------------
    | Atur via .env agar mudah ganti model tanpa ubah kode.
    */
    'models' => [
        'embed' => env('GEMINI_EMBED_MODEL', 'models/text-embedding-004'),
        'chat'  => env('GEMINI_MODEL',       'models/gemini-2.0-flash-001'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Runtime Options
    |--------------------------------------------------------------------------
    | Batasan ringan agar respons cepat & stabil.
    */
    'llm' => [
        // batas konteks yang dikirim ke LLM (lihat GeminiService)
        'max_context_items' => (int) env('CHATBOT_CTX_ITEMS', 3),
        'max_context_chars' => (int) env('CHATBOT_CTX_CHARS', 2000),

        // konfigurasi sampling (dipakai di GeminiService->chat())
        'temperature'       => (float) env('CHATBOT_TEMPERATURE', 0.2),
        'top_k'             => (int)   env('CHATBOT_LLM_TOPK', 40),
        'top_p'             => (float) env('CHATBOT_LLM_TOPP', 0.95),
        'max_output_tokens' => (int)   env('CHATBOT_MAX_TOKENS', 512),

        // HTTP client
        'timeout'           => (int) env('CHATBOT_HTTP_TIMEOUT', 25), // detik
        'retry'             => (int) env('CHATBOT_HTTP_RETRY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug / Badge Sumber
    |--------------------------------------------------------------------------
    | Jika true, frontend bisa menampilkan sumber & skor similarity kecil.
    */
    'debug_badge' => (bool) env('CHATBOT_DEBUG_BADGE', true),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Handoff
    |--------------------------------------------------------------------------
    | Atur default CTA bila nomor tidak ditemukan di DB (company_contacts).
    */
    'whatsapp' => [
        // fallback default (opsional) kalau tidak ada primary di DB
        'fallback_number' => env('CHATBOT_WA_FALLBACK', null), // contoh: '6281335117054'
        'default_message' => env('CHATBOT_WA_MESSAGE', 'Halo Neev, saya butuh bantuan.'),
    ],
];
