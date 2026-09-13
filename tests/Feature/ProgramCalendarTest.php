<?php

namespace Tests\Feature;

use App\Enums\ProgramType;
use App\Models\Event;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_index_redirects_to_the_activity_showcase(): void
    {
        $this->get('/programlar')->assertRedirect(route('activities.index'));
    }

    public function test_legacy_event_index_redirects_to_the_activity_showcase(): void
    {
        $this->get('/etkinlikler')->assertRedirect(route('activities.index'));
    }

    public function test_event_detail_still_accepts_registration(): void
    {
        $event = $this->makeEvent();

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Faaliyetler')
            ->assertSee('Başvuruyu gönder');
    }

    public function test_program_detail_shows_the_participation_form_instead_of_the_contact_link(): void
    {
        $program = $this->makeProgram();

        $this->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('Katılım için bize yazın')
            ->assertSee('href="#kayit"', false)
            ->assertSee('Başvuruyu gönder')
            ->assertSee(route('programs.register', $program), false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProgram(array $attributes = []): Program
    {
        return Program::query()->create([
            'type' => ProgramType::Sohbet,
            'title' => 'Haftalık sohbet',
            'slug' => 'haftalik-sohbet',
            'is_published' => true,
            'starts_at' => now()->addDays(3),
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeEvent(array $attributes = []): Event
    {
        return Event::query()->create([
            'title' => 'Dönem açılış programı',
            'slug' => 'donem-acilis-programi',
            'is_published' => true,
            'registration_open' => true,
            'starts_at' => now()->addDays(10),
            ...$attributes,
        ]);
    }
}
