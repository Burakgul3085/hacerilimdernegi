<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Hâcer kurumsal Excel şablonuyla çok sayfalı xlsx üretir.
 * Dosya indirme sonrası saklanmaz.
 */
class XlsxWorkbook
{
    /**
     * @param  list<array{name: string, rows: list<list<string>>, context?: string|null, summary?: string|null}>  $sheets
     */
    public function build(string $title, array $sheets): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Excel dışa aktarımı için PHP zip eklentisi gerekir.');
        }

        if ($sheets === []) {
            $sheets[] = [
                'name' => 'Başvurular',
                'rows' => [['Kayıt yok']],
                'context' => null,
                'summary' => null,
            ];
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        if ($path === false) {
            throw new RuntimeException('Excel dosyası için geçici yol oluşturulamadı.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Excel dosyası oluşturulamadı.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->packageRels());
        $zip->addFromString('docProps/core.xml', $this->coreProps($title));
        $zip->addFromString('docProps/app.xml', $this->appProps());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $this->worksheet(
                    $sheet['rows'],
                    $sheet['context'] ?? $sheet['name'],
                    $sheet['summary'] ?? null,
                ),
            );
        }

        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        if ($contents === false || $contents === '') {
            throw new RuntimeException('Excel dosyası okunamadı.');
        }

        return $contents;
    }

    private function contentTypes(int $sheetCount): string
    {
        $overrides = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .$overrides
            .'</Types>';
    }

    private function packageRels(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
              <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
              <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
              <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
            </Relationships>
            XML;
    }

    private function coreProps(string $title): string
    {
        $created = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$this->xml($title).'</dc:title>'
            .'<dc:creator>'.$this->xml(HacerXlsxTemplate::CREATOR).'</dc:creator>'
            .'<cp:lastModifiedBy>'.$this->xml(HacerXlsxTemplate::CREATOR).'</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function appProps(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>'.$this->xml(HacerXlsxTemplate::ORGANIZATION).'</Application>'
            .'<Company>'.$this->xml(HacerXlsxTemplate::ORGANIZATION).'</Company>'
            .'<Manager>'.$this->xml(HacerXlsxTemplate::DOCUMENT_KIND).'</Manager>'
            .'</Properties>';
    }

    /**
     * @param  list<array{name: string, rows: list<list<string>>}>  $sheets
     */
    private function workbook(array $sheets): string
    {
        $markup = '';

        foreach ($sheets as $index => $sheet) {
            $markup .= '<sheet name="'.$this->xml($sheet['name']).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$markup.'</sheets>'
            .'</workbook>';
    }

    private function workbookRels(int $sheetCount): string
    {
        $markup = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $markup .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }

        $styleId = $sheetCount + 1;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$markup
            .'<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        $forest = HacerXlsxTemplate::COLOR_FOREST;
        $gold = HacerXlsxTemplate::COLOR_GOLD;
        $cream = HacerXlsxTemplate::COLOR_CREAM;
        $paper = HacerXlsxTemplate::COLOR_PAPER;
        $line = HacerXlsxTemplate::COLOR_LINE;
        $goldSoft = HacerXlsxTemplate::COLOR_GOLD_SOFT;
        $muted = HacerXlsxTemplate::COLOR_MUTED;

        return <<<XML
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
              <fonts count="6">
                <font><sz val="11"/><color rgb="FF161513"/><name val="Calibri"/></font>
                <font><b/><sz val="16"/><color rgb="{$paper}"/><name val="Calibri"/></font>
                <font><sz val="10"/><color rgb="{$goldSoft}"/><name val="Calibri"/></font>
                <font><b/><sz val="11"/><color rgb="{$paper}"/><name val="Calibri"/></font>
                <font><sz val="10"/><color rgb="{$muted}"/><name val="Calibri"/><i/></font>
                <font><b/><sz val="11"/><color rgb="FF161513"/><name val="Calibri"/></font>
              </fonts>
              <fills count="6">
                <fill><patternFill patternType="none"/></fill>
                <fill><patternFill patternType="gray125"/></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="{$forest}"/></patternFill></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="{$gold}"/></patternFill></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="{$cream}"/></patternFill></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="{$paper}"/></patternFill></fill>
              </fills>
              <borders count="3">
                <border><left/><right/><top/><bottom/><diagonal/></border>
                <border>
                  <left style="thin"><color rgb="{$line}"/></left>
                  <right style="thin"><color rgb="{$line}"/></right>
                  <top style="thin"><color rgb="{$line}"/></top>
                  <bottom style="thin"><color rgb="{$line}"/></bottom>
                </border>
                <border>
                  <left style="thin"><color rgb="{$gold}"/></left>
                  <right style="thin"><color rgb="{$gold}"/></right>
                  <top style="thin"><color rgb="{$gold}"/></top>
                  <bottom style="medium"><color rgb="{$gold}"/></bottom>
                </border>
              </borders>
              <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
              <cellXfs count="7">
                <xf numFmtId="49" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
                <xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
                <xf numFmtId="49" fontId="2" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
                <xf numFmtId="49" fontId="5" fillId="4" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
                <xf numFmtId="49" fontId="3" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
                <xf numFmtId="49" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
                <xf numFmtId="49" fontId="4" fillId="5" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
              </cellXfs>
            </styleSheet>
            XML;
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function worksheet(array $rows, ?string $context, ?string $summary): string
    {
        if ($rows === []) {
            $rows = [['']];
        }

        $columnCount = max(1, max(array_map(count(...), $rows)));
        $lastColumn = $this->columnLetter($columnCount);
        $brandRows = HacerXlsxTemplate::BRAND_ROWS;
        $headerRowNumber = $brandRows + 1;
        $dataStart = $headerRowNumber + 1;
        $rowMarkup = '';

        $brand = [
            1 => ['text' => HacerXlsxTemplate::ORGANIZATION, 'style' => '1'],
            2 => ['text' => HacerXlsxTemplate::subtitleLine($context), 'style' => '2'],
            3 => ['text' => $summary ?: ' ', 'style' => '3'],
            4 => ['text' => ' ', 'style' => '3'],
        ];

        foreach ($brand as $rowNumber => $meta) {
            $cells = '';

            for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
                $reference = $this->columnLetter($columnIndex).$rowNumber;
                $value = $columnIndex === 1 ? $meta['text'] : '';
                $cells .= '<c r="'.$reference.'" t="inlineStr" s="'.$meta['style'].'"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
            }

            $height = match ($rowNumber) {
                1 => ' ht="28" customHeight="1"',
                2 => ' ht="20" customHeight="1"',
                3 => ' ht="18" customHeight="1"',
                default => ' ht="8" customHeight="1"',
            };

            $rowMarkup .= '<row r="'.$rowNumber.'"'.$height.'>'.$cells.'</row>';
        }

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $headerRowNumber + $rowIndex;
            $isHeader = $rowIndex === 0;
            $isAlt = ! $isHeader && $rowIndex % 2 === 0;
            $style = $isHeader ? '4' : ($isAlt ? '5' : '0');
            $cells = '';

            for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
                $reference = $this->columnLetter($columnIndex + 1).$rowNumber;
                $value = (string) ($row[$columnIndex] ?? '');
                $cells .= '<c r="'.$reference.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
            }

            $rowMarkup .= '<row r="'.$rowNumber.'" ht="18" customHeight="1">'.$cells.'</row>';
        }

        $lastDataRow = $headerRowNumber + count($rows) - 1;
        $footerRow = $lastDataRow + 2;
        $footerCells = '';

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $reference = $this->columnLetter($columnIndex).$footerRow;
            $value = $columnIndex === 1 ? HacerXlsxTemplate::footerNote() : '';
            $footerCells .= '<c r="'.$reference.'" t="inlineStr" s="6"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
        }

        $rowMarkup .= '<row r="'.$footerRow.'" ht="32" customHeight="1">'.$footerCells.'</row>';

        $merges = '';
        for ($row = 1; $row <= $brandRows; $row++) {
            if ($columnCount > 1) {
                $merges .= '<mergeCell ref="A'.$row.':'.$lastColumn.$row.'"/>';
            }
        }
        if ($columnCount > 1) {
            $merges .= '<mergeCell ref="A'.$footerRow.':'.$lastColumn.$footerRow.'"/>';
        }
        $mergeCount = ($columnCount > 1 ? $brandRows + 1 : 0);

        $cols = '';
        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $width = match ($columnIndex) {
                1 => 14,
                2 => 18,
                3 => 22,
                4 => 28,
                default => 18,
            };
            $cols .= '<col min="'.$columnIndex.'" max="'.$columnIndex.'" width="'.$width.'" customWidth="1"/>';
        }

        $filterEnd = max($headerRowNumber, $lastDataRow);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$headerRowNumber.'" topLeftCell="A'.$dataStart.'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.$cols.'</cols>'
            .'<sheetData>'.$rowMarkup.'</sheetData>'
            .($mergeCount > 0 ? '<mergeCells count="'.$mergeCount.'">'.$merges.'</mergeCells>' : '')
            .'<autoFilter ref="A'.$headerRowNumber.':'.$lastColumn.$filterEnd.'"/>'
            .'<pageSetup orientation="landscape" fitToPage="1" fitToWidth="1" fitToHeight="0"/>'
            .'<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            .'</worksheet>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function xml(string $value): string
    {
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? $value;

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
