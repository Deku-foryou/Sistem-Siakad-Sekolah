<?php

namespace App\Console\Commands;

use App\Models\AbsensiHarian;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Pengampu;
use App\Models\Semester;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiHarianService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAbsensiHardeningFix extends Command
{
    protected $signature = 'verify:absensi-hardening';

    protected $description = 'Verify T1 & T2 fixes for Absensi Harian';

    public function handle()
    {
        DB::beginTransaction();

        try {
            $this->info('==================== VERIFY HARDENING FIXES ====================');
            $this->newLine();

            $service = app(AbsensiHarianService::class);
            $superadmin = User::where('level', 'superadmin')->first();
            $guru = User::where('level', 'guru')->first();

            if (! $superadmin || ! $guru || ! $guru->guru) {
                $this->error('Missing test users (superadmin or guru)');

                return;
            }

            $tahunAjaran = TahunAjaran::first();
            $semester = Semester::first();
            $kelas = Kelas::first();
            $tanggalTest = Carbon::now()->subDays(5)->format('Y-m-d');

            // ===== TEST T1: Guru access validation di service =====
            $this->line('TEST T1: Guru access validation di service layer');
            $this->newLine();

            // Get kelas yang bukan kewenangannya (bukan wali kelas DAN bukan pengampu)
            $guruPengampuKelas = Pengampu::where('guru_idguru', $guru->guru->idguru)
                ->pluck('kelas_idkelas')
                ->toArray();

            $kelasLain = Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                ->where('guru_idguru', '!=', $guru->guru->idguru)
                ->whereNotIn('idkelas', $guruPengampuKelas)
                ->first();

            if (! $kelasLain) {
                $this->line('   ℹ️ Semua kelas adalah kewenangannya, skip test');
            } else {
                $this->line('   Guru mencoba saveAbsensiMassal untuk kelas bukan kewenangannya');

                $siswaKelasLain = Kelassiswa::where('kelas_idkelas', $kelasLain->idkelas)
                    ->where('status', 'aktif')
                    ->first();

                if (! $siswaKelasLain) {
                    $this->line('   ℹ️ Tidak ada siswa aktif di kelas lain');
                } else {
                    try {
                        $absensiData = [
                            $siswaKelasLain->siswa->idsiswa => [
                                'status' => 'hadir',
                                'catatan' => 'Test bypass',
                            ],
                        ];

                        $result = $service->saveAbsensiMassal(
                            $tahunAjaran->idtahunajaran,
                            $semester->idsemester,
                            $kelasLain->idkelas,
                            $tanggalTest,
                            $absensiData,
                            $guru->id,
                            $guru  // NOW service-level validation
                        );

                        $this->error('   ❌ FAIL: Service accepted unauthorized access!');
                    } catch (\Exception $e) {
                        $this->line("   ✓ PASS: Service rejected - {$e->getMessage()}");
                    }
                }
            }

            // ===== TEST T2: Siswa aktif validation di service =====
            $this->newLine();
            $this->line('TEST T2: Siswa aktif validation di service layer');
            $this->newLine();

            $siswaAktif = Kelassiswa::where('kelas_idkelas', $kelas->idkelas)
                ->where('status', 'aktif')
                ->first();

            if (! $siswaAktif) {
                $this->line('   ℹ️ Tidak ada siswa aktif di kelas');
            } else {
                // Get siswa dari kelas lain
                $siswaKelasLain = Kelassiswa::where('kelas_idkelas', '!=', $kelas->idkelas)
                    ->where('status', 'aktif')
                    ->first();

                if (! $siswaKelasLain) {
                    $this->line('   ℹ️ Tidak ada siswa dari kelas lain');
                } else {
                    $this->line('   Superadmin mencoba saveAbsensiMassal untuk siswa dari kelas lain');
                    $this->line("   Siswa: {$siswaKelasLain->siswa->nama} (dari kelas lain)");

                    try {
                        $absensiData = [
                            $siswaKelasLain->siswa->idsiswa => [
                                'status' => 'hadir',
                                'catatan' => 'Invalid siswa',
                            ],
                        ];

                        $result = $service->saveAbsensiMassal(
                            $tahunAjaran->idtahunajaran,
                            $semester->idsemester,
                            $kelas->idkelas,
                            $tanggalTest,
                            $absensiData,
                            $superadmin->id,
                            $superadmin  // Superadmin should bypass guru access check
                        );

                        $this->error('   ❌ FAIL: Service accepted non-active siswa!');
                    } catch (\Exception $e) {
                        $this->line("   ✓ PASS: Service rejected - {$e->getMessage()}");
                    }
                }
            }

            // ===== TEST T1.2: Guru edit absensi di kelas lain =====
            $this->newLine();
            $this->line('TEST T1.2: Guru editAbsensi di kelas lain');
            $this->newLine();

            if ($kelasLain && $siswaKelasLain) {
                $this->line('   Create absensi di kelas lain untuk test edit');

                // Superadmin create first
                $absensiOther = AbsensiHarian::create([
                    'tahunajaran_idtahunajaran' => $tahunAjaran->idtahunajaran,
                    'semester_idsemester' => $semester->idsemester,
                    'kelas_idkelas' => $kelasLain->idkelas,
                    'tanggal' => $tanggalTest,
                    'siswa_idsiswa' => $siswaKelasLain->siswa->idsiswa,
                    'status' => 'hadir',
                    'diinput_oleh' => $superadmin->id,
                ]);

                try {
                    $this->line('   Guru mencoba editAbsensi di kelas bukan kewenangannya');

                    $service->editAbsensi(
                        $absensiOther->idabsensi_harian,
                        'sakit',
                        'Edited',
                        $guru->id,
                        $guru,
                        $absensiOther->tahunajaran_idtahunajaran,
                        $absensiOther->semester_idsemester,
                        $absensiOther->kelas_idkelas,
                        $absensiOther->tanggal
                    );

                    $this->error('   ❌ FAIL: Service accepted unauthorized edit!');
                } catch (\Exception $e) {
                    $this->line("   ✓ PASS: Service rejected - {$e->getMessage()}");
                }
            }

            // ===== TEST T2.2: Valid siswa can be saved =====
            $this->newLine();
            $this->line('TEST T2.2: Valid siswa can be saved by superadmin');
            $this->newLine();

            if ($siswaAktif) {
                try {
                    $tanggalValid = Carbon::now()->subDays(20)->format('Y-m-d');

                    $absensiData = [
                        $siswaAktif->siswa->idsiswa => [
                            'status' => 'hadir',
                            'catatan' => 'Valid test',
                        ],
                    ];

                    $result = $service->saveAbsensiMassal(
                        $tahunAjaran->idtahunajaran,
                        $semester->idsemester,
                        $kelas->idkelas,
                        $tanggalValid,
                        $absensiData,
                        $superadmin->id,
                        $superadmin
                    );

                    $this->line("   ✓ PASS: {$result['message']}");

                    // Verify record created
                    $created = AbsensiHarian::where('siswa_idsiswa', $siswaAktif->siswa->idsiswa)
                        ->where('kelas_idkelas', $kelas->idkelas)
                        ->where('tanggal', $tanggalValid)
                        ->first();

                    if ($created) {
                        $this->line('   ✓ PASS: Record verified in database');
                    } else {
                        $this->error('   ❌ FAIL: Record not found');
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ FAIL: {$e->getMessage()}");
                }
            }

            // ===== TEST T1.3: Superadmin dapat akses semua kelas =====
            $this->newLine();
            $this->line('TEST T1.3: Superadmin can access all classes');
            $this->newLine();

            if ($kelasLain && $siswaKelasLain) {
                try {
                    $tanggalSuperadmin = Carbon::now()->subDays(3)->format('Y-m-d');

                    $absensiData = [
                        $siswaKelasLain->siswa->idsiswa => [
                            'status' => 'hadir',
                        ],
                    ];

                    $result = $service->saveAbsensiMassal(
                        $tahunAjaran->idtahunajaran,
                        $semester->idsemester,
                        $kelasLain->idkelas,
                        $tanggalSuperadmin,
                        $absensiData,
                        $superadmin->id,
                        $superadmin
                    );

                    $this->line("   ✓ PASS: Superadmin can access other classes - {$result['message']}");
                } catch (\Exception $e) {
                    $this->error("   ❌ FAIL: {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->info('==================== VERIFICATION COMPLETE ====================');

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ VERIFICATION ERROR: {$e->getMessage()}");
            $this->line("File: {$e->getFile()}");
            $this->line("Line: {$e->getLine()}");
        } finally {
            DB::rollBack();
            $this->newLine();
            $this->line('[Transaction rolled back]');
        }
    }
}
