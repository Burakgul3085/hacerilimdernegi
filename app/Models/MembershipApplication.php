<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class MembershipApplication extends Model
{
    use Auditable, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'city',
        'message',
        'kvkk_accepted',
        'status',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'kvkk_accepted' => 'boolean',
            'status' => ApplicationStatus::class,
            'replied_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<MembershipApplicationReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(MembershipApplicationReply::class)->latest('sent_at');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
