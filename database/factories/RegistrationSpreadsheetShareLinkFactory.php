<?php

namespace Database\Factories;

use App\Models\RegistrationSpreadsheet;
use App\Models\RegistrationSpreadsheetShareLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RegistrationSpreadsheetShareLink>
 */
class RegistrationSpreadsheetShareLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_spreadsheet_id' => RegistrationSpreadsheet::factory(),
            'user_id' => null,
            'token_hash' => RegistrationSpreadsheetShareLink::hashToken(Str::random(48)),
            'expires_at' => now()->addDay(),
            'single_use' => false,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }
}
