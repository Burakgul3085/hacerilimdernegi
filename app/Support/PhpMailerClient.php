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

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?? strip_tags($htmlBody);

            $mail->send();
        } catch (PhpMailerException $exception) {
            throw new RuntimeException('E-posta gönderilemedi: '.$exception->getMessage(), previous: $exception);
        }
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
        $password = (string) SiteSettings::secret('mailer_password');
        $fromName = trim((string) SiteSettings::get('mailer_from_name', SiteSettings::get('site_name')));

        return [
            'ready' => $username !== '' && $password !== '',
            'username' => $username,
            'password' => $password,
            'from_name' => $fromName !== '' ? $fromName : 'Hâcer İlim Yönetim',
        ];
    }
}
