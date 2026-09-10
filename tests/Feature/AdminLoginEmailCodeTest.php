<?php

namespace Tests\Feature;

use App\Auth\EmailCodeAuthentication;
use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminLoginVerificationCode;
use App\Notifications\Channels\PhpMailerChannel;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLoginEmailCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailer_settings_are_saved_and_password_is_encrypted(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ManageSettings::class)
            ->set('data.mailer_username', 'dernek@gmail.com')
            ->set('data.mailer_password', 'abcd-efgh-ijkl-mnop')
            ->set('data.mailer_otp_to', 'guvenlik@hacerilimvekulturdernegi.org')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('dernek@gmail.com', SiteSettings::get('mailer_username'));
        $this->assertSame('guvenlik@hacerilimvekulturdernegi.org', SiteSettings::get('mailer_otp_to'));
        $this->assertSame('abcd-efgh-ijkl-mnop', SiteSettings::secret('mailer_password'));
        $this->assertNotSame(
            'abcd-efgh-ijkl-mnop',
            Setting::query()->where('key', 'mailer_password')->value('value'),
        );
    }

    public function test_blank_mailer_password_keeps_the_existing_secret(): void
    {
        SiteSettings::put('mailer_password', 'eski-uygulama-sifresi');

        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ManageSettings::class)
            ->set('data.mailer_username', 'dernek@gmail.com')
            ->set('data.mailer_password', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('eski-uygulama-sifresi', SiteSettings::secret('mailer_password'));
    }

    public function test_mailer_settings_strip_spaces_from_the_application_password(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ManageSettings::class)
            ->set('data.mailer_username', 'dernek@gmail.com')
            ->set('data.mailer_password', 'abcd efgh ijkl mnop')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('abcdefghijklmnop', SiteSettings::secret('mailer_password'));
    }

    public function test_email_authentication_generates_a_four_digit_code(): void
    {
        $provider = EmailCodeAuthentication::make();

        for ($i = 0; $i < 20; $i++) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $provider->generateCode());
        }
    }

    public function test_login_code_notification_is_dispatched_to_the_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $provider = EmailCodeAuthentication::make();

        $this->assertTrue($provider->sendCode($user));

        Notification::assertSentTo($user, AdminLoginVerificationCode::class, function (AdminLoginVerificationCode $notification): bool {
            return preg_match('/^\d{4}$/', $notification->code) === 1
                && $notification->codeExpiryMinutes === 10;
        });
    }

    public function test_valid_code_from_notification_passes_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $provider = EmailCodeAuthentication::make();
        $provider->sendCode($user);

        $code = null;
        Notification::assertSentTo($user, AdminLoginVerificationCode::class, function (AdminLoginVerificationCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $this->assertNotNull($code);
        $this->assertTrue($provider->verifyCode($code, $user));
        $this->assertFalse($provider->verifyCode('0000', $user));
    }

    public function test_phpmailer_channel_sends_to_the_configured_otp_recipient(): void
    {
        SiteSettings::put('mailer_otp_to', 'alici@hacer.org');

        $this->mock(PhpMailerClient::class, function ($mock): void {
            $mock->shouldReceive('otpRecipient')
                ->once()
                ->with('yonetici@example.com')
                ->andReturn('alici@hacer.org');

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (array $to, string $subject, string $html): bool {
                    return $to === ['alici@hacer.org']
                        && str_contains($subject, '1234')
                        && str_contains($html, '1234');
                });
        });

        $user = User::factory()->make([
            'email' => 'yonetici@example.com',
            'role' => UserRole::SuperAdmin,
        ]);

        $notification = new AdminLoginVerificationCode('1234', 10);
        app(PhpMailerChannel::class)->send($user, $notification);
    }

    public function test_panel_users_always_require_email_authentication(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);

        $this->assertTrue($user->hasEmailAuthentication());
    }

    public function test_login_challenge_renders_animated_code_inputs(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertSee('Kimliğinizi doğrulayın')
            ->assertSee('E-postanıza gelen 4 haneli kodu kutulara yazın.')
            ->assertSee('hacer-otp', false)
            ->assertSee('hacer-otp-pop', false)
            ->assertSee('fi-one-time-code-input-digit', false);
    }
}
