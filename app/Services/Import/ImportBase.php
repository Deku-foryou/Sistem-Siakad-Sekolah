<?php

namespace App\Services\Import;

use App\Support\JenisKelamin;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

abstract class ImportBase
{
    abstract public function jenis(): string;

    abstract public function kolom(): array;

    abstract protected function normalisasi(array $values): array;

    abstract protected function simpan(array $values): array;

    abstract public function kunciDuplikat(array $values): ?string;

    public static function dariJenis(string $jenis): self
    {
        return match ($jenis) {
            'siswa' => new ImportSiswa,
            'guru' => new ImportGuru,
            'mapel' => new ImportMapel,
            'kelas' => new ImportKelas,
            'penempatan' => new ImportPenempatan,
            default => throw new InvalidArgumentException('Jenis import tidak dikenal.'),
        };
    }

    public function proses(array $values): array
    {
        try {
            $normalized = $this->normalisasi($values);
            $result = $this->simpan($normalized);
            $errors = $result['errors'] ?? [];

            return [
                'success' => (bool) ($result['success'] ?? false),
                'errors' => array_values($errors),
                'warnings' => array_values($result['warnings'] ?? []),
            ];
        } catch (InvalidArgumentException $exception) {
            return ['success' => false, 'errors' => [$exception->getMessage()], 'warnings' => []];
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    protected function wajibTeks(array $values, string $kolom): string
    {
        $value = $values[$kolom] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Kolom {$kolom} wajib diisi dengan teks.");
        }

        return trim($value);
    }

    protected function teksOpsional(array $values, string $kolom): ?string
    {
        $value = $values[$kolom] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException("Kolom {$kolom} harus berupa teks.");
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    protected function normalisasiNisn(mixed $value): string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException('NISN harus berupa angka.');
        }
        if (is_float($value) && (! is_finite($value) || floor($value) !== $value)) {
            throw new InvalidArgumentException('NISN tidak boleh berupa angka desimal atau notasi ilmiah.');
        }

        $nisn = trim((string) $value);
        if ($nisn === '' || preg_match('/[Ee.,]/', $nisn)) {
            throw new InvalidArgumentException('NISN tidak boleh mengandung huruf E, titik, atau koma.');
        }
        if (! ctype_digit($nisn)) {
            throw new InvalidArgumentException('NISN harus berupa angka.');
        }
        if (strlen($nisn) > 10) {
            throw new InvalidArgumentException('NISN maksimal 10 digit.');
        }

        return str_pad($nisn, 10, '0', STR_PAD_LEFT);
    }

    protected function normalisasiNip(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            if (is_float($value) && (! is_finite($value) || floor($value) !== $value)) {
                throw new InvalidArgumentException('NIP tidak boleh berupa angka desimal atau notasi ilmiah.');
            }
            if (abs($value) >= 1_000_000_000_000_000) {
                throw new InvalidArgumentException('NIP harus berformat teks (presisi angka hilang di Excel).');
            }
        } elseif (! is_string($value)) {
            throw new InvalidArgumentException('NIP harus berupa angka.');
        }

        $nip = trim((string) $value);
        if ($nip === '' || preg_match('/[Ee.,]/', $nip)) {
            throw new InvalidArgumentException('NIP tidak boleh berupa notasi ilmiah atau desimal.');
        }
        if (! ctype_digit($nip)) {
            throw new InvalidArgumentException('NIP harus berupa digit saja.');
        }
        if (strlen($nip) > 25) {
            throw new InvalidArgumentException('NIP maksimal 25 digit.');
        }

        return $nip;
    }

    protected function tanggalLahir(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject($value);
            } catch (Throwable) {
                throw new InvalidArgumentException('Tanggal lahir bukan serial tanggal Excel yang valid.');
            }

            $parsed = DateTimeImmutable::createFromInterface($date);
        } elseif (is_string($value)) {
            $input = trim($value);
            $parsed = null;
            foreach (['!d/m/Y', '!d-m-Y', '!Y-m-d'] as $format) {
                $candidate = DateTimeImmutable::createFromFormat($format, $input);
                $details = DateTimeImmutable::getLastErrors();
                if ($candidate && ($details === false || ($details['warning_count'] === 0 && $details['error_count'] === 0))) {
                    $expected = ltrim($format, '!');
                    if ($candidate->format($expected) === $input) {
                        $parsed = $candidate;
                        break;
                    }
                }
            }
            if (! $parsed) {
                throw new InvalidArgumentException('Tanggal lahir harus berformat dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd, atau serial Excel yang valid.');
            }
        } else {
            throw new InvalidArgumentException('Tanggal lahir harus berupa teks tanggal atau serial Excel.');
        }

        $date = CarbonImmutable::instance($parsed)->startOfDay();
        if ($date->isFuture()) {
            throw new InvalidArgumentException('Tanggal lahir tidak boleh di masa depan.');
        }

        return $date->format('Y-m-d');
    }

    protected function tahunMasuk(array $values): ?int
    {
        $value = $values['tahun_masuk'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException('Tahun masuk harus berupa empat digit.');
        }
        if (is_float($value) && (! is_finite($value) || floor($value) !== $value)) {
            throw new InvalidArgumentException('Tahun masuk harus berupa empat digit.');
        }

        $year = is_float($value) ? sprintf('%.0f', $value) : trim((string) $value);
        $max = (int) now()->format('Y') + 1;
        if (! preg_match('/^\d{4}$/', $year) || (int) $year < 2000 || (int) $year > $max) {
            throw new InvalidArgumentException("Tahun masuk harus 4 digit antara 2000 dan {$max}.");
        }

        return (int) $year;
    }

    protected function telepon(array $values, string $kolom, array &$warnings): ?string
    {
        $value = $values[$kolom] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            if (is_float($value) && (! is_finite($value) || floor($value) !== $value)) {
                throw new InvalidArgumentException("Kolom {$kolom} harus berupa teks atau bilangan bulat.");
            }
            $phone = (string) $value;
            if (strlen(ltrim($phone, '-')) < 9) {
                $warnings[] = "Kolom {$kolom}: nol di depan mungkin hilang.";
            }
        } elseif (is_string($value)) {
            $phone = trim($value);
        } else {
            throw new InvalidArgumentException("Kolom {$kolom} harus berupa teks atau bilangan bulat.");
        }

        return str_replace([' ', '-'], '', $phone);
    }

    protected function gender(array $values): string
    {
        try {
            return JenisKelamin::normalisasi($this->teksOpsional($values, 'jenis_kelamin'));
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException('Jenis kelamin tidak valid.');
        }
    }

    protected function key(array $values, array $columns): string
    {
        return implode('|', array_map(
            static fn (string $column): string => mb_strtolower(trim((string) ($values[$column] ?? '')), 'UTF-8'),
            $columns
        ));
    }

    protected function hasilService(array $result): array
    {
        return $result;
    }
}
