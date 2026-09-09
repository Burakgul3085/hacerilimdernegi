<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'kvkk_accepted', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'kvkk_accepted' => 'boolean',
            'is_read' => 'boolean',
        ];
    }
}
