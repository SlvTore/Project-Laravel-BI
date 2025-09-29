<?php

return [
    'preview' => [
        'sample_size' => env('DATA_FEEDS_PREVIEW_SAMPLE_SIZE', 50),
        'token_ttl_minutes' => env('DATA_FEEDS_PREVIEW_TOKEN_TTL', 60),
        'max_file_size_mb' => env('DATA_FEEDS_PREVIEW_MAX_FILE_MB', 10),
        'product_match' => [
            'fuzzy_threshold' => env('DATA_FEEDS_PRODUCT_MATCH_FUZZY', 70),
            'exact_threshold' => env('DATA_FEEDS_PRODUCT_MATCH_EXACT', 90),
            'max_suggestions' => env('DATA_FEEDS_PRODUCT_MATCH_SUGGESTIONS', 3),
        ],
    ],
];
