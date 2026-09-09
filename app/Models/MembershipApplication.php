<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;

class MembershipApplication extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'city', 'message', 'kvkk_accepted', 'status',
    ];

    protected function casts(): array
    {
        return [
            'kvkk_accepted' => 'boolean',
            'status' => ApplicationStatus::class,
        ];
    }
}
