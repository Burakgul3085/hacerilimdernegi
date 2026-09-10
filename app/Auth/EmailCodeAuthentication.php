<?php

namespace App\Auth;

use App\Notifications\AdminLoginVerificationCode;
use Closure;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;
use SensitiveParameter;
use Throwable;

/**
 * Filament e-posta MFA sağlayıcısı: 4 haneli kod, PHPMailer bildirimi, zorunlu doğrulama.
 */
class EmailCodeAuthentication extends EmailAuthentication
{
    public function __construct()
    {
        $this->codeExpiryMinutes(10);
        $this->codeNotification(AdminLoginVerificationCode::class);
        $this->generateCodesUsing(fn (): string => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT));
    }

    public function getLoginFormLabel(): string
    {
        return 'E-posta doğrulama kodu';
    }

    public function sendCode(HasEmailAuthentication $user): bool
    {
        try {
            return parent::sendCode($user);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Doğrulama kodu gönderilemedi')
                ->body($exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Mailer ayarlarını kontrol edin veya daha sonra yeniden deneyin.')
                ->danger()
                ->send();

            return false;
        }
    }

    /**
     * @return array<Component | Action>
     */
    public function getManagementSchemaComponents(): array
    {
        return [
            Text::make('Yönetim paneline her girişte 4 haneli bir kod, Site ayarları → Mailer bölümünde tanımlanan adrese PHPMailer ile gönderilir.')
                ->color('gray'),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getActions(): array
    {
        return [];
    }

    /**
     * @return array<Component | Action>
     */
    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            View::make('filament.auth.otp-intro'),
            OneTimeCodeInput::make('code')
                ->label('Doğrulama kodu')
                ->length(4)
                ->extraAttributes(['class' => 'hacer-otp'])
                ->extraFieldWrapperAttributes(['class' => 'hacer-otp-field'])
                ->validationAttribute('doğrulama kodu')
                ->belowContent(Action::make('resend')
                    ->label('Kodu yeniden gönder')
                    ->link()
                    ->action(function () use ($user): void {
                        if (! $this->sendCode($user)) {
                            Notification::make()
                                ->title('Çok sık deneme veya gönderim hatası')
                                ->body('Lütfen kısa bir süre sonra yeniden deneyin veya mailer ayarlarını kontrol edin.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Kod yeniden gönderildi')
                            ->success()
                            ->send();
                    }))
                ->required()
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($user): void {
                        if (is_string($value) && $this->verifyCode($value, $user)) {
                            return;
                        }

                        $fail('Doğrulama kodu geçersiz veya süresi dolmuş.');
                    };
                }),
        ];
    }
}
