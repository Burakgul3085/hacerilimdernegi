<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    use Auditable;

    protected $fillable = [
        'event_id', 'name', 'email', 'phone', 'notes', 'kvkk_accepted', 'status',
    ];

    protected function casts(): array
    {
        return [
            'kvkk_accepted' => 'boolean',
            'status' => ApplicationStatus::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
