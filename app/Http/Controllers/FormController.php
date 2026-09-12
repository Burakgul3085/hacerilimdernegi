<?php

namespace App\Http\Controllers;

use App\Actions\ProcessContactMessage;
use App\Actions\ProcessMembershipApplication;
use App\Enums\ApplicationStatus;
use App\Models\ContactMessage;
use App\Models\MembershipApplication;
use App\Models\NewsletterSubscriber;
use App\Support\FormGuard;
use App\Support\FormStatus;
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
            return FormStatus::redirect('Başvurunuz iletildi. Size de bir onay e-postası gönderdik.', 'membership');
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

        return FormStatus::redirect('Başvurunuz iletildi. Size de bir onay e-postası gönderdik.', 'membership');
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'settings' => SiteSettings::all(),
            'whatsappChatUrl' => SiteSettings::whatsappChatUrl(),
        ]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        if (FormGuard::isBot($request)) {
            return FormStatus::redirect('Mesajınız iletildi. Teşekkür ederiz.', 'contact');
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

        return FormStatus::redirect('Mesajınız iletildi. Teşekkür ederiz.', 'contact');
    }

    public function storeWhatsapp(Request $request): RedirectResponse
    {
        if (FormGuard::isBot($request)) {
            return FormStatus::redirect('Mesajınız WhatsApp’a iletildi.', 'whatsapp');
        }

        $data = $request->validate([
            'wa_name' => ['required', 'string', 'max:120'],
            'wa_phone' => ['nullable', 'string', 'max:40'],
            'wa_message' => ['required', 'string', 'max:1500'],
            'kvkk_accepted' => ['accepted'],
        ]);

        $url = SiteSettings::whatsappComposeUrl(
            $data['wa_name'],
            $data['wa_message'],
            $data['wa_phone'] ?? null,
        );

        if ($url === null) {
            return back()->withErrors([
                'wa_message' => 'WhatsApp hattı henüz tanımlı değil. E-posta formunu kullanabilirsiniz.',
            ]);
        }

        return redirect()->away($url);
    }

    public function donate(): View
    {
        $iban = (string) SiteSettings::get('iban', '');

        return view('pages.donate', [
            'settings' => SiteSettings::all(),
            'ibanDisplay' => SiteSettings::formattedIban($iban),
            'ibanCopy' => SiteSettings::ibanCopyValue($iban),
            'donationPurposes' => SiteSettings::list('donation_purposes'),
            'whatsappChatUrl' => SiteSettings::whatsappChatUrl(),
            'bankDetailsAreDemo' => ((string) SiteSettings::get('bank_details_are_demo', '1')) === '1',
        ]);
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
            return FormStatus::redirect('E-bülten kaydınız alındı.', 'newsletter');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
        ]);

        NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $data['email']],
            ['confirmed_at' => now(), 'ip_address' => $request->ip()],
        );

        return FormStatus::redirect('E-bülten kaydınız alındı.', 'newsletter');
    }
}
