<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\EventRegistrations\Pages\EditEventRegistration;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\EventRegistrationReceivedForAdmin;
use App\Support\RegistrationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityRegistrationFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_form_page_renders_custom_fields(): void
    {
        $activity = $this->makeActivityWithFields();

        $this->get(route('activities.register.form', $activity))
            ->assertOk()
            ->assertSee('Katılım başvurusu')
            ->assertSee('Yaş')
            ->assertSee('Şehir')
            ->assertSee('Gaziantep')
            ->assertSee('Ulaşım istiyorum')
            ->assertSee(route('activities.register', $activity), false);
    }

    public function test_activity_detail_also_renders_the_dynamic_fields(): void
    {
        $activity = $this->makeActivityWithFields();

        $this->get(route('activities.show', $activity))
            ->assertOk()
            ->assertSee('Yaş')
            ->assertSee('Şehir');
    }

    public function test_registration_stores_custom_answers_in_program_inbox(): void
    {
        $activity = $this->makeActivityWithFields();

        $this->from(route('activities.register.form', $activity))
            ->post(route('activities.register', $activity), [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'phone' => '05320000000',
                'custom' => [
                    'yas' => '28',
                    'sehir' => 'Gaziantep',
                    'ulasim_istiyorum' => '1',
                ],
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $registration = EventRegistration::query()->where('email', 'ayse@example.com')->firstOrFail();

        $this->assertSame($activity->id, $registration->activity_id);
        $this->assertSame(ApplicationStatus::Pending, $registration->status);
        $this->assertSame([
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '28'],
            ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'Gaziantep'],
            ['key' => 'ulasim_istiyorum', 'label' => 'Ulaşım istiyorum', 'value' => 'Evet'],
        ], $registration->answers);
        $this->assertStringContainsString('Yaş: 28', (string) $registration->notes);
        $this->assertStringContainsString('Şehir: Gaziantep', (string) $registration->notes);
    }

    public function test_missing_required_custom_field_is_rejected(): void
    {
        $activity = $this->makeActivityWithFields();

        $this->from(route('activities.register.form', $activity))
            ->post(route('activities.register', $activity), [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'custom' => [
                    'sehir' => 'Gaziantep',
                ],
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect(route('activities.register.form', $activity))
            ->assertSessionHasErrors(['custom.yas']);

        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_closed_registration_form_page_returns_404(): void
    {
        $activity = Activity::factory()->create([
            'slug' => 'kapali-dinamik-form',
            'registration_open' => false,
        ]);

        $this->get(route('activities.register.form', $activity))->assertNotFound();
    }

    public function test_legacy_default_notes_field_still_works_without_configured_fields(): void
    {
        $activity = Activity::factory()->create([
            'slug' => 'varsayilan-not-alani',
            'registration_fields' => null,
        ]);

        $this->assertSame('notes', $activity->registrationFieldDefinitions()[0]['key']);

        $this->post(route('activities.register', $activity), [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'custom' => [
                'notes' => 'Katılmak istiyorum.',
            ],
            'kvkk_accepted' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_registrations', [
            'email' => 'ayse@example.com',
            'notes' => 'Katılmak istiyorum.',
        ]);
    }

    public function test_admin_can_see_custom_answers_on_registration_edit_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $activity = $this->makeActivityWithFields();
        $registration = EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'notes' => "Yaş: 28\nŞehir: Gaziantep",
            'answers' => [
                ['key' => 'yas', 'label' => 'Yaş', 'value' => '28'],
                ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'Gaziantep'],
            ],
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditEventRegistration::class, ['record' => $registration->getKey()])
            ->assertOk()
            ->assertSee('Form cevapları')
            ->assertSee('01')
            ->assertSee('Yaş')
            ->assertSee('28')
            ->assertSee('Şehir')
            ->assertSee('Gaziantep')
            ->assertDontSee('Not / özet')
            ->assertDontSee('Form answers');
    }

    public function test_number_date_and_help_text_are_supported_on_the_public_form(): void
    {
        $activity = Activity::factory()->create([
            'slug' => 'genisletilmis-form',
            'registration_fields' => [
                [
                    'key' => 'yas',
                    'label' => 'Yaş',
                    'type' => 'number',
                    'required' => true,
                    'help' => 'Tam sayı yazın',
                    'placeholder' => 'Örn. 28',
                    'options' => [],
                ],
                [
                    'key' => 'baslangic',
                    'label' => 'Başlangıç tarihi',
                    'type' => 'date',
                    'required' => true,
                    'help' => '',
                    'placeholder' => '',
                    'options' => [],
                ],
            ],
        ]);

        $this->get(route('activities.register.form', $activity))
            ->assertOk()
            ->assertSee('Yaş')
            ->assertSee('Tam sayı yazın')
            ->assertSee('Örn. 28')
            ->assertSee('Başlangıç tarihi')
            ->assertSee('type="number"', false)
            ->assertSee('type="date"', false);

        $this->post(route('activities.register', $activity), [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'custom' => [
                'yas' => '28',
                'baslangic' => '2026-10-01',
            ],
            'kvkk_accepted' => '1',
        ])->assertRedirect();

        $registration = EventRegistration::query()->where('email', 'ayse@example.com')->firstOrFail();

        $this->assertSame('28', $registration->answers[0]['value']);
        $this->assertStringContainsString('2026', $registration->answers[1]['value']);
    }

    public function test_admin_mail_includes_structured_answers_and_deep_link(): void
    {
        $activity = $this->makeActivityWithFields();
        $registration = EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'phone' => '05320000000',
            'notes' => "Yaş: 28\nŞehir: Gaziantep",
            'answers' => [
                ['key' => 'yas', 'label' => 'Yaş', 'value' => '28'],
                ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'Gaziantep'],
            ],
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $payload = (new EventRegistrationReceivedForAdmin($registration))
            ->toPhpMailer($registration);

        $this->assertStringContainsString('Form cevapları', $payload['html']);
        $this->assertStringContainsString('Yaş', $payload['html']);
        $this->assertStringContainsString('Gaziantep', $payload['html']);
        $this->assertStringContainsString('Başvuruyu aç', $payload['html']);
        $this->assertStringContainsString($registration->panelEditUrl(), $payload['html']);
        $this->assertStringContainsString('Yaş: 28', $payload['text']);
        $this->assertStringNotContainsString('>Not<', $payload['html']);
    }

    public function test_registration_form_normalizes_select_options_from_multiline_text(): void
    {
        $fields = RegistrationForm::normalize([
            [
                'label' => 'Şehir',
                'type' => 'select',
                'required' => true,
                'options' => "Gaziantep\nİstanbul\n",
            ],
        ]);

        $this->assertSame('sehir', $fields[0]['key']);
        $this->assertSame(['Gaziantep', 'İstanbul'], $fields[0]['options']);
        $this->assertArrayHasKey('help', $fields[0]);
        $this->assertArrayHasKey('placeholder', $fields[0]);
    }

    private function makeActivityWithFields(): Activity
    {
        return Activity::factory()->create([
            'title' => 'Kitap tahlili başvurusu',
            'slug' => 'kitap-tahlili-dinamik',
            'registration_fields' => [
                [
                    'key' => 'yas',
                    'label' => 'Yaş',
                    'type' => 'text',
                    'required' => true,
                    'help' => '',
                    'placeholder' => '',
                    'options' => [],
                ],
                [
                    'key' => 'sehir',
                    'label' => 'Şehir',
                    'type' => 'select',
                    'required' => true,
                    'help' => '',
                    'placeholder' => '',
                    'options' => ['Gaziantep', 'Diğer'],
                ],
                [
                    'key' => 'ulasim_istiyorum',
                    'label' => 'Ulaşım istiyorum',
                    'type' => 'checkbox',
                    'required' => false,
                    'help' => '',
                    'placeholder' => '',
                    'options' => [],
                ],
            ],
        ]);
    }
}
