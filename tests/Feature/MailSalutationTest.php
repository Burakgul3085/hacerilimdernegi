<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\AdminLoginVerificationCode;
use App\Notifications\ContactMessageAcknowledged;
use App\Support\DecisionMail;
use App\Support\MailSalutation;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MailSalutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mails_use_merhaba_and_saygilarimizla_by_default(): void
    {
        $mail = (new ContactMessageAcknowledged($this->contactMessage('Ayşe Yılmaz')))->toPhpMailer(new \stdClass);

        $this->assertStringContainsString('Merhaba Ayşe Yılmaz,', $mail['html']);
        $this->assertStringContainsString('Saygılarımızla,<br><strong>Hâcer İlim ve Kültür Derneği</strong>', $mail['html']);
        $this->assertStringStartsWith('Merhaba Ayşe Yılmaz,', $mail['text']);
        $this->assertStringEndsWith("Saygılarımızla,\nHâcer İlim ve Kültür Derneği", $mail['text']);
    }

    public function test_admin_sets_words_once_and_every_mail_follows(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ManageSettings::class)
            ->set('data.mail_greeting', ' Selamünaleyküm, ')
            ->set('data.mail_closing', '{Selametle}.')
            ->set('data.mail_greeting_name', MailSalutation::NAME_FIRST)
            ->assertSee('Önizleme: Selamünaleyküm Burak, … Selametle, Hâcer İlim ve Kültür Derneği')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Selamünaleyküm', SiteSettings::get('mail_greeting'));
        $this->assertSame('Selametle', SiteSettings::get('mail_closing'));

        $contact = (new ContactMessageAcknowledged($this->contactMessage('ismail ışık')))->toPhpMailer(new \stdClass);
        $this->assertStringContainsString('Selamünaleyküm İsmail,', $contact['html']);
        $this->assertStringContainsString('Selametle,<br><strong>Hâcer İlim ve Kültür Derneği</strong>', $contact['html']);
        $this->assertStringNotContainsString('Merhaba', $contact['html']);
        $this->assertStringNotContainsString('Saygılarımızla', $contact['text']);

        $decision = DecisionMail::compose([
            'enabled' => true,
            'subject' => 'Onay',
            'body' => '<p>Görüşmek üzere.</p>',
            'button_label' => null,
            'button_url' => null,
            'whatsapp_url' => null,
        ], ApplicationStatus::Approved, 'BURAK GÜL', 'Hadis Dersleri');
        $this->assertStringContainsString('Selamünaleyküm Burak,', $decision['html']);
        $this->assertStringContainsString("Selametle,\nHâcer İlim ve Kültür Derneği", $decision['text']);

        $code = (new AdminLoginVerificationCode('123456', 10))->toPhpMailer(new \stdClass);
        $this->assertStringContainsString('Selamünaleyküm,', $code['html']);
    }

    public function test_names_are_title_cased_in_turkish_without_touching_mixed_case(): void
    {
        $this->assertSame('Burak Gül', MailSalutation::displayName('burak gül'));
        $this->assertSame('İsmail Işık', MailSalutation::displayName('İSMAİL IŞIK'));
        $this->assertSame('McAllister Ali', MailSalutation::displayName('McAllister ali'));
        $this->assertSame('Merhaba,', MailSalutation::greetingText(''));
    }

    private function contactMessage(string $name): ContactMessage
    {
        return ContactMessage::query()->create([
            'name' => $name,
            'email' => 'kisi@example.com',
            'subject' => 'Soru',
            'message' => 'Merhaba',
            'kvkk_accepted' => true,
        ]);
    }
}
