@php
    $sans = "'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif";
    $serif = "Georgia,'Times New Roman',serif";
    $paper = '#fffdf9';
    $canvas = '#efe8dc';
    $line = '#e7dccb';
    $muted = '#6b6359';
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, p, a { font-family: Arial, sans-serif !important; }
        h1 { font-family: Georgia, 'Times New Roman', serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        @media only screen and (max-width: 620px) {
            .mail-shell { width: 100% !important; }
            .mail-pad { padding-left: 22px !important; padding-right: 22px !important; }
            .mail-title { font-size: 23px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:{{ $canvas }};-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
@if (! empty($preheader))
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {{ $preheader }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;
    </div>
@endif

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:{{ $canvas }};margin:0;padding:0;">
    <tr>
        <td align="center" style="padding:32px 12px 40px;">
            <table role="presentation" class="mail-shell" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;margin:0 auto;border-collapse:separate;">

                {{-- Üst kimlik --}}
                <tr>
                    <td align="center" style="padding:0 0 22px;">
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" width="64" height="64" style="display:block;margin:0 auto;border:0;outline:none;text-decoration:none;width:64px;height:64px;border-radius:50%;background-color:{{ $paper }};box-shadow:0 0 0 1px {{ $line }};">
                        <p style="margin:12px 0 0;font-family:{!! $serif !!};font-size:17px;line-height:1.3;color:{{ $primary }};">{{ $siteName }}</p>
                        @if (! empty($tagline))
                            <p style="margin:5px 0 0;font-family:{!! $sans !!};font-size:10px;font-weight:600;letter-spacing:0.22em;text-transform:uppercase;color:{{ $gold }};">{{ $tagline }}</p>
                        @endif
                    </td>
                </tr>

                {{-- Kart --}}
                <tr>
                    <td style="background-color:{{ $paper }};border:1px solid {{ $line }};border-top:4px solid {{ $gold }};border-radius:18px 18px 0 0;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td class="mail-pad" style="padding:38px 44px 10px;">
                                    @if (! empty($eyebrow))
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;">
                                            <tr>
                                                <td style="background-color:#f5eee2;border:1px solid {{ $line }};border-radius:999px;padding:5px 12px;font-family:{!! $sans !!};font-size:10px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:{{ $gold }};">
                                                    {{ $eyebrow }}
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    <h1 class="mail-title" style="margin:0 0 14px;font-family:{!! $serif !!};font-size:27px;line-height:1.25;font-weight:600;color:{{ $primary }};">
                                        {{ $title }}
                                    </h1>

                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
                                        <tr>
                                            <td style="width:44px;height:2px;line-height:2px;font-size:0;background-color:{{ $gold }};">&nbsp;</td>
                                        </tr>
                                    </table>

                                    @if (! empty($greeting))
                                        <p style="margin:0 0 14px;font-family:{!! $sans !!};font-size:16px;line-height:1.6;font-weight:600;color:{{ $primary }};">
                                            {!! $greeting !!}
                                        </p>
                                    @endif

                                    @if (! empty($intro))
                                        <div style="margin:0 0 20px;font-family:{!! $sans !!};font-size:15px;line-height:1.75;color:#4a443e;">
                                            {!! $intro !!}
                                        </div>
                                    @endif

                                    @if (! empty($subjectCard))
                                        @php
                                            $success = ($subjectCard['tone'] ?? 'neutral') === 'success';
                                            $badgeBg = $success ? '#e8f4ec' : '#f1ece4';
                                            $badgeColor = $success ? '#1f6b3f' : '#6b6359';
                                            $badgeBorder = $success ? '#c7e3d1' : $line;
                                        @endphp
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
                                            <tr>
                                                <td style="background-color:#f8f3ea;border:1px solid {{ $line }};border-radius:14px;padding:18px 20px;">
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td valign="middle">
                                                                <p style="margin:0 0 5px;font-family:{!! $sans !!};font-size:10px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:{{ $gold }};">{{ $subjectCard['label'] ?? 'Faaliyet' }}</p>
                                                                <p style="margin:0;font-family:{!! $serif !!};font-size:18px;line-height:1.35;color:{{ $primary }};">{{ $subjectCard['title'] }}</p>
                                                            </td>
                                                            @if (! empty($subjectCard['badge']))
                                                                <td valign="middle" align="right" style="padding-left:12px;white-space:nowrap;">
                                                                    <span style="display:inline-block;background-color:{{ $badgeBg }};border:1px solid {{ $badgeBorder }};border-radius:999px;padding:5px 12px;font-family:{!! $sans !!};font-size:11px;font-weight:700;color:{{ $badgeColor }};">
                                                                        {{ $subjectCard['badge'] }}
                                                                    </span>
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    @if (! empty($highlight))
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
                                            <tr>
                                                <td style="background-color:#f8f3ea;border:1px solid {{ $line }};border-left:4px solid {{ $gold }};border-radius:14px;padding:16px 20px;">
                                                    {!! $highlight !!}
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    @if (! empty($body))
                                        <div style="margin:0 0 10px;font-family:{!! $sans !!};font-size:15px;line-height:1.75;color:#3f3b36;">
                                            {!! $body !!}
                                        </div>
                                    @endif

                                    @php
                                        $allButtons = $buttons ?? [];
                                        if (! empty($ctaUrl) && ! empty($ctaLabel)) {
                                            $allButtons[] = ['label' => $ctaLabel, 'url' => $ctaUrl];
                                        }
                                    @endphp
                                    @if ($allButtons !== [])
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 26px;">
                                            <tr>
                                                @foreach ($allButtons as $button)
                                                    <td style="padding-right:10px;">
                                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                            <tr>
                                                                <td style="border-radius:999px;background-color:{{ $primary }};">
                                                                    <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:13px 24px;font-family:{!! $sans !!};font-size:13px;font-weight:700;letter-spacing:0.03em;text-decoration:none;color:#fffcf8;border-radius:999px;">
                                                                        {{ $button['label'] }}&nbsp;&rarr;
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    @endif

                                    @if (! empty($whatsapp))
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:6px 0 28px;">
                                            <tr>
                                                <td style="background-color:#eef8f1;border:1px solid #cfe9d8;border-radius:16px;padding:20px 22px;">
                                                    <p style="margin:0 0 4px;font-family:{!! $sans !!};font-size:10px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#1f8a4c;">WhatsApp</p>
                                                    <p style="margin:0 0 6px;font-family:{!! $serif !!};font-size:18px;line-height:1.35;color:#123d25;">{{ $whatsapp['title'] }}</p>
                                                    @if (! empty($whatsapp['text']))
                                                        <p style="margin:0 0 16px;font-family:{!! $sans !!};font-size:14px;line-height:1.65;color:#3d5a48;">{{ $whatsapp['text'] }}</p>
                                                    @endif
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td style="border-radius:999px;background-color:#1fa855;">
                                                                <a href="{{ $whatsapp['url'] }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:12px 22px;font-family:{!! $sans !!};font-size:13px;font-weight:700;letter-spacing:0.03em;text-decoration:none;color:#ffffff;border-radius:999px;">
                                                                    Gruba katıl&nbsp;&rarr;
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    @if (! empty($closing))
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 30px;">
                                            <tr>
                                                <td style="border-top:1px solid {{ $line }};padding-top:20px;font-family:{!! $sans !!};font-size:15px;line-height:1.65;color:{{ $primary }};">
                                                    {!! $closing !!}
                                                </td>
                                            </tr>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Alt bilgi --}}
                <tr>
                    <td class="mail-pad" align="center" style="background-color:{{ $primary }};border-radius:0 0 18px 18px;padding:26px 44px 28px;">
                        @if (! empty($socialLinks))
                            <p style="margin:0 0 12px;font-family:{!! $sans !!};font-size:10px;font-weight:700;letter-spacing:0.22em;text-transform:uppercase;color:{{ $gold }};">Bizi takip edin</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 16px;">
                                <tr>
                                    @foreach ($socialLinks as $social)
                                        <td style="padding:0 5px;">
                                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $social['label'] }}" style="text-decoration:none;">
                                                <img src="{{ $social['icon'] }}" alt="{{ $social['label'] }}" width="30" height="30" style="display:block;border:0;outline:none;height:30px;width:30px;border-radius:50%;">
                                            </a>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        @endif

                        @if (! empty($address))
                            <p style="margin:0 0 4px;font-family:{!! $sans !!};font-size:12px;line-height:1.6;color:#d8d0c4;">{{ $address }}</p>
                        @endif
                        @if (! empty($contactLine))
                            <p style="margin:0 0 4px;font-family:{!! $sans !!};font-size:12px;line-height:1.6;color:#d8d0c4;">{!! $contactLine !!}</p>
                        @endif
                        @if (! empty($siteUrl))
                            <p style="margin:8px 0 0;">
                                <a href="{{ $siteUrl }}" style="font-family:{!! $sans !!};font-size:12px;font-weight:600;letter-spacing:0.04em;color:{{ $gold }};text-decoration:none;">
                                    {{ parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl }}
                                </a>
                            </p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:18px 20px 0;">
                        <p style="margin:0;font-family:{!! $sans !!};font-size:11px;line-height:1.6;color:#8c8378;">
                            Bu e-posta {{ $siteName }} tarafından gönderilmiştir.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
