<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivitySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivitySession>
 */
class ActivitySessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'starts_at' => now()->addDays(7)->setTime(14, 0),
            'location' => 'Şehitkamil / Gaziantep',
            'note' => null,
        ];
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subDays(7)->setTime(14, 0),
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->setTime(10, 0),
        ]);
    }
}
