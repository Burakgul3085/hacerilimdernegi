<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FlashStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_success_renders_the_notice_inside_the_form(): void
    {
        Notification::fake();

        $this->from('/uyelik')
            ->post('/uyelik', [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect('/uyelik#form-status-membership')
            ->assertSessionHas('status', 'Başvurunuz iletildi. Size de bir onay e-postası gönderdik.')
            ->assertSessionHas('status_context', 'membership');

        $this->withSession([
            'status' => 'Başvurunuz iletildi. Size de bir onay e-postası gönderdik.',
            'status_context' => 'membership',
        ])
            ->get('/uyelik')
            ->assertSeeInOrder([
                'Üyelik / gönüllü',
                'form-status-membership',
                'Başvurunuz iletildi',
                'Ad soyad',
            ]);
    }

    public function test_contact_success_renders_the_notice_inside_the_email_form(): void
    {
        Notification::fake();

        $this->from('/iletisim')
            ->post('/iletisim', [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'message' => 'Merhaba',
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect('/iletisim#form-status-contact')
            ->assertSessionHas('status_context', 'contact');

        $this->withSession([
            'status' => 'Mesajınız iletildi. Teşekkür ederiz.',
            'status_context' => 'contact',
        ])
            ->get('/iletisim')
            ->assertSeeInOrder([
                'İletişim',
                'E-posta ile yazın',
                'form-status-contact',
                'Mesajınız iletildi',
                'Gönder',
            ]);
    }

    public function test_newsletter_success_renders_the_notice_in_the_footer(): void
    {
        $this->from('/')
            ->post('/bulten', [
                'email' => 'ayse-bulten@example.com',
            ])
            ->assertRedirect(url('/').'#form-status-newsletter')
            ->assertSessionHas('status_context', 'newsletter');

        $this->withSession([
            'status' => 'E-bülten kaydınız alındı.',
            'status_context' => 'newsletter',
        ])
            ->get('/')
            ->assertSeeInOrder([
                'id="main"',
                'form-status-newsletter',
                'E-bülten kaydınız alındı',
                'footer-newsletter',
            ]);
    }

    public function test_event_registration_success_renders_the_notice_inside_the_form(): void
    {
        Notification::fake();

        $event = Event::query()->create([
            'title' => 'Dönem açılışı',
            'slug' => 'donem-acilisi-bildirim',
            'registration_open' => true,
            'is_published' => true,
        ]);

        $this->from(route('events.show', $event))
            ->post(route('events.register', $event), [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect(route('events.show', $event).'#form-status-event')
            ->assertSessionHas('status_context', 'event');

        $this->withSession([
            'status' => 'Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.',
            'status_context' => 'event',
        ])
            ->get(route('events.show', $event))
            ->assertSeeInOrder([
                'Katılım başvurusu',
                'form-status-event',
                'Katılım başvurunuz alındı',
                'Ad soyad',
            ]);
    }
}
