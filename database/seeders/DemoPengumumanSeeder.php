<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Notifikasi;
use App\Models\NotifikasiPenerima;
use App\Models\Pengumuman;
use App\Models\PengumumanBaca;
use App\Models\PengumumanTarget;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Pengumuman & notifikasi demo (sintetis):
 * - 1 pengumuman terbit (target semua) + 1 terbit (target jurusan IPA) + 1 draf + 1 arsip
 * - Pembaca pertama X IPA 1 + target PBB (jurusan IPA)
 * - 2 notifikasi (akademik untuk X IPA 1; pengingat tagihan dibuat oleh DemoKeuanganSeeder bila relevan)
 * Idempoten: firstOrCreate.
 */
class DemoPengumumanSeeder extends Seeder
{
    public function run(): void
    {
        $superadminId = User::where('level', 'superadmin')->value('id');
        if (! $superadminId) {
            throw new \RuntimeException('DemoPengumumanSeeder: superadmin belum ada.');
        }

        $jurusanIpa = Jurusan::where('kode', 'IPA')->first();
        $kelasX = Kelas::where('nama', 'X IPA 1')->first();
        $kelasXiIpa = Kelas::where('nama', 'XI IPA 1')->first();

        // ---------- Pengumuman ----------
        $p1 = Pengumuman::firstOrCreate(
            ['judul' => 'Simulasi Ujian Tengah Semester Ganjil'],
            [
                'isi' => 'Simulasi UTS akan dilaksanakan pada 16–21 November 2026. Seluruh siswa diharapkan hadir tepat waktu dan membawa kartu pelajar.',
                'kategori' => 'akademik', 'prioritas' => 'sedang', 'status' => 'terbit',
                'waktu_terbit' => now()->subDays(3), 'berlaku_dari' => now()->subDays(3)->toDateString(), 'berlaku_sampai' => now()->addDays(30)->toDateString(),
                'target' => 'semua', 'dibuat_oleh' => $superadminId,
            ]
        );

        $p2 = Pengumuman::firstOrCreate(
            ['judul' => 'Workshop Parenting Kelas IPA'],
            [
                'isi' => 'Workshop parenting untuk orang tua siswa jurusan IPA akan dilaksanakan pada 22 November 2026 pukul 09.00 di aula sekolah.',
                'kategori' => 'penting', 'prioritas' => 'tinggi', 'status' => 'terbit',
                'waktu_terbit' => now()->subDays(1), 'berlaku_dari' => now()->subDays(1)->toDateString(), 'berlaku_sampai' => now()->addDays(14)->toDateString(),
                'target' => 'jurusan', 'dibuat_oleh' => $superadminId,
            ]
        );
        if ($jurusanIpa) {
            PengumumanTarget::firstOrCreate(
                ['pengumuman_idpengumuman' => $p2->idpengumuman, 'jurusan_idjurusan' => $jurusanIpa->idjurusan],
                ['jurusan_idjurusan' => $jurusanIpa->idjurusan]
            );
        }

        Pengumuman::firstOrCreate(
            ['judul' => 'Pentas Seni Akhir Semester'],
            [
                'isi' => 'Perencanaan pentas seni akhir semester masih dalam tahap finalisasi panitia. Informasi lebih lanjut menyusul.',
                'kategori' => 'acara', 'prioritas' => 'rendah', 'status' => 'draf',
                'waktu_terbit' => null, 'berlaku_dari' => null, 'berlaku_sampai' => null,
                'target' => 'semua', 'dibuat_oleh' => $superadminId,
            ]
        );

        Pengumuman::firstOrCreate(
            ['judul' => 'Banjir Genangan — Belajar Dari Rumah (Selesai)'],
            [
                'isi' => 'Kegiatan belajar dari rumah selama banjir genangan telah selesai. Kegiatan belajar tatap muka kembali normal.',
                'kategori' => 'darurat', 'prioritas' => 'mendesak', 'status' => 'arsip',
                'waktu_terbit' => now()->subDays(30), 'berlaku_dari' => now()->subDays(32)->toDateString(), 'berlaku_sampai' => now()->subDays(10)->toDateString(),
                'target' => 'semua', 'dibuat_oleh' => $superadminId,
            ]
        );

        // Pembaca pertama: 4 siswa X IPA 1 membaca pengumuman simulasi UTS
        if ($kelasX) {
            $pembaca = Siswa::whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelasX->idkelas))->orderBy('idsiswa')->limit(4)->pluck('idsiswa');
            foreach ($pembaca as $siswaId) {
                PengumumanBaca::firstOrCreate(
                    ['pengumuman_idpengumuman' => $p1->idpengumuman, 'siswa_idsiswa' => $siswaId],
                    ['dibaca_pada' => now()->subDays(2)]
                );
            }
        }

        // ---------- Notifikasi (UTS, dikirim ke siswa X IPA 1 + guru) ----------
        $notif = Notifikasi::firstOrCreate(
            ['judul' => 'Jadwal Simulasi UTS', 'sumber_tabel' => 'pengumuman'],
            [
                'isi' => 'Simulasi UTS 16–21 November 2026. Cek jadwal lengkap di menu pengumuman.',
                'jenis' => 'akademik', 'prioritas' => 'sedang',
                'sumber_id' => $p1->idpengumuman, 'link' => '/pengumuman',
                'dibuat_oleh' => $superadminId, 'waktu_dibuat' => now()->subDays(3),
            ]
        );

        if ($kelasXiIpa && $kelasX) {
            $penerima = User::whereIn('id', Siswa::whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelasX->idkelas))->pluck('user_iduser'))->pluck('id')->all();
            foreach ($penerima as $i => $userId) {
                NotifikasiPenerima::firstOrCreate(
                    ['notifikasi_idnotifikasi' => $notif->idnotifikasi, 'user_iduser' => $userId],
                    ['dibaca_pada' => $i < 2 ? now()->subDays(1) : null]
                );
            }
        }

        $this->command?->info('DemoPengumumanSeeder: 4 pengumuman (draf/terbit/arsip), target & pembaca, 1 notifikasi selesai.');
    }
}
