<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\ContactMessage;
use App\Models\MembershipApplication;
use App\Models\NewsletterSubscriber;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormController extends Controller
{
    public function membership(): View
    {
        return view('pages.membership');
    }

    public function storeMembership(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:2000'],
            'kvkk_accepted' => ['accepted'],
        ]);

        MembershipApplication::query()->create([
            ...$data,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        return back()->with('status', 'Başvurunuz alındı. Dernek yönetimi sizinle iletişime geçecektir.');
    }

    public function contact(): View
    {
        return view('pages.contact', ['settings' => SiteSettings::all()]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
            'kvkk_accepted' => ['accepted'],
        ]);

        ContactMessage::query()->create([
            ...$data,
            'kvkk_accepted' => true,
        ]);

        return back()->with('status', 'Mesajınız iletildi. Teşekkür ederiz.');
    }

    public function donate(): View
    {
        return view('pages.donate', ['settings' => SiteSettings::all()]);
    }

    public function live(): View
    {
        return view('pages.live', ['settings' => SiteSettings::all()]);
    }

    public function legal(string $type): View
    {
        $map = [
            'kvkk' => ['title' => 'KVKK aydınlatma metni', 'key' => 'kvkk_text'],
            'gizlilik' => ['title' => 'Gizlilik politikası', 'key' => 'privacy_text'],
            'cerezler' => ['title' => 'Çerez politikası', 'key' => 'cookie_text'],
        ];

        abort_unless(isset($map[$type]), 404);

        return view('pages.legal', [
            'title' => $map[$type]['title'],
            'body' => SiteSettings::get($map[$type]['key']),
        ]);
    }

    public function newsletter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
        ]);

        NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $data['email']],
            ['confirmed_at' => now(), 'ip_address' => $request->ip()],
        );

        return back()->with('status', 'E-bülten kaydınız alındı.');
    }
}
