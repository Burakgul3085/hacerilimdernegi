<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityEditLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_edit_page_groups_fields_into_tabs_with_a_publishing_sidebar(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = Activity::factory()->create();

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
            ->assertOk()
            ->assertSeeInOrder(['Genel bilgiler', 'Kayıt formu', 'Başvuru e-postaları', 'Galeri', 'Oturumlar', 'Kenar kutuları'])
            ->assertSee('Yayın')
            ->assertSee('Kapak görseli');
    }
}
