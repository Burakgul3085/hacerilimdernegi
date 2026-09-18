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
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #ece7df;
            color: var(--ink);
            font-family: "Segoe UI", Calibri, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
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
            box-shadow: 0 1px 0 rgba(44, 40, 37, 0.04);
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
            text-decoration: none;
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
            width: min(1100px, calc(100% - 32px));
            margin: 20px auto 40px;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(44, 40, 37, 0.06);
        }

        .brand {
            background: var(--band);
            padding: 18px 22px 14px;
            border-bottom: 3px solid var(--gold);
        }

        .brand h1 {
            margin: 0 0 6px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .brand .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 18px;
            color: var(--muted);
            font-size: 12px;
        }

        .sheet {
            padding: 18px 22px 8px;
        }

        .sheet + .sheet {
            border-top: 1px solid var(--line);
        }

        .sheet-head {
            margin-bottom: 10px;
        }

        .sheet-head h2 {
            margin: 0 0 4px;
            font-size: 15px;
            font-weight: 700;
        }

        .sheet-head p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 640px;
        }

        thead th {
            background: var(--header);
            color: #fffcf8;
            font-weight: 600;
            text-align: left;
            padding: 9px 10px;
            white-space: nowrap;
        }

        tbody td {
            padding: 8px 10px;
            border-top: 1px solid var(--line);
            vertical-align: top;
            word-break: break-word;
        }

        tbody tr:nth-child(even) {
            background: var(--band);
        }

        .footer-note {
            margin: 0;
            padding: 14px 22px 18px;
            color: var(--muted);
            font-size: 11px;
            border-top: 1px solid var(--line);
            background: var(--band);
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .page {
                width: 100%;
                margin: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .sheet {
                break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            tbody tr {
                break-inside: avoid;
            }
        }

        @page {
            margin: 12mm;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <p>Yazdırma önizlemesi · Panel kayıtları değişmez</p>
        <div class="toolbar-actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">Yazdır</button>
            <button type="button" class="btn btn-secondary" onclick="window.close()">Kapat</button>
        </div>
    </div>

    <main class="page">
        <header class="brand">
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
            @endphp

            <section class="sheet">
                <div class="sheet-head">
                    <h2>{{ $sheet['context'] ?? $sheet['name'] }}</h2>
                    @if (filled($sheet['summary'] ?? null))
                        <p>{{ $sheet['summary'] }}</p>
                    @endif
                </div>

                <div class="table-wrap">
                    <table>
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
