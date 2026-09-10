<?php

namespace App\Http\Controllers;

use App\Actions\ProcessContactMessage;
use App\Actions\ProcessMembershipApplication;
use App\Enums\ApplicationStatus;
use App\Models\ContactMessage;
use App\Models\MembershipApplication;
use App\Models\NewsletterSubscriber;
use App\Support\FormGuard;
use App\Support\InstagramMedia;
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
        if (FormGuard::isBot($request)) {
            return back()->with('status', 'Başvurunuz iletildi. Size de bir onay e-postası gönderdik.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:2000'],
            'kvkk_accepted' => ['accepted'],
        ]);

        $application = MembershipApplication::query()->create([
            ...$data,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        app(ProcessMembershipApplication::class)->handle($application);

        return back()->with('status', 'Başvurunuz iletildi. Size de bir onay e-postası gönderdik.');
    }

    public function contact(): View
    {
        return view('pages.contact', ['settings' => SiteSettings::all()]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        if (FormGuard::isBot($request)) {
            return back()->with('status', 'Mesajınız iletildi. Teşekkür ederiz.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
            'kvkk_accepted' => ['accepted'],
        ]);

        $message = ContactMessage::query()->create([
            ...$data,
            'kvkk_accepted' => true,
        ]);

        app(ProcessContactMessage::class)->handle($message);

        return back()->with('status', 'Mesajınız iletildi. Teşekkür ederiz.');
    }

    public function donate(): View
    {
        return view('pages.donate', ['settings' => SiteSettings::all()]);
    }

    public function social(): View
    {
        $profileUrl = InstagramMedia::profileUrl((string) SiteSettings::get('instagram'));

        return view('pages.social', [
            'settings' => SiteSettings::all(),
            'posts' => InstagramMedia::collection(SiteSettings::list('instagram_posts')),
            'profileUrl' => $profileUrl,
            'profileUsername' => $profileUrl ? trim((string) parse_url($profileUrl, PHP_URL_PATH), '/') : null,
            'intro' => SiteSettings::socialIntro(),
        ]);
    }

    public function newsletter(Request $request): RedirectResponse
    {
        if (FormGuard::isBot($request)) {
            return back()->with('status', 'E-bülten kaydınız alındı.');
        }

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
