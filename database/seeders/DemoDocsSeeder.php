<?php

namespace Database\Seeders;

use App\Models\Docs;
use App\Models\Docscat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Docs (wiki sekolah) demo: 3 kategori + 5 dokumen.
 * docs.content adalah judul halaman (bukan file upload) — tanpa file sampah.
 */
class DemoDocsSeeder extends Seeder
{
    public function run(): void
    {
        $umum = Docscat::firstOrCreate(['docscat' => 'Umum'], ['urut' => 1]);
        $akademik = Docscat::firstOrCreate(['docscat' => 'Akademik'], ['urut' => 2]);
        $keuangan = Docscat::firstOrCreate(['docscat' => 'Keuangan'], ['urut' => 3]);

        $docs = [
            ['Selamat Datang di SIAKAD', $umum->iddocscat, 1],
            ['Panduan Login untuk Siswa dan Orang Tua', $umum->iddocscat, 2],
            ['Kalender Akademik Tahun 2026/2027', $akademik->iddocscat, 1],
            ['Panduan Verifikasi Pembayaran', $keuangan->iddocscat, 1],
            ['Alur Pengajuan Refund & Koreksi', $keuangan->iddocscat, 2],
        ];

        foreach ($docs as [$title, $catId, $urut]) {
            Docs::firstOrCreate(
                ['docscat_iddocscat' => $catId, 'urut' => $urut],
                ['title' => $title, 'content' => Str::limit($title, 255)]
            );
        }

        $this->command?->info('DemoDocsSeeder: 3 kategori + 5 dokumen wiki selesai.');
    }
}
