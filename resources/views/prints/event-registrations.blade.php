<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $organization }} — {{ $label }}</title>
    <style>
        :root {
            --ink: #2c2825;
            --muted: #6b6560;
            --band: #f3eee4;
            --header: #7a6b55;
            --line: #e6dfd3;
            --paper: #fffcf8;
            --gold: #d4cbbe;
            --gold-soft: #ebe4d8;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            background: #e8e2d8;
            color: var(--ink);
            font-family: "Segoe UI", Calibri, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .toolbar p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--header);
            color: #fffcf8;
        }

        .btn-secondary {
            background: var(--band);
            color: var(--ink);
        }

        .page {
            width: min(1200px, calc(100% - 32px));
            margin: 20px auto 40px;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 12px 36px rgba(44, 40, 37, 0.08);
        }

        .brand {
            background: linear-gradient(180deg, #f7f2ea 0%, var(--band) 100%);
            padding: 20px 24px 16px;
            border-bottom: 3px solid var(--gold);
        }

        .brand-kicker {
            margin: 0 0 4px;
            color: var(--header);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .brand h1 {
            margin: 0 0 8px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .brand .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 14px;
            color: var(--muted);
            font-size: 12px;
        }

        .brand .meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .brand .meta span:not(:last-child)::after {
            content: "";
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: var(--gold);
            margin-left: 14px;
        }

        .sheet {
            padding: 16px 24px 12px;
        }

        .sheet + .sheet {
            border-top: 1px solid var(--line);
        }

        .sheet-head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 6px 16px;
            margin-bottom: 10px;
        }

        .sheet-head h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }

        .sheet-head p {
            margin: 0;
            color: var(--muted);
            font-size: 11px;
        }

        .table-wrap {
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.data thead th {
            background: var(--header);
            color: #fffcf8;
            font-weight: 600;
            text-align: left;
            padding: 8px 8px;
            border-right: 1px solid rgba(255, 252, 248, 0.12);
            vertical-align: middle;
        }

        table.data thead th:last-child {
            border-right: 0;
        }

        table.data tbody td {
            padding: 7px 8px;
            border-top: 1px solid var(--line);
            border-right: 1px solid var(--gold-soft);
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        table.data tbody td:last-child {
            border-right: 0;
        }

        table.data tbody tr:nth-child(even) {
            background: var(--band);
        }

        table.data tbody tr:hover {
            background: #f0ebe2;
        }

        /* Sütun sayısına göre sıkılık */
        .density-comfortable table.data {
            font-size: 11.5px;
        }

        .density-compact table.data {
            font-size: 9.5px;
        }

        .density-compact table.data thead th,
        .density-compact table.data tbody td {
            padding: 5px 5px;
        }

        .density-dense table.data {
            font-size: 8px;
            line-height: 1.25;
        }

        .density-dense table.data thead th,
        .density-dense table.data tbody td {
            padding: 3px 4px;
        }

        .sheet-index .table-wrap {
            max-width: 560px;
        }

        .sheet-index table.data {
            table-layout: auto;
            font-size: 11px;
        }

        .footer-note {
            margin: 0;
            padding: 12px 24px 16px;
            color: var(--muted);
            font-size: 10.5px;
            border-top: 1px solid var(--line);
            background: var(--band);
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            html, body {
                background: #fff !important;
            }

            .toolbar {
                display: none !important;
            }

            .page {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: visible !important;
            }

            .brand {
                padding: 0 0 8px;
                border-bottom-width: 2px;
                background: var(--band) !important;
            }

            .brand h1 {
                font-size: 16px;
            }

            .sheet {
                padding: 10px 0 6px;
                break-inside: auto;
                page-break-inside: auto;
            }

            .sheet + .sheet {
                border-top: 0;
                padding-top: 12px;
            }

            .sheet-index {
                margin-bottom: 4px;
            }

            .table-wrap {
                border-radius: 0;
                overflow: visible;
            }

            table.data thead {
                display: table-header-group;
            }

            table.data thead th {
                background: var(--header) !important;
                color: #fffcf8 !important;
            }

            table.data tbody tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            table.data tbody tr:nth-child(even) {
                background: var(--band) !important;
            }

            table.data tbody tr:hover {
                background: inherit;
            }

            .footer-note {
                padding: 8px 0 0;
                background: transparent !important;
                border-top: 1px solid var(--line);
            }
        }
    </style>
</head>
<body>
    @php
        $maxColumns = 0;

        foreach ($sheets as $sheet) {
            $maxColumns = max($maxColumns, count($sheet['rows'][0] ?? []));
        }

        $density = match (true) {
            $maxColumns >= 11 => 'density-dense',
            $maxColumns >= 7 => 'density-compact',
            default => 'density-comfortable',
        };
    @endphp

    <div class="toolbar">
        <p>Yazdırma önizlemesi · Varsayılan: yatay A4 · Panel kayıtları değişmez</p>
        <div class="toolbar-actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">Yazdır</button>
            <button type="button" class="btn btn-secondary" onclick="window.close()">Kapat</button>
        </div>
    </div>

    <main class="page {{ $density }}">
        <header class="brand">
            <p class="brand-kicker">Program kayıt dökümü</p>
            <h1>{{ $organization }}</h1>
            <div class="meta">
                <span>{{ $label }}</span>
                <span>{{ $documentKind }}</span>
                <span>{{ $generatedAt }}</span>
            </div>
        </header>

        @foreach ($sheets as $sheet)
            @php
                $rows = $sheet['rows'] ?? [];
                $headers = $rows[0] ?? [];
                $body = array_slice($rows, 1);
                $isIndex = ($sheet['context'] ?? $sheet['name'] ?? '') === 'İçindekiler'
                    || ($sheet['name'] ?? '') === 'İçindekiler';
            @endphp

            <section class="sheet {{ $isIndex ? 'sheet-index' : '' }}">
                <div class="sheet-head">
                    <h2>{{ $sheet['context'] ?? $sheet['name'] }}</h2>
                    @if (filled($sheet['summary'] ?? null))
                        <p>{{ $sheet['summary'] }}</p>
                    @endif
                </div>

                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                @foreach ($headers as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($body as $row)
                                <tr>
                                    @foreach ($headers as $index => $header)
                                        <td>{{ $row[$index] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(1, count($headers)) }}">Bu bölümde kayıt yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        <p class="footer-note">Anlık görüntü · Asıl kayıtlar Hâcer panelinde tutulur; bu sayfa sunucuda saklanmaz.</p>
    </main>

    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                window.print();
            }, 250);
        });
    </script>
</body>
</html>
