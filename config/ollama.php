<?php

return [
    'base_url'    => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'quick_model' => env('OLLAMA_QUICK_MODEL', 'gemma3:4b'),
    'smart_model' => env('OLLAMA_SMART_MODEL', 'qwen3:14b'),
    'embed_model' => env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'),
    'timeout'     => (int) env('OLLAMA_TIMEOUT', 120),
];
