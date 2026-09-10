<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Notifications\AdminLoginVerificationCode;
use App\Notifications\ContactMessageAcknowledged;
use App\Notifications\ContactMessageReceivedForAdmin;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSettings::put('site_name', 'Hâcer İlim ve Kültür Derneği');
        SiteSettings::put('tagline', 'Gaziantep’te ilim, sohbet ve kültür');
        SiteSettings::put('address', 'Şehitkamil / Gaziantep');
        SiteSettings::put('email', 'info@hacerilimvekulturdernegi.org');
        SiteSettings::put('domain', 'hacerilimvekulturdernegi.org');
    }

    public function test_brand_template_includes_identity_and_footer(): void
    {
        $html = MailTemplate::render([
            'title' => 'Örnek başlık',
            'eyebrow' => 'Örnek',
            'greeting' => 'Merhaba,',
            'intro' => '<p>İçerik</p>',
        ]);

        $this->assertStringContainsString('Hâcer İlim ve Kültür Derneği', $html);
        $this->assertStringContainsString('Örnek başlık', $html);
        $this->assertStringContainsString('Şehitkamil / Gaziantep', $html);
        $this->assertStringContainsString('hacerilimvekulturdernegi.org', $html);
        $this->assertStringContainsString('cid:hacer_mail_logo', $html);
        $this->assertStringContainsString('width="48"', $html);
        $this->assertMatchesRegularExpression('/#8[Aa]7[Aa]62/', $html);
        $this->assertArrayHasKey('hacer_mail_logo', MailTemplate::embeddedImages());
    }

    public function test_social_icons_appear_when_channels_are_configured(): void
    {
        SiteSettings::put('telegram', 'https://t.me/example');
        SiteSettings::put('whatsapp', 'https://whatsapp.com/channel/example');
        SiteSettings::put('twitter', 'https://x.com/example');

        $html = MailTemplate::render([
            'title' => 'Sosyal test',
            'intro' => '<p>İçerik</p>',
        ]);

        $this->assertStringContainsString('https://t.me/example', $html);
        $this->assertStringContainsString('cid:hacer_mail_telegram', $html);
        $this->assertStringContainsString('cid:hacer_mail_whatsapp', $html);
        $this->assertStringContainsString('cid:hacer_mail_twitter', $html);
    }

    public function test_acknowledgement_mail_uses_the_brand_template(): void
    {
        SiteSettings::put('telegram', 'https://t.me/example');
        SiteSettings::put('whatsapp', 'https://whatsapp.com/channel/example');
        SiteSettings::put('twitter', 'https://x.com/example');

        $message = ContactMessage::query()->create([
            'name' => 'Ayşe',
            'email' => 'ayse@example.com',
            'subject' => 'Soru',
            'message' => 'Merhaba',
            'kvkk_accepted' => true,
        ]);

        $html = (new ContactMessageAcknowledged($message))->toPhpMailer($message)['html'];

        $this->assertStringContainsString('Mesajınız bize ulaştı', $html);
        $this->assertStringContainsString('Merhaba Ayşe', $html);
        $this->assertStringContainsString('width="48"', $html);
        $this->assertStringContainsString('border-radius:50%', $html);
        $this->assertStringContainsString('max-width:520px', $html);
        $this->assertStringContainsString('Bizi takip edin', $html);
        $this->assertStringContainsString('cid:hacer_mail_telegram', $html);
    }

    public function test_verification_code_mail_highlights_the_code(): void
    {
        $user = new class
        {
            public string $email = 'admin@example.com';
        };

        SiteSettings::put('mailer_otp_to', 'admin@example.com');

        $html = (new AdminLoginVerificationCode('4281', 10))->toPhpMailer($user)['html'];

        $this->assertStringContainsString('4281', $html);
        $this->assertStringContainsString('Doğrulama kodunuz', $html);
        $this->assertStringContainsString('letter-spacing:0.42em', $html);
    }

    public function test_admin_contact_notification_lists_sender_details(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'Ali Veli',
            'email' => 'ali@example.com',
            'phone' => '05320000000',
            'subject' => 'Program',
            'message' => 'Bilgi rica ederim.',
            'kvkk_accepted' => true,
        ]);

        SiteSettings::put('mailer_otp_to', 'yonetim@hacer.org');

        $html = (new ContactMessageReceivedForAdmin($message))->toPhpMailer($message)['html'];

        $this->assertStringContainsString('Ali Veli', $html);
        $this->assertStringContainsString('ali@example.com', $html);
        $this->assertStringContainsString('Bilgi rica ederim.', $html);
        $this->assertStringContainsString('Panele git', $html);
    }
}
