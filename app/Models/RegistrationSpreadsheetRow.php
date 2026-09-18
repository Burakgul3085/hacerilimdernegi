<?php

namespace App\Models;

use Database\Factories\RegistrationSpreadsheetRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationSpreadsheetRow extends Model
{
    /** @use HasFactory<RegistrationSpreadsheetRowFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_spreadsheet_id',
        'event_registration_id',
        'sort_order',
        'cells',
    ];

    protected function casts(): array
    {
        return [
            'cells' => 'array',
        ];
    }

    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(RegistrationSpreadsheet::class, 'registration_spreadsheet_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}
