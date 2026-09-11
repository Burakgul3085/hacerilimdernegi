<?php

namespace Tests\Feature;

use App\Actions\SendAdminPasswordResetLink;
use App\Enums\UserRole;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Models\User;
use App\Notifications\AdminPasswordResetLink;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSettings::put('mailer_username', 'gonderici@gmail.com');
        SiteSettings::put('mailer_password', 'uygulama-sifresi');
        SiteSettings::put('mailer_otp_to', 'alici@hacer.org');
        SiteSettings::put('site_name', 'Hâcer İlim ve Kültür Derneği');
    }

    public function test_login_page_shows_home_and_forgot_password_links(): void
    {
        $this->get('/yonetim/login')
            ->assertOk()
            ->assertSee('Ana sayfaya dön')
            ->assertSee('Şifremi unuttum')
            ->assertSee('hacer-login-links', false)
            ->assertSee(route('home'), false)
            ->assertSee('/yonetim/password-reset/request', false);
    }

    public function test_login_page_renders_the_background_video(): void
    {
        $this->get('/yonetim/login')
            ->assertOk()
            ->assertSee('hacer-login-bg', false)
            ->assertSee('/videos/admin-login-bg.mp4', false)
            ->assertSee('autoplay', false);

        $this->get('/yonetim/password-reset/request')
            ->assertOk()
            ->assertDontSee('hacer-login-bg', false)
            ->assertDontSee('/videos/admin-login-bg.mp4', false);
    }

    public function test_reset_link_is_sent_to_the_mailer_recipient_not_the_login_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email' => 'info@hacerilimvekulturdernegi.org',
            'role' => UserRole::SuperAdmin,
        ]);

        app(SendAdminPasswordResetLink::class)->handle();

        Notification::assertSentTo(
            $admin,
            AdminPasswordResetLink::class,
            function (AdminPasswordResetLink $notification) use ($admin): bool {
                $payload = $notification->toPhpMailer($admin);

                return $payload['to'] === ['alici@hacer.org']
                    && ! in_array($admin->email, $payload['to'], true)
                    && str_contains($payload['html'], 'Şifreyi yenile');
            },
        );
    }

    public function test_reset_link_follows_an_updated_mailer_recipient(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        SiteSettings::put('mailer_otp_to', 'yeni-alici@hacer.org');

        app(SendAdminPasswordResetLink::class)->handle();

        Notification::assertSentTo(
            $admin,
            AdminPasswordResetLink::class,
            function (AdminPasswordResetLink $notification) use ($admin): bool {
                return $notification->toPhpMailer($admin)['to'] === ['yeni-alici@hacer.org'];
            },
        );
    }

    public function test_valid_token_replaces_the_super_admin_password(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'password' => Hash::make('eski-sifre-99'),
            'role' => UserRole::SuperAdmin,
        ]);

        app(SendAdminPasswordResetLink::class)->handle();

        $url = null;
        Notification::assertSentTo($admin, AdminPasswordResetLink::class, function (AdminPasswordResetLink $notification) use (&$url): bool {
            $url = $notification->url;

            return true;
        });

        $this->assertNotNull($url);
        $this->assertNotSame('', parse_url($url, PHP_URL_QUERY));

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        Livewire::test(ResetPassword::class, [
            'email' => $query['email'] ?? $admin->email,
            'token' => $query['token'] ?? '',
        ])
            ->set('password', 'yeni-sifre-99')
            ->set('passwordConfirmation', 'yeni-sifre-99')
            ->call('resetPassword');

        $admin->refresh();

        $this->assertTrue(Hash::check('yeni-sifre-99', $admin->password));
        $this->assertFalse(Hash::check('eski-sifre-99', $admin->password));
    }

    public function test_invalid_token_does_not_change_the_password(): void
    {
        $admin = User::factory()->create([
            'password' => Hash::make('eski-sifre-99'),
            'role' => UserRole::SuperAdmin,
        ]);

        Livewire::test(ResetPassword::class, [
            'email' => $admin->email,
            'token' => 'gecersiz-token',
        ])
            ->set('password', 'yeni-sifre-99')
            ->set('passwordConfirmation', 'yeni-sifre-99')
            ->call('resetPassword');

        $admin->refresh();

        $this->assertTrue(Hash::check('eski-sifre-99', $admin->password));
    }

    public function test_request_page_dispatches_the_reset_mail(): void
    {
        Notification::fake();

        User::factory()->create(['role' => UserRole::SuperAdmin]);

        Livewire::test(RequestPasswordReset::class)
            ->call('request');

        Notification::assertSentTo(
            User::query()->where('role', UserRole::SuperAdmin)->first(),
            AdminPasswordResetLink::class,
        );
    }
}
