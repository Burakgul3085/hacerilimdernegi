<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td { font-family: Georgia, 'Times New Roman', serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#e6ddd0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
@if (! empty($preheader))
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {{ $preheader }}
    </div>
@endif

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#e6ddd0;margin:0;padding:0;">
    <tr>
        <td align="center" style="padding:24px 10px;">
            <table role="presentation" width="520" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:520px;margin:0 auto;border-collapse:separate;">

                <tr>
                    <td style="height:4px;line-height:4px;font-size:0;background-color:{{ $gold }};border-radius:14px 14px 0 0;">&nbsp;</td>
                </tr>

                <tr>
                    <td style="background-color:{{ $primary }};padding:14px 18px 13px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td valign="middle" style="padding-right:12px;">
                                    <p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:16px;line-height:1.3;color:#fffcf8;">
                                        {{ $siteName }}
                                    </p>
                                    @if (! empty($tagline))
                                        <p style="margin:4px 0 0;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:{{ $gold }};">
                                            {{ $tagline }}
                                        </p>
                                    @endif
                                </td>
                                <td valign="middle" align="right" width="52" style="width:52px;">
                                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" width="48" height="48" style="display:block;border:0;outline:none;text-decoration:none;width:48px;height:48px;border-radius:50%;background-color:#fffcf8;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="background-color:#fffcf8;padding:22px 22px 8px;border-left:1px solid #d8ccba;border-right:1px solid #d8ccba;">
                        @if (! empty($eyebrow))
                            <p style="margin:0 0 8px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:{{ $gold }};">
                                {{ $eyebrow }}
                            </p>
                        @endif

                        <h1 style="margin:0 0 10px;font-family:Georgia,'Times New Roman',serif;font-size:22px;line-height:1.25;font-weight:600;color:{{ $primary }};">
                            {{ $title }}
                        </h1>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;">
                            <tr>
                                <td style="width:36px;height:2px;line-height:2px;font-size:0;background-color:{{ $gold }};">&nbsp;</td>
                                <td style="height:2px;line-height:2px;font-size:0;background-color:#efe6d7;">&nbsp;</td>
                            </tr>
                        </table>

                        @if (! empty($greeting))
                            <p style="margin:0 0 8px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:{{ $primary }};">
                                {!! $greeting !!}
                            </p>
                        @endif

                        @if (! empty($intro))
                            <div style="margin:0 0 16px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#5c554e;">
                                {!! $intro !!}
                            </div>
                        @endif

                        @if (! empty($highlight))
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;">
                                <tr>
                                    <td style="background-color:#f7f1e6;border:1px solid #e4d8c4;border-left:4px solid {{ $gold }};border-radius:12px;padding:14px 16px;">
                                        {!! $highlight !!}
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if (! empty($body))
                            <div style="margin:0 0 16px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#3f3b36;">
                                {!! $body !!}
                            </div>
                        @endif

                        @if (! empty($ctaUrl) && ! empty($ctaLabel))
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:2px 0 18px;">
                                <tr>
                                    <td style="border-radius:999px;background-color:{{ $primary }};">
                                        <a href="{{ $ctaUrl }}" style="display:inline-block;padding:10px 18px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-decoration:none;color:#fffcf8;">
                                            {{ $ctaLabel }} →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if (! empty($closing))
                            <p style="margin:0 0 6px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:{{ $primary }};">
                                {!! $closing !!}
                            </p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="background-color:{{ $primary }};border-radius:0 0 14px 14px;padding:16px 22px 18px;">
                        @if (! empty($socialLinks))
                            <p style="margin:0 0 10px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:{{ $gold }};">
                                Bizi takip edin
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 14px;">
                                <tr>
                                    @foreach ($socialLinks as $social)
                                        <td style="padding-right:8px;">
                                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $social['label'] }}" style="text-decoration:none;">
                                                <img src="{{ $social['icon'] }}" alt="{{ $social['label'] }}" width="32" height="32" style="display:block;border:0;outline:none;height:32px;width:32px;border-radius:50%;">
                                            </a>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        @endif

                        @if (! empty($address))
                            <p style="margin:0 0 4px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:11px;line-height:1.55;color:#d8d0c4;">
                                {{ $address }}
                            </p>
                        @endif
                        @if (! empty($contactLine))
                            <p style="margin:0 0 8px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:11px;line-height:1.55;color:#d8d0c4;">
                                {!! $contactLine !!}
                            </p>
                        @endif

                        @if (! empty($siteUrl))
                            <p style="margin:0 0 10px;">
                                <a href="{{ $siteUrl }}" style="font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:11px;color:{{ $gold }};text-decoration:underline;">
                                    {{ parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl }}
                                </a>
                            </p>
                        @endif

                        <p style="margin:0;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:10px;line-height:1.5;color:#7a746c;">
                            Bu e-posta {{ $siteName }} tarafından otomatik olarak gönderilmiştir.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
