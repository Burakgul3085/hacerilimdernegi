<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessageReply>
 */
class ContactMessageReplyFactory extends Factory
{
    protected $model = ContactMessageReply::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_message_id' => ContactMessage::query()->create([
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'message' => fake()->paragraph(),
                'kvkk_accepted' => true,
            ])->id,
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'sent_at' => now(),
        ];
    }
}
