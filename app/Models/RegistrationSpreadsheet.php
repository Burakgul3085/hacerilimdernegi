<?php

namespace App\Models;

use Database\Factories\RegistrationSpreadsheetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationSpreadsheet extends Model
{
    /** @use HasFactory<RegistrationSpreadsheetFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'title',
        'source',
        'column_keys',
        'headers',
    ];

    protected function casts(): array
    {
        return [
            'column_keys' => 'array',
            'headers' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(RegistrationSpreadsheetRow::class)->orderBy('sort_order')->orderBy('id');
    }
}
