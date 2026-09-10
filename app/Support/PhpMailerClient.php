<?php

namespace App\Support;

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/**
 * Site ayarlarından okunan SMTP kimlik bilgileriyle PHPMailer üzerinden e-posta gönderir.
 */
class PhpMailerClient
{
    public const HOST = 'smtp.gmail.com';

    public const PORT = 587;

    public const ENCRYPTION = 'tls';

    /**
     * @param  list<string>  $to
     * @param  array<string, string>  $embeds  cid => absolute file path
     *
     * @throws RuntimeException
     */
    public function send(
        array $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $replyTo = null,
        ?string $replyToName = null,
        array $embeds = [],
    ): void {
        $config = $this->config();

        if (! $config['ready']) {
            throw new RuntimeException('Mailer ayarları eksik. Yönetim panelinden e-posta ve uygulama şifresini kaydedin.');
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = self::HOST;
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->Port = self::PORT;
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 20;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($config['username'], $config['from_name']);
            $mail->Sender = $config['username'];

            if (filled($replyTo)) {
                $mail->addReplyTo($replyTo, $replyToName ?: $replyTo);
            }

            foreach (array_unique(array_filter($to)) as $address) {
                $mail->addAddress($address);
            }

            foreach ($embeds as $cid => $path) {
                if (is_string($path) && is_file($path)) {
                    $mail->addEmbeddedImage($path, $cid, basename($path));
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?? strip_tags($htmlBody);

            $mail->send();
        } catch (PhpMailerException $exception) {
            throw new RuntimeException(self::deliveryErrorMessage($exception->getMessage()), previous: $exception);
        }
    }

    /**
     * Gmail uygulama şifreleri 4’lü gruplar halinde kopyalanır; boşluklar kimliği bozar.
     */
    public static function normalizeApplicationPassword(string $password): string
    {
        return preg_replace('/\s+/', '', trim($password)) ?? '';
    }

    public static function deliveryErrorMessage(string $phpMailerMessage): string
    {
        if (str_contains($phpMailerMessage, 'Could not authenticate')) {
            return 'Gmail SMTP kimliği reddedildi. Site ayarları → Mailer bölümünde Gmail adresi ve 16 karakterlik uygulama şifresini kaydedin. Normal Gmail şifresi çalışmaz.';
        }

        return 'E-posta gönderilemedi: '.$phpMailerMessage;
    }

    /**
     * Doğrulama kodunun gideceği adres: paneldeki alıcı alanı, yoksa giriş yapan kullanıcının e-postası.
     */
    public function otpRecipient(?string $fallback = null): string
    {
        $configured = trim((string) SiteSettings::get('mailer_otp_to', ''));

        if ($configured !== '') {
            return $configured;
        }

        return (string) $fallback;
    }

    public function isConfigured(): bool
    {
        return $this->config()['ready'];
    }

    /**
     * @return array{ready: bool, username: string, password: string, from_name: string}
     */
    private function config(): array
    {
        $username = trim((string) SiteSettings::get('mailer_username', ''));
        $password = self::normalizeApplicationPassword((string) SiteSettings::secret('mailer_password'));
        $fromName = trim((string) SiteSettings::get('mailer_from_name', SiteSettings::get('site_name')));

        return [
            'ready' => $username !== '' && $password !== '',
            'username' => $username,
            'password' => $password,
            'from_name' => $fromName !== '' ? $fromName : 'Hâcer İlim Yönetim',
        ];
    }
}
