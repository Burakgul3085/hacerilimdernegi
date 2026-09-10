<?php

namespace App\Actions;

use App\Models\NewsletterSubscriber;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

/**
 * E-bülten abonelerini kurumsal Excel (xlsx) raporu olarak üretir.
 */
class ExportNewsletterSubscribers
{
    /**
     * @return array{filename: string, contents: string}
     */
    public function handle(): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Excel dışa aktarımı için PHP zip eklentisi gerekir.');
        }

        $subscribers = NewsletterSubscriber::query()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['email', 'confirmed_at', 'created_at']);

        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $contents = $this->buildWorkbook($siteName, $subscribers);
        $filename = 'Hacer-E-Bulten-Aboneler-'.now()->format('Y-m-d').'.xlsx';

        return [
            'filename' => $filename,
            'contents' => $contents,
        ];
    }

    /**
     * @param  Collection<int, NewsletterSubscriber>  $subscribers
     */
    private function buildWorkbook(string $siteName, Collection $subscribers): string
    {
        $logo = $this->logoBinary();
        $path = tempnam(sys_get_temp_dir(), 'bulten');

        if ($path === false) {
            throw new RuntimeException('Excel dosyası için geçici yol oluşturulamadı.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Excel dosyası oluşturulamadı.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes($logo !== null));
        $zip->addFromString('_rels/.rels', $this->packageRels());
        $zip->addFromString('docProps/core.xml', $this->coreProps($siteName));
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($siteName, $subscribers, $logo !== null));

        if ($logo !== null) {
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->sheetRels());
            $zip->addFromString('xl/drawings/drawing1.xml', $this->drawing($logo['widthEmu'], $logo['heightEmu']));
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->drawingRels($logo['extension']));
            $zip->addFromString('xl/media/logo.'.$logo['extension'], $logo['bytes']);
        }

        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        if ($contents === false || $contents === '') {
            throw new RuntimeException('Excel dosyası okunamadı.');
        }

        return $contents;
    }

    /**
     * @return array{bytes: string, extension: string, widthEmu: int, heightEmu: int}|null
     */
    private function logoBinary(): ?array
    {
        $path = MailTemplate::logoPath();

        if (! is_file($path)) {
            $path = public_path('images/logo-mark.png');
        }

        if (! is_file($path)) {
            return null;
        }

        $bytes = file_get_contents($path);
        $info = @getimagesize($path);

        if ($bytes === false || $info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
            return null;
        }

        $mime = $info['mime'] ?? '';
        $extension = match ($mime) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            default => null,
        };

        if ($extension === null) {
            return null;
        }

        $ratio = $info[0] / $info[1];
        $heightEmu = (int) round(1.7 * 360000);
        $widthEmu = (int) round($heightEmu * $ratio);
        $maxWidthEmu = (int) round(2.4 * 360000);

        if ($widthEmu > $maxWidthEmu) {
            $widthEmu = $maxWidthEmu;
            $heightEmu = (int) round($widthEmu / $ratio);
        }

        return [
            'bytes' => $bytes,
            'extension' => $extension,
            'widthEmu' => $widthEmu,
            'heightEmu' => $heightEmu,
        ];
    }

    private function contentTypes(bool $withLogo): string
    {
        $logoDefaults = $withLogo
            ? '<Default Extension="png" ContentType="image/png"/>'
                .'<Default Extension="jpeg" ContentType="image/jpeg"/>'
                .'<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>'
            : '';

        return <<<XML
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
              <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
              <Default Extension="xml" ContentType="application/xml"/>
              {$logoDefaults}
              <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
              <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
              <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
              <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
            </Types>
            XML;
    }

    private function packageRels(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
              <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
              <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
            </Relationships>
            XML;
    }

    private function workbookRels(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
              <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
              <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
            </Relationships>
            XML;
    }

    private function sheetRels(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
              <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>
            </Relationships>
            XML;
    }

    private function drawingRels(string $extension): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
              <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.{$extension}"/>
            </Relationships>
            XML;
    }

    private function drawing(int $widthEmu, int $heightEmu): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
              <xdr:oneCellAnchor>
                <xdr:from>
                  <xdr:col>0</xdr:col>
                  <xdr:colOff>60000</xdr:colOff>
                  <xdr:row>0</xdr:row>
                  <xdr:rowOff>60000</xdr:rowOff>
                </xdr:from>
                <xdr:ext cx="{$widthEmu}" cy="{$heightEmu}"/>
                <xdr:pic>
                  <xdr:nvPicPr>
                    <xdr:cNvPr id="1" name="Logo"/>
                    <xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr>
                  </xdr:nvPicPr>
                  <xdr:blipFill>
                    <a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rId1"/>
                    <a:stretch><a:fillRect/></a:stretch>
                  </xdr:blipFill>
                  <xdr:spPr>
                    <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
                  </xdr:spPr>
                </xdr:pic>
                <xdr:clientData/>
              </xdr:oneCellAnchor>
            </xdr:wsDr>
            XML;
    }

    private function coreProps(string $siteName): string
    {
        $created = now()->utc()->toAtomString();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>E-bülten abone listesi</dc:title>'
            .'<dc:creator>'.$this->xml($siteName).'</dc:creator>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:created>'
            .'</cp:coreProperties>';
    }

    private function workbook(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
              <sheets>
                <sheet name="E-bülten" sheetId="1" r:id="rId1"/>
              </sheets>
            </workbook>
            XML;
    }

    private function styles(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
              <fonts count="7">
                <font><sz val="11"/><color rgb="FF161513"/><name val="Calibri"/></font>
                <font><b/><sz val="18"/><color rgb="FF161513"/><name val="Calibri"/></font>
                <font><i/><sz val="11"/><color rgb="FF6B6560"/><name val="Calibri"/></font>
                <font><sz val="10"/><color rgb="FF8A7A62"/><name val="Calibri"/></font>
                <font><b/><sz val="12"/><color rgb="FF8A7A62"/><name val="Calibri"/></font>
                <font><b/><sz val="11"/><color rgb="FFFFFCF8"/><name val="Calibri"/></font>
                <font><sz val="9"/><color rgb="FF6B6560"/><name val="Calibri"/></font>
              </fonts>
              <fills count="5">
                <fill><patternFill patternType="none"/></fill>
                <fill><patternFill patternType="gray125"/></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="FF8A7A62"/></patternFill></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="FFFBF6EC"/></patternFill></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="FFFFFCF8"/></patternFill></fill>
              </fills>
              <borders count="2">
                <border><left/><right/><top/><bottom/><diagonal/></border>
                <border>
                  <left style="thin"><color rgb="FFE6DFD3"/></left>
                  <right style="thin"><color rgb="FFE6DFD3"/></right>
                  <top style="thin"><color rgb="FFE6DFD3"/></top>
                  <bottom style="thin"><color rgb="FFE6DFD3"/></bottom>
                </border>
              </borders>
              <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
              <cellXfs count="11">
                <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="0"/></xf>
                <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center" wrapText="0"/></xf>
                <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
                <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
                <xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center" wrapText="0"/></xf>
                <xf numFmtId="0" fontId="5" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>
                <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="0"/></xf>
                <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="0"/></xf>
                <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>
                <xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
                <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>
              </cellXfs>
            </styleSheet>
            XML;
    }

    /**
     * @param  Collection<int, NewsletterSubscriber>  $subscribers
     */
    private function worksheet(string $siteName, Collection $subscribers, bool $withLogo): string
    {
        $tagline = (string) SiteSettings::get('tagline', '');
        $address = (string) SiteSettings::get('address', '');
        $email = (string) SiteSettings::get('email', '');
        $phone = (string) SiteSettings::get('phone', '');
        $count = $subscribers->count();
        $exportedAt = now()->timezone(config('app.timezone'))->format('d.m.Y H:i');
        $contact = implode('  ·  ', array_values(array_filter([$email, $phone])));
        $headerRow = 9;
        $firstDataRow = $headerRow + 1;
        $lastDataRow = $count > 0 ? $headerRow + $count : $headerRow;
        $footerRow = $lastDataRow + 2;
        $lastRow = $footerRow;
        $drawing = $withLogo ? '<drawing r:id="rId1"/>' : '';

        $rows = '';
        $rows .= $this->row(1, 24, $this->textCell('B', 1, $siteName, 1));
        $rows .= $this->row(2, 18, $this->textCell('B', 2, $tagline !== '' ? $tagline : 'Gaziantep · İlim · Sohbet · Kültür', 2));
        $rows .= $this->row(3, 32, $this->textCell('B', 3, $address, 3));
        $rows .= $this->row(4, 16, $this->textCell('B', 4, $contact, 3));
        $rows .= $this->row(6, 20, $this->textCell('A', 6, 'E-bülten abone listesi', 4));
        $rows .= $this->row(7, 16, $this->textCell('A', 7, "Dışa aktarım: {$exportedAt}    Kayıt sayısı: {$count}", 3));

        $headerCells = $this->textCell('A', $headerRow, 'No', 5)
            .$this->textCell('B', $headerRow, 'E-posta', 5)
            .$this->textCell('C', $headerRow, 'Onay tarihi', 5)
            .$this->textCell('D', $headerRow, 'Kayıt tarihi', 5);
        $rows .= $this->row($headerRow, 22, $headerCells);

        foreach ($subscribers->values() as $index => $subscriber) {
            $row = $firstDataRow + $index;
            $odd = $index % 2 === 1;
            $cellStyle = $odd ? 7 : 6;
            $numStyle = $odd ? 10 : 8;
            $confirmed = $subscriber->confirmed_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—';
            $created = $subscriber->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—';

            $cells = '<c r="A'.$row.'" s="'.$numStyle.'"><v>'.($index + 1).'</v></c>'
                .$this->textCell('B', $row, $subscriber->email, $cellStyle)
                .$this->textCell('C', $row, $confirmed, $cellStyle)
                .$this->textCell('D', $row, $created, $cellStyle);

            $rows .= $this->row($row, 20, $cells);
        }

        $rows .= $this->row($footerRow, 28, $this->textCell('A', $footerRow, 'Bu liste dernek içi kullanıma aittir. Kişisel veriler KVKK kapsamında saklanır.', 9));

        return <<<XML
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
              <sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>
              <dimension ref="A1:D{$lastRow}"/>
              <sheetViews>
                <sheetView workbookViewId="0" showGridLines="0" tabSelected="1"/>
              </sheetViews>
              <sheetFormatPr defaultRowHeight="18"/>
              <cols>
                <col min="1" max="1" width="14" customWidth="1"/>
                <col min="2" max="2" width="44" customWidth="1"/>
                <col min="3" max="3" width="22" customWidth="1"/>
                <col min="4" max="4" width="22" customWidth="1"/>
              </cols>
              <sheetData>{$rows}</sheetData>
              <mergeCells count="7">
                <mergeCell ref="B1:D1"/>
                <mergeCell ref="B2:D2"/>
                <mergeCell ref="B3:D3"/>
                <mergeCell ref="B4:D4"/>
                <mergeCell ref="A6:D6"/>
                <mergeCell ref="A7:D7"/>
                <mergeCell ref="A{$footerRow}:D{$footerRow}"/>
              </mergeCells>
              <pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.2" footer="0.2"/>
              <pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>
              {$drawing}
            </worksheet>
            XML;
    }

    private function row(int $row, int $height, string $cells): string
    {
        return '<row r="'.$row.'" ht="'.$height.'" customHeight="1">'.$cells.'</row>';
    }

    private function textCell(string $col, int $row, string $value, int $style): string
    {
        if ($value === '') {
            return '';
        }

        return '<c r="'.$col.$row.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
