<?php

namespace App\Notifications\Contracts;

interface SendsViaPhpMailer
{
    /**
     * @return array{to: list<string>, subject: string, html: string, text?: string, reply_to?: string, reply_to_name?: string}
     */
    public function toPhpMailer(object $notifiable): array;
}
