<?php

namespace Database\Seeders;

use App\Models\Beasiswa;
use App\Models\BeasiswaSiswa;
use App\Models\Denda;
use App\Models\DendaPengajuan;
use App\Models\Kelas;
use App\Models\KoreksiPengajuan;
use App\Models\Notifikasi;
use App\Models\NotifikasiPenerima;
use App\Models\PaymentAttempt;
use App\Models\Pembayaran;
use App\Models\PembayaranAlokasi;
use App\Models\RefundPengajuan;
use App\Models\RekonsiliasiHasil;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanAdjustment;
use App\Models\TagihanPengingat;
use App\Models\Tahunajaran;
use App\Models\Tarif;
use App\Models\TarifRiwayat;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Keuangan demo (sintetis) — tarif, beasiswa, tagihan, pembayaran (manual & saldo),
 * payment_attempt, alokasi, denda, pengingat, refund/koreksi (pending) & rekonsiliasi.
 *
 * PRINSIP LEDGER:
 * - pembayaran manual berstatus success + alokasi ke tagihan/saldo selalu seimbang
 *   (SUM(alokasi) == nominal_dibayar).
 * - Tidak ada payment_attempt success sintetis; tidak ada panggilan Midtrans.
 * - Semua uang demo adalah angka sintetis; identifier prefix DEMO-.
 * - status tagihan dihitung ulang dari pembayaran success setelah semua insert.
 */
