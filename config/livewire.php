<?php

return [
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:2097152'],
        'max_upload_time' => 60,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
            'webm', 'ogv', 'ogg', 'mkv', 'm4v',
        ],
    ],
];
