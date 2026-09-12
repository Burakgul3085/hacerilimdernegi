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

    public function test_program_index_lists_upcoming_programs_and_events_together(): void
    {
        $this->makeProgram([
            'title' => 'Haftalık sohbet',
            'slug' => 'haftalik-sohbet-takvim',
            'starts_at' => now()->addDays(2),
        ]);
        $this->makeEvent([
            'title' => 'Dönem açılışı',
            'slug' => 'donem-acilisi-takvim',
            'starts_at' => now()->addDays(5),
        ]);

        $this->get('/programlar')
            ->assertOk()
            ->assertSee('Yaklaşan')
            ->assertSee('Geçmiş')
            ->assertSee('Haftalık sohbet')
            ->assertSee('Sohbet')
            ->assertSee('Dönem açılışı')
            ->assertSee('Kayıt açık')
            ->assertSeeInOrder(['Haftalık sohbet', 'Dönem açılışı']);
    }

    public function test_past_scope_hides_upcoming_items(): void
    {
        $this->makeProgram([
            'title' => 'Yaklaşan sohbet',
            'slug' => 'yaklasan-sohbet',
            'starts_at' => now()->addDays(3),
        ]);
        $this->makeProgram([
            'title' => 'Geçen sohbet',
            'slug' => 'gecen-sohbet',
            'starts_at' => now()->subDays(10),
        ]);

        $this->get('/programlar?durum=gecmis')
            ->assertOk()
            ->assertSee('Geçen sohbet')
            ->assertDontSee('Yaklaşan sohbet');
    }

    public function test_month_filter_limits_the_calendar(): void
    {
        $thisMonth = $this->makeEvent([
            'title' => 'Bu ayki program',
            'slug' => 'bu-ayki-program',
            'starts_at' => now()->addDays(2),
        ]);
        $this->makeEvent([
            'title' => 'İki ay sonraki program',
            'slug' => 'iki-ay-sonraki-program',
            'starts_at' => now()->addMonths(2),
        ]);

        $this->get('/programlar?ay='.$thisMonth->starts_at->format('Y-m'))
            ->assertOk()
            ->assertSee('Bu ayki program')
            ->assertDontSee('İki ay sonraki program');
    }

    public function test_legacy_event_index_redirects_to_the_program_calendar(): void
    {
        $this->get('/etkinlikler')
            ->assertRedirect(route('programs.index'));

        $this->get('/etkinlikler?ay=2026-09')
            ->assertRedirect(route('programs.index', ['ay' => '2026-09']));
    }

    public function test_event_detail_still_accepts_registration(): void
    {
        $event = $this->makeEvent();

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Programlar')
            ->assertSee('Başvuruyu gönder');
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
