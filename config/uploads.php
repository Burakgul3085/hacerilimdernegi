<?php

return [
    'max_image_kb' => (int) env('UPLOAD_MAX_IMAGE_KB', 204800),
    'max_audio_kb' => (int) env('UPLOAD_MAX_AUDIO_KB', 512000),
    'max_video_kb' => (int) env('UPLOAD_MAX_VIDEO_KB', 2097152),
    'max_pdf_kb' => (int) env('UPLOAD_MAX_PDF_KB', 204800),
];
