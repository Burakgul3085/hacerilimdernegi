<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use Auditable;

    protected $fillable = ['email', 'confirmed_at', 'ip_address'];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }
}
