<?php

return [
    'base_url'    => env('QDRANT_BASE_URL', 'http://qdrant:6333'),
    'api_key'     => env('QDRANT_API_KEY'),
    'vector_size' => (int) env('QDRANT_VECTOR_SIZE', 768), // matches nomic-embed-text
    'timeout'     => (int) env('QDRANT_TIMEOUT', 30),
];