class DemoKeuanganSeeder extends Seeder
{
    public function run(): void
    {
        $superadminId = User::where('level', 'superadmin')->value('id');
        $adminId = User::where('username', 'demo.admin')->value('id');
        if (! $superadminId) {
            throw new \RuntimeException('DemoKeuanganSeeder: superadmin belum ada.');
        }

        $ta = Tahunajaran::where('nama', '2026/2027')->first();
        $sem = Semester::where('tahunajaran_idtahunajaran', $ta?->idtahunajaran)->where('nama', 'Ganjil')->first();
        if (! $ta || ! $sem) {
            throw new \RuntimeException('DemoKeuanganSeeder: TA/Semester Ganjil belum ada.');
        }

        $kelas = Kelas::where('tahunajaran_idtahunajaran', $ta->idtahunajaran)->get()->keyBy('nama');
        $byUsername = Siswa::with('user')->get()->keyBy(fn ($s) => $s->user?->username);
        $siswaOf = fn (string $username): ?Siswa => $byUsername->get($username);
        $kelasOf = fn (Siswa $s): ?Kelas => Kelas::where('tahunajaran_idtahunajaran', $ta->idtahunajaran)
            ->whereHas('kelassiswa', fn ($q) => $q->where('siswa_idsiswa', $s->idsiswa)->where('status', 'aktif'))
            ->first();

        // ============ Tarif ============
        $t1 = Tarif::firstOrCreate(
            ['nama' => 'SPP Reguler 2026/2027', 'jenis' => 'global'],
            ['nominal' => 350000, 'berlaku_dari' => '2026-07-01', 'berlaku_sampai' => null, 'status' => 'aktif', 'dibuat_oleh' => $superadminId]
        );
        TarifRiwayat::firstOrCreate(
            ['tarif_idtarif' => $t1->idtarif, 'aksi' => 'dibuat'],
            ['nominal_baru' => 350000, 'nama_baru' => $t1->nama, 'berlaku_dari_baru' => '2026-07-01', 'status_baru' => 'aktif', 'alasan' => 'Tarif awal TA 2026/2027 (demo)', 'oleh' => $superadminId]
        );

        $kelasXipa1 = $kelas->get('X IPA 1');
        $salsa = $siswaOf('salsa.marlina.051');
        $t2 = Tarif::firstOrCreate(
            ['nama' => 'Uang Seragam 2026', 'jenis' => 'kelas', 'kelas_idkelas' => $kelasXipa1?->idkelas],
            ['nominal' => 500000, 'berlaku_dari' => '2026-07-01', 'status' => 'aktif', 'dibuat_oleh' => $superadminId, 'alasan' => 'Uang seragam untuk kelas X (demo)']
        );
        $t3 = $salsa ? Tarif::firstOrCreate(
            ['nama' => 'SPP Khusus XII IPS 1', 'jenis' => 'individu', 'siswa_idsiswa' => $salsa->idsiswa],
            ['nominal' => 250000, 'berlaku_dari' => '2026-07-01', 'status' => 'aktif', 'dibuat_oleh' => $superadminId, 'alasan' => 'SPP khusus (demo)']
        ) : null;
        Tarif::firstOrCreate(
            ['nama' => 'SPP 2025/2026 (Nonaktif)', 'jenis' => 'global'],
            ['nominal' => 300000, 'berlaku_dari' => '2025-07-01', 'berlaku_sampai' => '2026-06-30', 'status' => 'nonaktif', 'dibuat_oleh' => $superadminId]
        );

        // ============ Beasiswa + penerima ============
        $b1 = Beasiswa::firstOrCreate(['nama' => 'Beasiswa Prestasi 50%'], ['jenis' => 'persen', 'nilai' => 50, 'berlaku_dari' => '2026-07-01', 'status' => 'aktif', 'dibuat_oleh' => $superadminId, 'alasan' => 'Potongan 50% untuk siswa berprestasi (demo)']);
        $b2 = Beasiswa::firstOrCreate(['nama' => 'Beasiswa Yatim Piatu'], ['jenis' => 'nominal', 'nilai' => 100000, 'berlaku_dari' => '2026-07-01', 'status' => 'aktif', 'dibuat_oleh' => $superadminId, 'alasan' => 'Potongan Rp 100.000 (demo)']);
        $b3 = Beasiswa::firstOrCreate(['nama' => 'Beasiswa Pembebasan SPP 2026'], ['jenis' => 'nominal', 'nilai' => 350000, 'berlaku_dari' => '2026-07-01', 'status' => 'aktif', 'dibuat_oleh' => $superadminId, 'alasan' => 'Pembebasan SPP penuh (demo)']);

        foreach ([
            ['andre.santoso.001', $b1], ['karin.wijaya.057', $b2], ['farah.azizah.038', $b3],
        ] as [$username, $beasiswa]) {
            $siswa = $siswaOf($username);
            if ($siswa) {
                BeasiswaSiswa::firstOrCreate(
                    ['beasiswa_idbeasiswa' => $beasiswa->idbeasiswa, 'siswa_idsiswa' => $siswa->idsiswa],
                    ['berlaku_dari' => '2026-07-01', 'status' => 'aktif', 'dibuat_oleh' => $superadminId, 'alasan' => 'Penerima beasiswa (demo)']
                );
            }
        }

        // ============ Helper pembuat tagihan ============
        $buatTagihan = function (Siswa $s, string $jenis, string $periode, int $nominal, int $diskonPersen, int $diskonNominal, array $tarifSnap, ?string $jatuhTempo, ?string $catatan) use ($superadminId, $ta, $sem, $kelasOf) {
            $kelasAktif = $kelasOf($s);
            $total = max(0, $nominal - $diskonNominal - (int) floor($nominal * $diskonPersen / 100));

            return Tagihan::firstOrCreate(
                ['siswa_idsiswa' => $s->idsiswa, 'jenis' => $jenis, 'periode' => $periode],
                array_filter([
                    'nominal' => $nominal,
                    'diskon_persen' => $diskonPersen,
                    'diskon_nominal' => $diskonNominal,
                    'total_tagihan' => $total,
                    'tarif_idtarif' => $tarifSnap['id'] ?? null,
                    'tarif_nama' => $tarifSnap['nama'] ?? null,
                    'tarif_jenis' => $tarifSnap['jenis'] ?? null,
                    'tarif_nominal' => $tarifSnap['nominal'] ?? null,
                    'kelas_idkelas' => $kelasAktif?->idkelas,
                    'kelas_nama' => $kelasAktif?->nama,
                    'tahunajaran_idtahunajaran' => $ta->idtahunajaran,
                    'semester_idsemester' => $sem->idsemester,
                    'status' => 'belum_dibayar',
                    'tanggal_terbit' => $tarifSnap['terbit'] ?? '2026-11-01',
                    'tanggal_jatuh_tempo' => $jatuhTempo ?? '2026-11-10',
                    'catatan' => $catatan,
                    'dibuat_oleh' => $superadminId,
                ], fn ($v) => $v !== null)
            );
        };

        // ============ Tagihan SPP 2026-11 (semua siswa) ============
        $spp = [];
        foreach ($byUsername as $username => $s) {
            $nominal = 350000;
            $diskonP = 0;
            $diskonN = 0;
            $tarifSnap = ['id' => $t1->idtarif, 'nama' => $t1->nama, 'jenis' => 'global', 'nominal' => 350000, 'terbit' => '2026-11-01'];
            $catatan = null;

            if ($username === 'andre.santoso.001') {
                $diskonP = 50;
                $catatan = 'Beasiswa Prestasi 50% (demo)';
            } elseif ($username === 'salsa.marlina.051' && $t3) {
                $nominal = 250000;
                $tarifSnap = ['id' => $t3->idtarif, 'nama' => $t3->nama, 'jenis' => 'individu', 'nominal' => 250000, 'terbit' => '2026-11-01'];
            } elseif ($username === 'farah.azizah.038') {
                $diskonN = 350000;
                $catatan = 'Pembebasan SPP penuh via beasiswa (demo)';
            }

            $spp[$username] = $buatTagihan($s, 'spp', '2026-11', $nominal, $diskonP, $diskonN, $tarifSnap, '2026-11-10', $catatan);
        }

        // ============ Tagihan pendaftaran (X, 2026-07) ============
        $pendaftaran = [];
        foreach (['X IPA 1', 'X IPS 1'] as $kelasNama) {
            $kelasObj = $kelas->get($kelasNama);
            if (! $kelasObj) {
                continue;
            }
            $siswaKelas = Siswa::whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelasObj->idkelas)->where('status', 'aktif'))->get();
            foreach ($siswaKelas as $s) {
                $username = $s->user->username;
                $pendaftaran[$username] = $buatTagihan($s, 'pendaftaran', '2026-07', 500000, 0, 0, ['id' => $t1->idtarif, 'nama' => 'Uang Pendaftaran 2026', 'jenis' => 'global', 'nominal' => 500000, 'terbit' => '2026-07-01'], '2026-07-31', null);
            }
        }

        // ============ Tagihan seragam (X, 2026-08) — T2 snapshot ============
        $seragam = [];
        foreach (['X IPA 1', 'X IPS 1'] as $kelasNama) {
            $kelasObj = $kelas->get($kelasNama);
            if (! $kelasObj) {
                continue;
            }
            $siswaKelas = Siswa::whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelasObj->idkelas)->where('status', 'aktif'))->get();
            foreach ($siswaKelas as $s) {
                $username = $s->user->username;
                $seragam[$username] = $buatTagihan($s, 'seragam', '2026-08', 500000, 0, 0, ['id' => $t2->idtarif, 'nama' => $t2->nama, 'jenis' => 'kelas', 'nominal' => 500000, 'terbit' => '2026-08-01'], '2026-08-31', null);
            }
        }

        // ============ Tagihan ujian (XI & XII, 2026-11) ============
        $ujian = [];
        foreach (['XI IPA 1', 'XI IPS 1', 'XII IPA 1', 'XII IPS 1'] as $kelasNama) {
            $kelasObj = $kelas->get($kelasNama);
            if (! $kelasObj) {
                continue;
            }
            $siswaKelas = Siswa::whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelasObj->idkelas)->where('status', 'aktif'))->get();
            foreach ($siswaKelas as $s) {
                $username = $s->user->username;
                $ujian[$username] = $buatTagihan($s, 'ujian', '2026-11', 150000, 0, 0, ['id' => $t1->idtarif, 'nama' => 'Biaya Ujian 2026/2027', 'jenis' => 'global', 'nominal' => 150000, 'terbit' => '2026-11-05'], '2026-11-20', null);
            }
        }

        // ============ Helper pembayaran (manual) + alokasi ============
        // $alokasi: [['tagihan_id' => id|null, 'jenis' => 'tagihan'|'saldo', 'nominal' => n], ...]
        $bayar = function (Tagihan $tagihan, Siswa $s, string $metode, int $nominalDibayar, string $tanggal, array $alokasi, string $referensi, string $provider = 'manual') use ($superadminId) {
            $pb = Pembayaran::firstOrCreate(
                ['referensi' => $referensi],
                [
                    'tagihan_idtagihan' => $tagihan->idtagihan,
                    'siswa_idsiswa' => $s->idsiswa,
                    'nominal_dibayar' => $nominalDibayar,
                    'biaya_gateway' => 0,
                    'metode' => $metode,
                    'provider' => $provider,
                    'status' => 'success',
                    'dibayar_pada' => $tanggal.' 08:00:00',
                    'tanggal_pembayaran' => $tanggal,
                    'payer_jenis' => 'siswa',
                    'payer_nama' => $s->nama,
                    'nama_pembayar' => $s->nama,
                    'diinput_oleh' => $superadminId,
                    'diverifikasi_oleh' => $superadminId,
                    'diverifikasi_pada' => $tanggal.' 09:00:00',
                ]
            );

            foreach ($alokasi as $a) {
                PembayaranAlokasi::firstOrCreate(
                    ['pembayaran_idpembayaran' => $pb->idpembayaran, 'tagihan_idtagihan' => $a['tagihan_id'], 'jenis' => $a['jenis'], 'nominal' => $a['nominal']],
                    ['siswa_idsiswa' => $s->idsiswa, 'tagihan_idtagihan' => $a['tagihan_id'], 'jenis' => $a['jenis'], 'nominal' => $a['nominal']]
                );
            }

            return $pb;
        };

        // ============ Pembayaran pendaftaran (X) ============
        foreach ($pendaftaran as $username => $t) {
            $s = $siswaOf($username);
            if (in_array($username, ['yoga.saputra.012', 'bayu.nugroho.003'], true)) {
                // Overpay 600.000: 500.000 tagihan + 100.000 saldo
                $bayar($t, $s, 'transfer', 600000, '2026-07-05', [
                    ['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => 500000],
                    ['tagihan_id' => null, 'jenis' => 'saldo', 'nominal' => 100000],
                ], 'DEMO-PMT-'.$t->idtagihan);
            } else {
                $bayar($t, $s, 'transfer', 500000, '2026-07-05', [
                    ['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => 500000],
                ], 'DEMO-PMT-'.$t->idtagihan);
            }
        }

        // ============ Pembayaran seragam (X) ============
        $seragamLunas = ['andre.santoso.001', 'ayu.lestari.002', 'citra.anggraini.004', 'dimas.pratama.005', 'erika.putri.006', 'farhan.maulana.007', 'gita.permata.008', 'rizky.ramadhan.009', 'sinta.dewi.010', 'dika.saputra.011', 'yoga.saputra.012', 'nabila.azzahra.013', 'rian.firmansyah.014', 'lestari.wulandari.017'];
        foreach ($seragamLunas as $username) {
            $t = $seragam[$username];
            $s = $siswaOf($username);
            $bayar($t, $s, 'transfer', 500000, '2026-08-05', [['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => 500000]], 'DEMO-PMT-'.$t->idtagihan);
        }
        $seragamPartial = ['bayu.nugroho.003', 'fajar.nugroho.016', 'dinda.amelia.019', 'arif.ramadhan.020'];
        foreach ($seragamPartial as $username) {
            $t = $seragam[$username];
            $s = $siswaOf($username);
            $bayar($t, $s, 'transfer', 250000, '2026-08-07', [['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => 250000]], 'DEMO-PMT-'.$t->idtagihan);
        }

        // ============ Pembayaran SPP 2026-11 ============
        // a) lunas via transfer
        $sppLunas = ['andre.santoso.001', 'dimas.pratama.005', 'farhan.maulana.007', 'rizky.ramadhan.009', 'nabila.azzahra.013', 'rian.firmansyah.014', 'putri.rahma.021', 'ilham.firdaus.022', 'nia.kurniasih.023', 'faqih.ramadhan.026', 'bima.wijaya.031', 'rayhan.firdaus.035', 'rafi.syahputra.039', 'raka.ramadhan.041', 'galih.prasetyo.042', 'bela.safitri.043', 'intan.permata.047', 'zaki.mubarak.050', 'akbar.firmansyah.052', 'alya.maharani.055', 'ridho.pratama.060'];
        foreach ($sppLunas as $username) {
            $t = $spp[$username];
            $s = $siswaOf($username);
            $nominal = $username === 'andre.santoso.001' ? 175000 : 350000;
            $bayar($t, $s, 'transfer', $nominal, '2026-11-02', [['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => $nominal]], 'DEMO-PMT-'.$t->idtagihan);
        }
        // yoga: saldo 100.000 (kelebihan pendaftaran) + transfer 250.000 -> SPP lunas
        $tYoga = $spp['yoga.saputra.012'];
        $sYoga = $siswaOf('yoga.saputra.012');
        $bayar($tYoga, $sYoga, 'saldo', 100000, '2026-11-02', [['tagihan_id' => $tYoga->idtagihan, 'jenis' => 'tagihan', 'nominal' => 100000]], 'DEMO-PMT-'.$tYoga->idtagihan.'-S', 'saldo');
        $bayar($tYoga, $sYoga, 'transfer', 250000, '2026-11-02', [['tagihan_id' => $tYoga->idtagihan, 'jenis' => 'tagihan', 'nominal' => 250000]], 'DEMO-PMT-'.$tYoga->idtagihan);
        // salsa: SPP individu 250.000 lunas
        $tSalsa = $spp['salsa.marlina.051'];
        $sSalsa = $siswaOf('salsa.marlina.051');
        $bayar($tSalsa, $sSalsa, 'transfer', 250000, '2026-11-02', [['tagihan_id' => $tSalsa->idtagihan, 'jenis' => 'tagihan', 'nominal' => 250000]], 'DEMO-PMT-'.$tSalsa->idtagihan);
        // b) dibayar_sebagian SPP
        $bayar($spp['bayu.nugroho.003'], $siswaOf('bayu.nugroho.003'), 'saldo', 100000, '2026-11-02', [['tagihan_id' => $spp['bayu.nugroho.003']->idtagihan, 'jenis' => 'tagihan', 'nominal' => 100000]], 'DEMO-PMT-'.$spp['bayu.nugroho.003']->idtagihan, 'saldo');
        $bayar($spp['tania.maharani.015'], $siswaOf('tania.maharani.015'), 'transfer', 200000, '2026-11-04', [['tagihan_id' => $spp['tania.maharani.015']->idtagihan, 'jenis' => 'tagihan', 'nominal' => 200000]], 'DEMO-PMT-'.$spp['tania.maharani.015']->idtagihan);
        $bayar($spp['rangga.wijaya.018'], $siswaOf('rangga.wijaya.018'), 'transfer', 150000, '2026-11-04', [['tagihan_id' => $spp['rangga.wijaya.018']->idtagihan, 'jenis' => 'tagihan', 'nominal' => 150000]], 'DEMO-PMT-'.$spp['rangga.wijaya.018']->idtagihan);
        $bayar($spp['dinda.amelia.019'], $siswaOf('dinda.amelia.019'), 'transfer', 175000, '2026-11-04', [['tagihan_id' => $spp['dinda.amelia.019']->idtagihan, 'jenis' => 'tagihan', 'nominal' => 175000]], 'DEMO-PMT-'.$spp['dinda.amelia.019']->idtagihan);
        // farah.azizah.038 (B3 pembebasan penuh): total 0, tanpa pembayaran

        // ============ Pembayaran ujian (XI & XII) ============
        $ujianLunas = ['putri.rahma.021', 'ilham.firdaus.022', 'nia.kurniasih.023', 'faqih.ramadhan.026', 'bima.wijaya.031', 'rayhan.firdaus.035', 'raka.ramadhan.041', 'galih.prasetyo.042', 'intan.permata.047', 'zaki.mubarak.050', 'akbar.firmansyah.052', 'alya.maharani.055'];
        foreach ($ujianLunas as $username) {
            $t = $ujian[$username];
            $s = $siswaOf($username);
            if ($username === 'putri.rahma.021') {
                // overpay 200.000 -> 150.000 tagihan + 50.000 saldo (belum dialokasikan)
                $bayar($t, $s, 'transfer', 200000, '2026-11-06', [
                    ['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => 150000],
                    ['tagihan_id' => null, 'jenis' => 'saldo', 'nominal' => 50000],
                ], 'DEMO-PMT-'.$t->idtagihan);
            } else {
                $bayar($t, $s, 'transfer', 150000, '2026-11-06', [['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => 150000]], 'DEMO-PMT-'.$t->idtagihan);
            }
        }
        $ujianPartial = ['ratih.safitri.025' => 100000, 'rifqi.ramadhan.054' => 100000];
        foreach ($ujianPartial as $username => $nominal) {
            $t = $ujian[$username] ?? null;
            if (! $t) {
                continue;
            }
            $s = $siswaOf($username);
            $bayar($t, $s, 'transfer', $nominal, '2026-11-07', [['tagihan_id' => $t->idtagihan, 'jenis' => 'tagihan', 'nominal' => $nominal]], 'DEMO-PMT-'.$t->idtagihan);
        }

        // ============ Manual antrean (pending verifikasi) ============
        $pendingVerifikasi = [
            ['tania.maharani.015', $seragam['tania.maharani.015'] ?? null, 250000],
            ['rangga.wijaya.018', $seragam['rangga.wijaya.018'] ?? null, 250000],
            ['arif.ramadhan.020', $spp['arif.ramadhan.020'] ?? null, 175000],
        ];
        foreach ($pendingVerifikasi as [$username, $tagihan, $nominal]) {
            if (! $tagihan) {
                continue;
            }
            $s = $siswaOf($username);
            Pembayaran::firstOrCreate(
                ['referensi' => 'DEMO-PMT-'.$tagihan->idtagihan.'-PENDING'],
                [
                    'tagihan_idtagihan' => $tagihan->idtagihan,
                    'siswa_idsiswa' => $s->idsiswa,
                    'nominal_dibayar' => $nominal,
                    'biaya_gateway' => 0,
                    'metode' => 'transfer',
                    'provider' => 'manual',
                    'status' => 'pending',
                    'payer_jenis' => 'siswa',
                    'payer_nama' => $s->nama,
                    'nama_pembayar' => $s->nama,
                    'diinput_oleh' => $superadminId,
                ]
            );
        }

        // ============ Denda: tania berlaku, rangga dibebaskan ============
        $taniaT = $seragam['tania.maharani.015'] ?? null;
        if ($taniaT) {
            $denda1 = Denda::firstOrCreate(
                ['tagihan_idtagihan' => $taniaT->idtagihan],
                ['metode' => 'persen', 'nilai' => 2, 'nominal_denda' => 10000, 'status' => 'berlaku']
            );
            $taniaT->forceFill(['denda_id' => $denda1->iddenda])->save();
        }

        $ranggaT = $seragam['rangga.wijaya.018'] ?? null;
        if ($ranggaT) {
            $denda2 = Denda::firstOrCreate(
                ['tagihan_idtagihan' => $ranggaT->idtagihan],
                ['metode' => 'persen', 'nilai' => 2, 'nominal_denda' => 10000, 'status' => 'dibebaskan', 'dibebaskan_pada' => '2026-09-25 10:00:00', 'dibebaskan_oleh' => $superadminId, 'alasan_dibebaskan' => 'Kendala keuangan orang tua (contoh demo)']
            );
            $ranggaT->forceFill(['denda_id' => $denda2->iddenda])->save();

            DendaPengajuan::firstOrCreate(
                ['denda_iddenda' => $denda2->iddenda, 'tagihan_idtagihan' => $ranggaT->idtagihan, 'siswa_idsiswa' => $ranggaT->siswa_idsiswa],
                [
                    'nominal_denda' => 10000,
                    'alasan' => 'Persetujuan keringanan denda (demo)',
                    'status' => 'terproses',
                    'diajukan_oleh' => $adminId,
                    'diproses_oleh' => $superadminId,
                    'diproses_pada' => '2026-09-25 09:00:00',
                    'catatan_proses' => 'Disetujui setelah verifikasi admin (demo)',
                ]
            );

            $adjustmentExists = TagihanAdjustment::where('tagihan_idtagihan', $ranggaT->idtagihan)
                ->where('jenis', 'denda_dibebaskan')
                ->exists();
            if (! $adjustmentExists) {
                // Terapkan penurunan hanya sekali; idempoten untuk re-run seeder.
                $totalLama = (int) $ranggaT->total_tagihan;
                $terpakai = (int) Pembayaran::where('tagihan_idtagihan', $ranggaT->idtagihan)->where('status', 'success')->sum('nominal_dibayar');
                $penurunan = max(0, min(10000, $totalLama - $terpakai));
                $totalBaru = $totalLama - $penurunan;
                if ($totalBaru !== $totalLama) {
                    $ranggaT->forceFill(['total_tagihan' => $totalBaru])->save();
                }
                TagihanAdjustment::create(
                    ['tagihan_idtagihan' => $ranggaT->idtagihan, 'jenis' => 'denda_dibebaskan', 'oleh' => $superadminId, 'nominal_lama' => $totalLama, 'nominal_baru' => $totalBaru, 'alasan' => 'Pembebasan denda seragam (demo)'],
                );
            }
        }

        // ============ Pengingat tagihan (seragam belum dibayar) ============
        foreach (['tania.maharani.015', 'rangga.wijaya.018'] as $username) {
            $t = $seragam[$username] ?? null;
            if (! $t) {
                continue;
            }
            $s = $siswaOf($username);
            $penerimaId = $s->user?->id;
            if (! $penerimaId) {
                continue;
            }
            $notif = Notifikasi::firstOrCreate(
                ['judul' => 'Pengingat Tagihan Seragam', 'sumber_tabel' => 'tagihan', 'sumber_id' => $t->idtagihan],
                [
                    'isi' => 'Tagihan seragam sebesar Rp 500.000 belum dibayar. Jatuh tempo 2026-08-31.',
                    'jenis' => 'keuangan', 'prioritas' => 'sedang',
                    'link' => '/datatagihan', 'dibuat_oleh' => $superadminId, 'waktu_dibuat' => now()->subDays(5),
                ]
            );
            NotifikasiPenerima::firstOrCreate(['notifikasi_idnotifikasi' => $notif->idnotifikasi, 'user_iduser' => $penerimaId], ['dibaca_pada' => null]);
            TagihanPengingat::firstOrCreate(
                ['tagihan_idtagihan' => $t->idtagihan, 'tahap' => 'H+1', 'penerima_user_iduser' => $penerimaId],
                ['siswa_idsiswa' => $s->idsiswa, 'notifikasi_idnotifikasi' => $notif->idnotifikasi, 'dikirim_pada' => now()->subDays(4)]
            );
        }

        // ============ Payment attempt sintetis (bukan transaksi nyata) ============
        // Hanya created/pending/failed/expired — sengaja TIDAK ada attempt success sintetis.
        $attemptSiswa = ['ayu.lestari.002', 'erika.putri.006', 'sinta.dewi.010', 'arif.ramadhan.020', 'citra.anggraini.004', 'dika.saputra.011', 'salsabila.putri.034', 'gita.permata.008', 'dhea.ananda.036', 'tania.maharani.015', 'rangga.wijaya.018', 'dinda.amelia.019'];
        $attemptStatus = ['pending', 'pending', 'pending', 'pending', 'failed', 'failed', 'failed', 'expired', 'expired', 'created', 'created', 'created'];
        foreach ($attemptSiswa as $i => $username) {
            $s = $siswaOf($username);
            $t = $spp[$username] ?? $ujian[$username] ?? null;
            if (! $s || ! $t) {
                continue;
            }
            $nominal = max(0, (int) $t->total_tagihan) ?: (int) $t->nominal;
            $orderId = 'DEMO-'.strtoupper(substr(md5('demo-attempt-'.$t->idtagihan.'-'.$i), 0, 15));
            PaymentAttempt::firstOrCreate(
                ['order_id' => $orderId],
                [
                    'siswa_idsiswa' => $s->idsiswa,
                    'tagihan_idtagihan' => $t->idtagihan,
                    'public_token' => substr(md5('token-'.$orderId), 0, 64),
                    'provider' => 'midtrans',
                    'metode' => 'va',
                    'nominal' => $nominal,
                    'biaya_gateway' => null, // belum diketahui — tidak dikarang
                    'status' => $attemptStatus[$i],
                    'payer_jenis' => $i % 4 === 3 ? 'wali' : 'siswa',
                    'payer_nama' => $s->nama,
                    'snap_token' => null,
                    'snap_redirect_url' => null,
                    'payload_request' => ['order_id' => $orderId, 'gross_amount' => $nominal],
                    'payload_response' => null,
                    'expired_pada' => in_array($attemptStatus[$i], ['expired', 'pending', 'created'], true) ? now()->addDays(1) : null,
                ]
            );
        }

        // ============ Refund & koreksi (pending antrean) ============
        // Refund pending: kelebihan pendaftaran Bayu Nugroho (sumber: pembayaran transfer pendaftaran)
        $bayu = $siswaOf('bayu.nugroho.003');
        if ($bayu && isset($pendaftaran['bayu.nugroho.003'])) {
            $bayuPendaftaran = Pembayaran::where('referensi', 'DEMO-PMT-'.$pendaftaran['bayu.nugroho.003']->idtagihan)->first();
        } else {
            $bayuPendaftaran = null;
        }
        if ($bayu && $bayuPendaftaran) {
            RefundPengajuan::firstOrCreate(
                ['pembayaran_idpembayaran' => $bayuPendaftaran->idpembayaran, 'status' => 'pending'],
                [
                    'siswa_idsiswa' => $bayu->idsiswa,
                    'tagihan_idtagihan' => $pendaftaran['bayu.nugroho.003']->idtagihan,
                    'nominal_refund' => 50000,
                    'alasan' => 'Kelebihan bayar (contoh demo)',
                    'metode' => 'transfer_manual',
                    'diajukan_oleh' => $adminId,
                ]
            );
        }

        // Koreksi pending: Intan Permata — nominal usulan lebih rendah
        $intan = $siswaOf('intan.permata.047');
        if ($intan && isset($ujian['intan.permata.047'])) {
            $intanPembayaran = Pembayaran::where('referensi', 'DEMO-PMT-'.$ujian['intan.permata.047']->idtagihan)->first();
        } else {
            $intanPembayaran = null;
        }
        if ($intan && $intanPembayaran) {
            KoreksiPengajuan::firstOrCreate(
                ['pembayaran_idpembayaran' => $intanPembayaran->idpembayaran, 'jenis' => 'nominal', 'diajukan_oleh' => $adminId],
                [
                    'siswa_idsiswa' => $intan->idsiswa,
                    'tagihan_idtagihan' => $ujian['intan.permata.047']->idtagihan,
                    'nominal_usulan' => (int) $intanPembayaran->nominal_dibayar - 10000,
                    'metode_usulan' => 'transfer',
                    'tanggal_pembayaran_usulan' => '2026-11-06',
                    'nama_pembayar_usulan' => $intan->nama,
                    'alasan' => 'Verifikasi salah nominal (contoh demo)',
                    'snapshot_sebelum' => ['pembayaran' => ['nominal_dibayar' => (int) $intanPembayaran->nominal_dibayar, 'metode' => $intanPembayaran->metode], 'alokasi' => [['jenis' => 'tagihan', 'nominal' => (int) $intanPembayaran->nominal_dibayar]]],
                    'status' => 'pending',
                ]
            );
        }

        // ============ Rekonsiliasi (hasil demo, bersih) ============
        RekonsiliasiHasil::firstOrCreate(
            ['periode' => '2026-11', 'dijalankan_oleh' => $superadminId],
            [
                'dijalankan_pada' => now()->subDay(),
                'jumlah_pemeriksaan' => 12,
                'jumlah_temuan' => 0,
                'jumlah_kritis' => 0,
                'ringkasan' => ['per_kategori' => [], 'total_ditemukan' => 0, 'total_tersimpan' => 0, 'terpotong' => []],
            ]
        );

        // ============ Hitung ulang status tagihan dari pembayaran success ============
        foreach (Tagihan::whereIn('jenis', ['spp', 'ujian', 'seragam', 'pendaftaran'])->get() as $t) {
            if ($t->status === 'dibatalkan') {
                continue;
            }
            $dibayar = (int) Pembayaran::where('tagihan_idtagihan', $t->idtagihan)->where('status', 'success')->sum('nominal_dibayar');
            $total = (int) $t->total_tagihan;
            if ($total === 0) {
                $status = 'lunas'; // pembebasan penuh (demo)
            } elseif ($dibayar >= $total) {
                $status = 'lunas';
            } elseif ($dibayar > 0) {
                $status = 'dibayar_sebagian';
            } else {
                $status = 'belum_dibayar';
            }
            if ($t->status !== $status) {
                $t->forceFill(['status' => $status])->save();
            }
        }

        $this->command?->info('DemoKeuanganSeeder: tarif/beasiswa/tagihan/pembayaran/alokasi/denda/pengingat/attempt/refund/koreksi/rekonsiliasi selesai.');
    }
}
