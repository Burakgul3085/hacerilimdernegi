<?php

namespace Database\Factories;

use App\Models\RegistrationSpreadsheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationSpreadsheet>
 */
class RegistrationSpreadsheetFactory extends Factory
{
    protected $model = RegistrationSpreadsheet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_id' => null,
            'title' => 'E-tablo '.$this->faker->unique()->numerify('###'),
            'source' => 'activity',
            'column_keys' => ['id', 'name', 'email', 'status'],
            'headers' => ['Başvuru no', 'Ad soyad', 'E-posta', 'Durum'],
        ];
    }
}
