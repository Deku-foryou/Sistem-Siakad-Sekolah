<?php

namespace App\Services\Import;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TemplateGenerator
{
    public static function buat(string $jenis): string
    {
        $importer = ImportBase::dariJenis($jenis);
        $spreadsheet = new Spreadsheet;
        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data');

        foreach ($importer->kolom() as $index => $column) {
            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $dataSheet->setCellValueExplicit($coordinate.'1', $column, DataType::TYPE_STRING);
            if (in_array($column, ['nisn', 'nip', 'nis', 'telepon', 'telepon_ortu', 'kode', 'tanggal_lahir'], true)) {
                $dataSheet->getStyle($coordinate.'2:'.$coordinate.'1001')
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);
            }
        }

        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Petunjuk');
        foreach (['Kolom', 'Aturan', 'Contoh'] as $index => $header) {
            $instructionSheet->setCellValueExplicit(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1).'1',
                $header,
                DataType::TYPE_STRING
            );
        }

        foreach ($importer->kolom() as $index => $column) {
            $row = $index + 2;
            $instructionSheet->setCellValueExplicit('A'.$row, $column, DataType::TYPE_STRING);
            $instructionSheet->setCellValueExplicit('B'.$row, self::aturan($column, $jenis), DataType::TYPE_STRING);
            $instructionSheet->setCellValueExplicit('C'.$row, self::contoh($column), DataType::TYPE_STRING);
        }

        $instructionSheet->getColumnDimension('A')->setAutoSize(true);
        $instructionSheet->getColumnDimension('B')->setAutoSize(true);
        $instructionSheet->getColumnDimension('C')->setAutoSize(true);

        $directory = (new ImportFileReader)->directory();
        $path = $directory.DIRECTORY_SEPARATOR.'template-'.$jenis.'-'.Str::uuid().'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private static function aturan(string $column, string $jenis): string
    {
        return match ($column) {
            'nisn' => $jenis === 'penempatan'
                ? 'NISN siswa yang terdaftar dan berstatus aktif; 10 digit.'
                : '10 digit; teks angka boleh dipad nol di depan.',
            'nip' => 'Digit saja, maksimal 25 digit; gunakan format teks untuk 16 digit atau lebih.',
            'nis' => 'Teks opsional.',
            'kode' => 'Kode mapel unik.',
            'jenis_kelamin' => 'L, P, LAKI-LAKI, LAKI LAKI, atau PEREMPUAN.',
            'tanggal_lahir' => 'dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd, atau tanggal Excel; tidak boleh di masa depan.',
            'tahun_masuk' => 'Opsional; empat digit dari 2000 sampai tahun berjalan + 1.',
            'telepon', 'telepon_ortu' => 'Teks; spasi dan tanda hubung dihapus.',
            'tahun_ajaran' => 'Harus sama dengan nama tahun ajaran yang terdaftar.',
            'tingkat' => '10, 11, atau 12.',
            'jenis' => 'Induk (induk) atau Mapel pilihan (mapel_pilihan).',
            'nip_wali' => 'Opsional untuk Wali kelas Induk; guru harus aktif.',
            'nama_kelas' => 'Nama Kelas yang sudah terdaftar pada tahun ajaran yang dipilih.',
            'kelompok' => 'wajib atau pilihan.',
            default => 'Teks; kolom wajib diisi kecuali disebut opsional.',
        };
    }

    private static function contoh(string $column): string
    {
        return match ($column) {
            'nisn' => '0059143361',
            'nis' => '00123',
            'nip' => '198001012010011001',
            'nama' => 'Contoh Nama',
            'nama_kelas' => 'X-A',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '01/01/2000',
            'tahun_masuk' => (string) now()->year,
            'telepon', 'telepon_ortu' => '081234567890',
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'jenis' => 'induk',
            'nip_wali' => '198001012010011001',
            'kelompok' => 'wajib',
            'kode' => 'MAT001',
            default => 'Contoh',
        };
    }
}
