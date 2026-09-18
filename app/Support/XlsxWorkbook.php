<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Hâcer kurumsal Excel şablonuyla çok sayfalı xlsx üretir.
 * Yazdırma görünümüyle aynı dil: marka bandı, kompakt tablo, taşmayan hücreler.
 * Birleşik hücre / dondurulmuş bölme yok (Excel bozulmasın diye).
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
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
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
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
            .'<Application>Microsoft Excel</Application>'
            .'<Company>'.$this->xml(HacerXlsxTemplate::ORGANIZATION).'</Company>'
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
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="5">'
            .'<font><sz val="10"/><color rgb="FF2C2825"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF2C2825"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><sz val="10"/><color rgb="FF6B6560"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FFFFFCF8"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><sz val="9"/><color rgb="FF6B6560"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="6">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF3EEE4"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF7A6B55"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFD4CBBE"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFFCF8"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="3">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFE6DFD3"/></left>'
            .'<right style="thin"><color rgb="FFE6DFD3"/></right>'
            .'<top style="thin"><color rgb="FFE6DFD3"/></top>'
            .'<bottom style="thin"><color rgb="FFE6DFD3"/></bottom>'
            .'</border>'
            .'<border>'
            .'<left/><right/>'
            .'<top style="thin"><color rgb="FFD4CBBE"/></top>'
            .'<bottom/><diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="7">'
            // 0 body — wrap ile taşma yok, yazdırma gibi
            .'<xf numFmtId="49" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            // 1 brand title — wrap kapalı; boş komşu hücrelere taşarak unvan bandı
            .'<xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 2 brand meta
            .'<xf numFmtId="49" fontId="2" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 3 gold accent
            .'<xf numFmtId="49" fontId="0" fillId="4" borderId="0" xfId="0" applyNumberFormat="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            // 4 table header — yazdırma: sol hizalı
            .'<xf numFmtId="49" fontId="3" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            // 5 zebra
            .'<xf numFmtId="49" fontId="0" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            // 6 footer
            .'<xf numFmtId="49" fontId="4" fillId="2" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function worksheet(array $rows, ?string $context, ?string $summary): string
    {
        if ($rows === []) {
            $rows = [['']];
        }

        $rows = $this->clipRows($rows);
        $columnCount = max(1, max(array_map(count(...), $rows)));
        $lastColumn = $this->columnLetter($columnCount);
        $brandRows = HacerXlsxTemplate::BRAND_ROWS;
        $headerRowNumber = $brandRows + 1;
        $headers = array_values($rows[0] ?? []);
        $rowMarkup = '';

        $columnWidths = [];

        for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
            $columnWidths[$columnIndex] = HacerXlsxTemplate::columnWidthForContent(
                (string) ($headers[$columnIndex] ?? ''),
                $rows,
                $columnIndex,
            );
        }

        // Satır 1: kurum unvanı (wrap yok → bant boyunca okunur)
        $titleCells = '';

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $titleCells .= $this->inlineCell(
                $this->columnLetter($columnIndex).'1',
                $columnIndex === 1 ? HacerXlsxTemplate::ORGANIZATION : '',
                '1',
            );
        }

        $rowMarkup .= '<row r="1" ht="28" customHeight="1">'.$titleCells.'</row>';

        // Satır 2: meta (faaliyet · belge · özet)
        $metaCells = '';
        $meta = HacerXlsxTemplate::metaLine($context, $summary);

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $metaCells .= $this->inlineCell(
                $this->columnLetter($columnIndex).'2',
                $columnIndex === 1 ? $meta : '',
                '2',
            );
        }

        $rowMarkup .= '<row r="2" ht="20" customHeight="1">'.$metaCells.'</row>';

        // Satır 3: altın çizgi
        $accentCells = '';

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $accentCells .= $this->inlineCell($this->columnLetter($columnIndex).'3', '', '3');
        }

        $rowMarkup .= '<row r="3" ht="4" customHeight="1">'.$accentCells.'</row>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $headerRowNumber + $rowIndex;
            $isHeader = $rowIndex === 0;
            $isAlt = ! $isHeader && ($rowIndex % 2 === 0);
            $style = $isHeader ? '4' : ($isAlt ? '5' : '0');
            $cells = '';

            for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
                $cells .= $this->inlineCell(
                    $this->columnLetter($columnIndex + 1).$rowNumber,
                    (string) ($row[$columnIndex] ?? ''),
                    $style,
                );
            }

            $rowHeight = $isHeader ? 24 : 22;
            $rowMarkup .= '<row r="'.$rowNumber.'" ht="'.$rowHeight.'" customHeight="1">'.$cells.'</row>';
        }

        $lastDataRow = $headerRowNumber + count($rows) - 1;
        $spacerRow = $lastDataRow + 1;
        $footerRow = $lastDataRow + 2;

        $spacerCells = '';

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $spacerCells .= $this->inlineCell($this->columnLetter($columnIndex).$spacerRow, '', '2');
        }

        $rowMarkup .= '<row r="'.$spacerRow.'" ht="8" customHeight="1">'.$spacerCells.'</row>';

        $footerCells = '';

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $footerCells .= $this->inlineCell(
                $this->columnLetter($columnIndex).$footerRow,
                $columnIndex === 1 ? HacerXlsxTemplate::footerNote() : '',
                '6',
            );
        }

        $rowMarkup .= '<row r="'.$footerRow.'" ht="20" customHeight="1">'.$footerCells.'</row>';

        $cols = '';

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $width = $columnWidths[$columnIndex - 1] ?? 14.0;
            $cols .= '<col min="'.$columnIndex.'" max="'.$columnIndex.'" width="'.number_format($width, 2, '.', '').'" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="A1:'.$lastColumn.$footerRow.'"/>'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="22" defaultColWidth="12"/>'
            .'<cols>'.$cols.'</cols>'
            .'<sheetData>'.$rowMarkup.'</sheetData>'
            .'</worksheet>';
    }

    /**
     * @param  list<list<string>>  $rows
     * @return list<list<string>>
     */
    private function clipRows(array $rows): array
    {
        return array_map(
            fn (array $row): array => array_map(
                fn (mixed $cell): string => HacerXlsxTemplate::clipCell((string) $cell),
                $row,
            ),
            $rows,
        );
    }

    private function inlineCell(string $reference, string $value, string $style): string
    {
        return '<c r="'.$reference.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
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
