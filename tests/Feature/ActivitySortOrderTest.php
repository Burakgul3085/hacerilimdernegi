<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivitySortOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_sort_order_is_rejected_on_save(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = Activity::factory()->create(['sort_order' => 5]);

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
            ->fillForm([
                'title' => $activity->title,
                'sort_order' => -2,
            ])
            ->call('save')
            ->assertHasFormErrors(['sort_order']);

        $this->assertSame(5, $activity->fresh()->sort_order);
    }

    public function test_zero_sort_order_can_be_saved(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = Activity::factory()->create(['sort_order' => 5]);

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
            ->fillForm([
                'title' => $activity->title,
                'sort_order' => 0,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $activity->fresh()->sort_order);
    }
}
