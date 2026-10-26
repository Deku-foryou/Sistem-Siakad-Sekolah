<?php

namespace App\Services\Import;

use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class ImportFileReader
{
    private const MAX_FILE_BYTES = 2_097_152;

    private const MAX_ROWS = 1000;

    public function baca(string $path, string $jenis): array
    {
        $importer = ImportBase::dariJenis($jenis);
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('File upload tidak ditemukan atau tidak dapat dibaca.');
        }
        if (mb_strtolower(pathinfo($path, PATHINFO_EXTENSION), 'UTF-8') !== 'xlsx') {
            throw new InvalidArgumentException('Format file harus .xlsx.');
        }
        $size = filesize($path);
        if ($size === false || $size > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('Ukuran file maksimal 2 MB.');
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('File upload tidak dapat dibuka.');
        }
        try {
            $signature = fread($handle, 2);
        } finally {
            fclose($handle);
        }
        if ($signature !== 'PK') {
            throw new InvalidArgumentException('Isi file bukan berkas Excel .xlsx yang valid.');
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        try {
            $worksheetNames = $reader->listWorksheetNames($path);
            if (! in_array('Data', $worksheetNames, true)) {
                throw new InvalidArgumentException('File harus memiliki sheet bernama "Data".');
            }
            $spreadsheet = $reader->load($path);
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('File Excel tidak dapat dibaca.', previous: $exception);
        }

        try {
            $sheet = $spreadsheet->getSheetByName('Data');
            if (! $sheet) {
                throw new InvalidArgumentException('File harus memiliki sheet bernama "Data".');
            }
            $headers = $this->headers($sheet, $importer->kolom());
            $rows = $this->rows($sheet, $headers, $importer);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        $uuid = (string) Str::uuid();
        $directory = $this->directory();
        $sourcePath = $directory.DIRECTORY_SEPARATOR.$uuid.'.xlsx';
        $manifestPath = $directory.DIRECTORY_SEPARATOR.$uuid.'.json';

        if (! copy($path, $sourcePath)) {
            throw new InvalidArgumentException('File upload tidak dapat disimpan untuk diproses.');
        }

        $manifest = [
            'uuid' => $uuid,
            'jenis' => $jenis,
            'source' => $uuid.'.xlsx',
            'total' => count($rows),
            'offset' => 0,
            'berhasil' => 0,
            'gagal' => 0,
            'selesai' => false,
            'rows' => $rows,
            'warnings' => [],
            'actor_id' => null,
            'lock_owner' => null,
            'activity_written' => false,
        ];
        try {
            $this->writeJson($manifestPath, $manifest);
        } catch (Throwable $exception) {
            @unlink($sourcePath);
            throw $exception;
        }

        return $manifest;
    }

    public function directory(): string
    {
        $directory = storage_path('app/private/imports');
        if (! is_dir($directory) && ! mkdir($directory, 0770, true) && ! is_dir($directory)) {
            throw new InvalidArgumentException('Folder penyimpanan import tidak dapat dibuat.');
        }

        return $directory;
    }

    public function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $temporaryPath = $path.'.tmp';
        if (file_put_contents($temporaryPath, $json, LOCK_EX) === false || ! rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new InvalidArgumentException('Status import tidak dapat disimpan.');
        }
    }

    private function headers(Worksheet $sheet, array $expected): array
    {
        $highestColumn = $sheet->getHighestDataColumn();
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $headers = [];
        for ($column = 1; $column <= $lastColumn; $column++) {
            $value = $this->cellValue($sheet, $column, 1);
            $header = is_string($value) ? mb_strtolower(trim($value), 'UTF-8') : '';
            if ($header !== '') {
                $headers[$header] = $column;
            }
        }

        $expected = array_map(static fn (string $value): string => mb_strtolower(trim($value), 'UTF-8'), $expected);
        $actual = array_keys($headers);
        sort($expected);
        $sortedActual = $actual;
        sort($sortedActual);
        if (count($headers) !== count($actual) || $sortedActual !== $expected) {
            throw new InvalidArgumentException('Header baris 1 harus berisi tepat: '.implode(', ', $expected).'. Urutan kolom boleh berbeda.');
        }

        return $headers;
    }

    private function rows(Worksheet $sheet, array $headers, ImportBase $importer): array
    {
        $headerByColumn = array_flip($headers);
        $populatedRows = [];
        foreach ($sheet->getCoordinates() as $coordinate) {
            [$columnLetters, $rowNumber] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($coordinate);
            $rowNumber = (int) $rowNumber;
            if ($rowNumber < 2) {
                continue;
            }

            $value = $sheet->getCell($coordinate)->getValue();
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
            }
            if ($value === null) {
                continue;
            }

            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnLetters);
            if (! isset($headerByColumn[$column])) {
                throw new InvalidArgumentException("Baris {$rowNumber} memiliki data di kolom yang tidak tercantum pada header.");
            }
            $header = $headerByColumn[$column];
            $populatedRows[$rowNumber]['nilai'][$header] = $value;
            if (is_string($value) && str_starts_with($value, '=')) {
                $populatedRows[$rowNumber]['kesalahan'][] = "Kolom {$header} berisi formula; formula tidak diizinkan.";
            }
        }

        ksort($populatedRows, SORT_NUMERIC);
        $rows = [];
        $seen = [];

        foreach ($populatedRows as $rowNumber => $rowData) {
            if (count($rows) >= self::MAX_ROWS) {
                throw new InvalidArgumentException('File tidak boleh berisi lebih dari 1000 baris data.');
            }

            $rowValues = array_fill_keys($importer->kolom(), null);
            foreach ($rowData['nilai'] as $header => $value) {
                $rowValues[$header] = $value;
            }
            $readerErrors = $rowData['kesalahan'] ?? [];
            if ($readerErrors === []) {
                $key = $importer->kunciDuplikat($rowValues);
                if ($key !== null && array_key_exists($key, $seen)) {
                    $readerErrors[] = 'duplikat dalam file (sama dengan baris '.$seen[$key].')';
                } elseif ($key !== null) {
                    $seen[$key] = $rowNumber;
                }
            }

            $rows[] = [
                'baris_asli' => $rowNumber,
                'nilai' => $rowValues,
                'kesalahan_pembaca' => $readerErrors,
            ];
        }

        return $rows;
    }

    private function cellValue(Worksheet $sheet, int $column, int $row): mixed
    {
        $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$row;

        return $sheet->getCell($coordinate)->getValue();
    }
}
