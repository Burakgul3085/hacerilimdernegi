<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\ActivitySessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySession extends Model
{
    /** @use HasFactory<ActivitySessionFactory> */
    use Auditable;

    use HasFactory;

    protected $fillable = [
        'activity_id', 'starts_at', 'location', 'note',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function isPast(): bool
    {
        return $this->starts_at->lt(now());
    }

    public function isToday(): bool
    {
        return $this->starts_at->isToday();
    }

    public function shortDate(): string
    {
        return $this->starts_at->translatedFormat('d F');
    }

    public function longDate(): string
    {
        return $this->starts_at->translatedFormat('d F, l');
    }

    public function timeLabel(): string
    {
        return $this->starts_at->translatedFormat('H:i');
    }
}
