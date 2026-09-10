<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\MembershipApplication;
use App\Models\MembershipApplicationReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipApplicationReply>
 */
class MembershipApplicationReplyFactory extends Factory
{
    protected $model = MembershipApplicationReply::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_application_id' => MembershipApplication::query()->create([
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'message' => fake()->paragraph(),
                'kvkk_accepted' => true,
                'status' => ApplicationStatus::Pending,
            ])->id,
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'sent_at' => now(),
        ];
    }
}
