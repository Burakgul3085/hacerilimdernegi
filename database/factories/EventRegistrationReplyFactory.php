<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistrationReply>
 */
class EventRegistrationReplyFactory extends Factory
{
    protected $model = EventRegistrationReply::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $event = Event::query()->create([
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'is_published' => true,
            'registration_open' => true,
        ]);

        return [
            'event_registration_id' => EventRegistration::query()->create([
                'event_id' => $event->id,
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'notes' => fake()->paragraph(),
                'kvkk_accepted' => true,
                'status' => ApplicationStatus::Pending,
            ])->id,
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'sent_at' => now(),
        ];
    }
}
