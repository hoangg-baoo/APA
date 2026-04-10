<?php

return [
    'base_url' => env('CLIP_SERVICE_URL', 'http://127.0.0.1:8001'),
    'timeout'  => (int) env('CLIP_SERVICE_TIMEOUT', 60),
];
