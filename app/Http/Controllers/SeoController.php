<?php

namespace App\Http\Controllers;

use App\Support\MailTemplate;
use App\Support\PublicSitemap;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemap = MailTemplate::publicBaseUrl().'/sitemap.xml';
        $body = <<<TXT
User-agent: *
Allow: /
Disallow: /yonetim
Disallow: /yonetim/
Disallow: /livewire
Disallow: /livewire/
Disallow: /ara
Disallow: /ara/

Sitemap: {$sitemap}

TXT;

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(PublicSitemap $sitemap): Response
    {
        $xml = view('seo.sitemap', [
            'entries' => $sitemap->entries(),
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
