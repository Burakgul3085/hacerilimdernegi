<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class NewsletterSubscriber extends Model
{
    use Auditable, Notifiable;

    protected $fillable = ['email', 'confirmed_at', 'ip_address'];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
