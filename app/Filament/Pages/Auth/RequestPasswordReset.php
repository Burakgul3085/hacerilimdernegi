<?php

namespace App\Filament\Pages\Auth;

use App\Actions\SendAdminPasswordResetLink;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use RuntimeException;
use Throwable;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getExplanationComponent(),
            ]);
    }

    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        try {
            app(SendAdminPasswordResetLink::class)->handle();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Bağlantı gönderilemedi')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Bağlantı gönderilemedi')
                ->body('Mailer ayarlarını kontrol edin veya daha sonra yeniden deneyin.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Sıfırlama bağlantısı gönderildi')
            ->body('Mailer ayarlarındaki alıcı adresini kontrol edin.')
            ->success()
            ->send();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Şifremi unuttum';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Şifremi unuttum';
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('request')
            ->label('Bağlantı gönder')
            ->submit('request');
    }

    protected function getExplanationComponent(): Component
    {
        return Text::make('Bağlantı, Site ayarları → Mailer bölümünde kayıtlı alıcıya gönderilir. Giriş e-postasını yazmanız gerekmez.')
            ->color('gray');
    }
}
