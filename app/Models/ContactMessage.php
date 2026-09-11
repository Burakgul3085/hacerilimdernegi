<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class ContactMessage extends Model
{
    use Auditable, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'kvkk_accepted',
        'is_read',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'kvkk_accepted' => 'boolean',
            'is_read' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ContactMessageReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class)->latest('sent_at');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
