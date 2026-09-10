<?php

namespace Tests\Unit;

use App\Support\PhpMailerClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhpMailerClientTest extends TestCase
{
    #[DataProvider('applicationPasswords')]
    public function test_strips_whitespace_from_gmail_application_passwords(string $password, string $normalized): void
    {
        $this->assertSame($normalized, PhpMailerClient::normalizeApplicationPassword($password));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function applicationPasswords(): array
    {
        return [
            'grouped copy' => ['abcd efgh ijkl mnop', 'abcdefghijklmnop'],
            'leading and trailing space' => ['  abcdabcdabcdabcd  ', 'abcdabcdabcdabcd'],
            'already compact' => ['abcdefghijklmnop', 'abcdefghijklmnop'],
        ];
    }

    public function test_explains_gmail_authentication_failures_in_turkish(): void
    {
        $this->assertSame(
            'Gmail SMTP kimliği reddedildi. Site ayarları → Mailer bölümünde Gmail adresi ve 16 karakterlik uygulama şifresini kaydedin. Normal Gmail şifresi çalışmaz.',
            PhpMailerClient::deliveryErrorMessage('SMTP Error: Could not authenticate.'),
        );
    }

    public function test_keeps_other_smtp_failures_prefixed(): void
    {
        $this->assertSame(
            'E-posta gönderilemedi: SMTP connect() failed.',
            PhpMailerClient::deliveryErrorMessage('SMTP connect() failed.'),
        );
    }
}
