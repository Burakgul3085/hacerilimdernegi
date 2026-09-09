<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'confirmed_at', 'ip_address'];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }
}
