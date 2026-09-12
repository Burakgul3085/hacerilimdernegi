<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FlashStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_success_renders_the_refined_notice(): void
    {
        Notification::fake();

        $this->from('/uyelik')
            ->followingRedirects()
            ->post('/uyelik', [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'kvkk_accepted' => '1',
            ])
            ->assertOk()
            ->assertSee('flash-status', false)
            ->assertSee('İletildi')
            ->assertSee('Başvurunuz iletildi')
            ->assertSee('Size de bir onay e-postası gönderdik.');
    }

    public function test_contact_and_newsletter_success_use_the_same_notice(): void
    {
        Notification::fake();

        $this->from('/iletisim')
            ->followingRedirects()
            ->post('/iletisim', [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'message' => 'Merhaba',
                'kvkk_accepted' => '1',
            ])
            ->assertOk()
            ->assertSee('flash-status', false)
            ->assertSee('Mesajınız iletildi');

        $this->from('/')
            ->followingRedirects()
            ->post('/bulten', [
                'email' => 'ayse-bulten@example.com',
            ])
            ->assertOk()
            ->assertSee('flash-status', false)
            ->assertSee('E-bülten kaydınız alındı');
    }

    public function test_event_registration_success_renders_the_refined_notice(): void
    {
        Notification::fake();

        $event = Event::query()->create([
            'title' => 'Dönem açılışı',
            'slug' => 'donem-acilisi-bildirim',
            'registration_open' => true,
            'is_published' => true,
        ]);

        $this->from(route('events.show', $event))
            ->followingRedirects()
            ->post(route('events.register', $event), [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'kvkk_accepted' => '1',
            ])
            ->assertOk()
            ->assertSee('flash-status', false)
            ->assertSee('Katılım başvurunuz alındı');
    }
}
