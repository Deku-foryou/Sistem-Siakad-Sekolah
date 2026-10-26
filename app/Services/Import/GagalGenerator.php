<?php

namespace App\Services\Import;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GagalGenerator
{
    public static function buat(string $uuid): string
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
            throw new InvalidArgumentException('UUID import tidak valid.');
        }

        $directory = storage_path('app/private/imports');
        $manifestPath = $directory.DIRECTORY_SEPARATOR.$uuid.'.json';
        $failuresPath = $directory.DIRECTORY_SEPARATOR.$uuid.'-gagal.json';
        if (! is_file($manifestPath)) {
            throw new InvalidArgumentException('Data import tidak ditemukan.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $importer = ImportBase::dariJenis($manifest['jenis']);
        $failures = is_file($failuresPath)
            ? json_decode((string) file_get_contents($failuresPath), true, 512, JSON_THROW_ON_ERROR)
            : [];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $columns = array_merge($importer->kolom(), ['alasan', 'baris_asli']);
        foreach ($columns as $index => $column) {
            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValueExplicit($coordinate.'1', $column, DataType::TYPE_STRING);
        }

        foreach ($failures as $rowIndex => $failure) {
            $rowNumber = $rowIndex + 2;
            foreach ($importer->kolom() as $columnIndex => $column) {
                $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                $value = $failure['nilai'][$column] ?? '';
                $sheet->setCellValueExplicit($coordinate.$rowNumber, (string) $value, DataType::TYPE_STRING);
            }

            $reasonColumn = count($importer->kolom()) + 1;
            $rowColumn = $reasonColumn + 1;
            $reasonCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($reasonColumn);
            $rowCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($rowColumn);
            $sheet->setCellValueExplicit($reasonCoordinate.$rowNumber, implode('; ', $failure['alasan']), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($rowCoordinate.$rowNumber, (string) $failure['baris_asli'], DataType::TYPE_STRING);
        }

        $path = $directory.DIRECTORY_SEPARATOR.'gagal-'.$manifest['jenis'].'-'.now()->format('Ymd').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
