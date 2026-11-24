<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_is_listed_in_the_header_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="/uyelik"', false)
            ->assertSee('Üyelik / gönüllülük formu');

        $this->get('/uyelik')->assertOk();
    }

    public function test_admin_can_turn_membership_off_and_it_leaves_the_site(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ManageSettings::class)
            ->assertSet('data.membership_enabled', true)
            ->set('data.membership_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        auth()->logout();

        $this->assertFalse(SiteSettings::membershipIsEnabled());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('/uyelik', false)
            ->assertDontSee('Üyelik / gönüllülük formu');

        $this->get('/uyelik')->assertRedirect(route('home'));
    }

    public function test_membership_form_is_not_accepted_while_disabled(): void
    {
        SiteSettings::put('membership_enabled', false);

        $this->post('/uyelik', [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'kvkk_accepted' => '1',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseCount('membership_applications', 0);
    }
}
