<?php

namespace Database\Factories;

use App\Models\RegistrationSpreadsheet;
use App\Models\RegistrationSpreadsheetRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationSpreadsheetRow>
 */
class RegistrationSpreadsheetRowFactory extends Factory
{
    protected $model = RegistrationSpreadsheetRow::class;

    public function definition(): array
    {
        return [
            'registration_spreadsheet_id' => RegistrationSpreadsheet::factory(),
            'event_registration_id' => null,
            'sort_order' => 0,
            'cells' => [
                'id' => (string) $this->faker->numberBetween(1, 99),
                'name' => $this->faker->name(),
                'email' => $this->faker->safeEmail(),
                'status' => 'pending',
            ],
        ];
    }
}
