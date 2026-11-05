<?php

namespace App\Exports;

use App\Models\Lead;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Throwable;
use ZipArchive;

class DashboardXlsxExport
{
    /** @param Builder<Lead> $query */
    public function create(Builder $query): string
    {
        $xlsxPath = tempnam(sys_get_temp_dir(), 'umi-crm-xlsx-');
        $sheetPath = tempnam(sys_get_temp_dir(), 'umi-crm-sheet-');

        if ($xlsxPath === false || $sheetPath === false) {
            if (is_string($xlsxPath)) {
                @unlink($xlsxPath);
            }

            if (is_string($sheetPath)) {
                @unlink($sheetPath);
            }

            throw new RuntimeException('File sementara untuk ekspor Excel tidak dapat dibuat.');
        }

        $sheetStream = null;

        try {
            $sheetStream = fopen($sheetPath, 'wb');

            if ($sheetStream === false) {
                throw new RuntimeException('Worksheet sementara tidak dapat dibuka.');
            }

            $headers = ['Tanggal', 'Nama Admin', 'Kategori', 'Sumber Leads', ...array_values(Lead::STATUSES)];
            $this->writeAll($sheetStream, $this->worksheetStart(count($headers)));
            $this->writeAll($sheetStream, '<sheetData>');
            $this->writeAll($sheetStream, $this->headerRow($headers));

            $rowNumber = 2;
            $leads = (clone $query)
                ->with(['user:id,name', 'category:id,name', 'source:id,name'])
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->lazy(500);

            foreach ($leads as $lead) {
                $cells = [
                    $this->dateCell('A', $rowNumber, $this->excelDate($lead->entry_date->toDateString())),
                    $this->stringCell('B', $rowNumber, $lead->user?->name ?? ''),
                    $this->stringCell('C', $rowNumber, $lead->category?->name ?? ''),
                    $this->stringCell('D', $rowNumber, $lead->source?->name ?? ''),
                ];

                foreach (array_keys(Lead::STATUSES) as $index => $key) {
                    $cells[] = $this->numberCell($this->columnName($index + 5), $rowNumber, (int) $lead->{$key});
                }

                $this->writeAll($sheetStream, '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>');
                $rowNumber++;
            }

            $lastRow = $rowNumber - 1;
            $this->writeAll($sheetStream, '</sheetData><autoFilter ref="A1:'.$this->columnName(count($headers)).$lastRow.'"/>');
            $this->writeAll($sheetStream, $this->worksheetEnd());
            fclose($sheetStream);
            $sheetStream = null;

            $archive = new ZipArchive;
            $opened = $archive->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($opened !== true) {
                throw new RuntimeException('File Excel tidak dapat dibuat.');
            }

            $this->addArchiveFiles($archive, $sheetPath);

            if (! $archive->close()) {
                throw new RuntimeException('File Excel gagal disimpan.');
            }

            return $xlsxPath;
        } catch (Throwable $exception) {
            @unlink($xlsxPath);

            throw $exception;
        } finally {
            if (is_resource($sheetStream)) {
                fclose($sheetStream);
            }

            @unlink($sheetPath);
        }
    }

    private function worksheetStart(int $columnCount): string
    {
        $widths = [14, 24, 24, 24, 14, 14, 14, 14, 14];
        $columns = '';

        foreach ($widths as $index => $width) {
            if ($index >= $columnCount) {
                break;
            }

            $column = $index + 1;
            $columns .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetViews><sheetView showGridLines="1" tabSelected="1" workbookViewId="0">'
            .'<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            .'<selection pane="bottomLeft" activeCell="A2" sqref="A2"/>'
            .'</sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols>'.$columns.'</cols>';
    }

    private function worksheetEnd(): string
    {
        return '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            .'<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';
    }

    /** @param list<string> $headers */
    private function headerRow(array $headers): string
    {
        $cells = [];

        foreach ($headers as $index => $header) {
            $cells[] = $this->stringCell($this->columnName($index + 1), 1, $header, 1);
        }

        return '<row r="1" ht="26" customHeight="1">'.implode('', $cells).'</row>';
    }

    private function stringCell(string $column, int $row, string $value, int $style = 0): string
    {
        $styleAttribute = $style > 0 ? ' s="'.$style.'"' : '';

        return '<c r="'.$column.$row.'" t="inlineStr"'.$styleAttribute.'><is><t xml:space="preserve">'
            .$this->escapeXml($value).'</t></is></c>';
    }

    private function numberCell(string $column, int $row, int $value): string
    {
        return '<c r="'.$column.$row.'" t="n"><v>'.$value.'</v></c>';
    }

    private function dateCell(string $column, int $row, int $value): string
    {
        return '<c r="'.$column.$row.'" s="2" t="n"><v>'.$value.'</v></c>';
    }

    private function excelDate(string $date): int
    {
        $timezone = new DateTimeZone('UTC');
        $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);
        $epoch = new DateTimeImmutable('1899-12-30', $timezone);

        if ($value === false) {
            throw new RuntimeException('Tanggal leads tidak valid untuk ekspor Excel.');
        }

        return (int) (($value->getTimestamp() - $epoch->getTimestamp()) / 86400);
    }

    private function columnName(int $column): string
    {
        $name = '';

        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function writeAll($stream, string $content): void
    {
        $length = strlen($content);
        $offset = 0;

        while ($offset < $length) {
            $written = fwrite($stream, substr($content, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('Data worksheet gagal ditulis.');
            }

            $offset += $written;
        }
    }

    private function addArchiveFiles(ZipArchive $archive, string $sheetPath): void
    {
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000"/></bookViews>'
                .'<sheets><sheet name="Laporan Leads" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => $this->stylesXml(),
        ];

        foreach ($files as $name => $content) {
            if (! $archive->addFromString($name, $content)) {
                throw new RuntimeException('Komponen file Excel gagal dibuat.');
            }
        }

        if (! $archive->addFile($sheetPath, 'xl/worksheets/sheet1.xml')) {
            throw new RuntimeException('Worksheet gagal ditambahkan ke file Excel.');
        }
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="yyyy-mm-dd"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF003D57"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
