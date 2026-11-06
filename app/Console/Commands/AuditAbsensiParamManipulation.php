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

class AuditAbsensiParamManipulation extends Command
{
    protected $signature = 'audit:absensi-param-manip';

    protected $description = 'Audit parameter manipulation pada Absensi Harian';

    public function handle()
    {
        DB::beginTransaction();

        try {
            $this->info('==================== AUDIT PARAMETER MANIPULATION ====================');
            $this->newLine();

            $service = app(AbsensiHarianService::class);
            $superadmin = User::where('level', 'superadmin')->first();
            $guru = User::where('level', 'guru')->first();

            $tahunAjaran = TahunAjaran::first();
            $semester = Semester::first();
            $kelas = Kelas::first();
            $tanggalHari = Carbon::today()->format('Y-m-d');

            // TEST 1: Guru coba akses kelas yang bukan kewenangannya
            $this->line('1. TEST: Guru akses kelas di luar kewenangannya');
            if (! $guru || ! $guru->guru) {
                $this->line('   ℹ️ Guru user tidak tersedia');
            } else {
                $guruId = $guru->guru->idguru;

                // Ambil kelas yang TIDAK di-ampuh guru ini (bukan wali & bukan pengampu)
                $kelasLain = Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                    ->where('guru_idguru', '!=', $guruId)
                    ->first();

                if ($kelasLain) {
                    $this->line("   Guru mencoba akses kelas: {$kelasLain->namakelas}");
                    $hasAccess = $service->checkGuruAccess(
                        $guru,
                        $tahunAjaran->idtahunajaran,
                        $semester->idsemester,
                        $kelasLain->idkelas
                    );
                    $this->line('   Result: '.($hasAccess ? 'AKSES DIBERIKAN ⚠️' : 'AKSES DITOLAK ✓'));

                    if ($hasAccess) {
                        $this->error('   KRITIS: Guru dapat akses kelas bukan kewenangannya!');
                    }
                } else {
                    $this->line('   ℹ️ Semua kelas adalah kewenangannya');
                }
            }

            // TEST 2: Manipulasi siswaId di absensiData
            $this->newLine();
            $this->line('2. TEST: Manipulasi siswaId di absensiData');

            $siswaAktif = Kelassiswa::where('kelas_idkelas', $kelas->idkelas)
                ->where('status', 'aktif')
                ->first();

            if ($siswaAktif) {
                // Ambil siswa dari kelas lain
                $siswaLain = Kelassiswa::where('kelas_idkelas', '!=', $kelas->idkelas)
                    ->where('status', 'aktif')
                    ->first();

                if ($siswaLain) {
                    $this->line("   Coba submit absensi untuk siswa dari kelas lain: {$siswaLain->siswa->nama}");
                    $this->line("   Di kelas: {$kelas->namakelas}");

                    try {
                        $absensiData = [
                            $siswaLain->siswa->idsiswa => [
                                'status' => 'hadir',
                                'catatan' => 'Manipulasi siswa',
                            ],
                        ];

                        $result = $service->saveAbsensiMassal(
                            $tahunAjaran->idtahunajaran,
                            $semester->idsemester,
                            $kelas->idkelas,
                            $tanggalHari,
                            $absensiData,
                            $superadmin->id,
                            $superadmin
                        );

                        $this->error('   MASALAH: Absensi siswa dari kelas lain diterima!');
                        $this->line("   Result: {$result['message']}");

                        // Check FK constraint
                        $isCreated = AbsensiHarian::where('siswa_idsiswa', $siswaLain->siswa->idsiswa)
                            ->where('kelas_idkelas', $kelas->idkelas)
                            ->exists();

                        if ($isCreated) {
                            $this->error('   KRITIS: Record benar-benar tersimpan! FK tidak memvalidasi.');
                        } else {
                            $this->line('   ✓ Database FK constraint menolak (meskipun service tidak cek)');
                        }
                    } catch (\Exception $e) {
                        $this->line("   ✓ Ditolak: {$e->getMessage()}");
                    }
                } else {
                    $this->line('   ℹ️ Tidak ada siswa dari kelas lain');
                }
            }

            // TEST 3: Manipulasi kelasId via loadKelas
            $this->newLine();
            $this->line('3. TEST: Manipulasi kelasId (indirect via service)');

            if ($guru && $guru->guru) {
                $kelasLain = Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                    ->where('guru_idguru', '!=', $guru->guru->idguru)
                    ->first();

                if ($kelasLain) {
                    $this->line('   Guru coba simpan absensi untuk kelas bukan kewenangannya');
                    $this->line("   Kelas: {$kelasLain->namakelas}");

                    $siswaKelasLain = Kelassiswa::where('kelas_idkelas', $kelasLain->idkelas)
                        ->where('status', 'aktif')
                        ->first();

                    if ($siswaKelasLain) {
                        try {
                            $absensiData = [
                                $siswaKelasLain->siswa->idsiswa => [
                                    'status' => 'hadir',
                                ],
                            ];

                            $result = $service->saveAbsensiMassal(
                                $tahunAjaran->idtahunajaran,
                                $semester->idsemester,
                                $kelasLain->idkelas,
                                $tanggalHari,
                                $absensiData,
                                $guru->id,
                                $guru
                            );

                            $this->error('   MASALAH: Guru dapat submit absensi kelas bukan kewenangannya!');
                            $this->line('   (Service tidak cek akses guru di saveAbsensiMassal)');
                        } catch (\Exception $e) {
                            $this->line("   ✓ Ditolak: {$e->getMessage()}");
                        }
                    }
                }
            }

            // TEST 4: Negative atau zero ID injection
            $this->newLine();
            $this->line('4. TEST: Negative/Zero ID injection');

            try {
                $this->line('   Coba validate context dengan kelasId = -1...');
                $service->validateContext(
                    $tahunAjaran->idtahunajaran,
                    $semester->idsemester,
                    -1,
                    $tanggalHari,
                    $superadmin
                );
                $this->error('   MASALAH: Negative ID diterima!');
            } catch (\Exception $e) {
                $this->line("   ✓ Ditolak: {$e->getMessage()}");
            }

            // TEST 5: Type casting attack (string ke int)
            $this->newLine();
            $this->line('5. TEST: Type casting (string kelasId)');

            try {
                $this->line("   Coba dengan kelasId sebagai string: 'abc'...");
                $service->validateContext(
                    $tahunAjaran->idtahunajaran,
                    $semester->idsemester,
                    'abc',
                    $tanggalHari,
                    $superadmin
                );
                $this->error('   MASALAH: String ID diterima!');
            } catch (\Exception $e) {
                $this->line("   ✓ Ditolak: {$e->getMessage()}");
            }

            // TEST 6: Audit trail (diinput_oleh, diubah_oleh)
            $this->newLine();
            $this->line('6. TEST: Audit trail (diinput_oleh tracking)');

            if ($siswaAktif) {
                try {
                    $tanggalAudit = Carbon::now()->subDays(10)->format('Y-m-d');

                    $absensiData = [
                        $siswaAktif->siswa->idsiswa => [
                            'status' => 'hadir',
                            'catatan' => 'Audit test',
                        ],
                    ];

                    $result = $service->saveAbsensiMassal(
                        $tahunAjaran->idtahunajaran,
                        $semester->idsemester,
                        $kelas->idkelas,
                        $tanggalAudit,
                        $absensiData,
                        $superadmin->id,
                        $superadmin
                    );

                    $record = AbsensiHarian::where('siswa_idsiswa', $siswaAktif->siswa->idsiswa)
                        ->where('kelas_idkelas', $kelas->idkelas)
                        ->where('tanggal', $tanggalAudit)
                        ->first();

                    if ($record && $record->diinput_oleh == $superadmin->id) {
                        $this->line('   ✓ diinput_oleh correctly set to superadmin ID');
                    } else {
                        $this->error('   MASALAH: diinput_oleh tidak sesuai!');
                    }

                    // Test edit
                    $service->editAbsensi(
                        $record->idabsensi_harian,
                        'sakit',
                        'Edited',
                        $guru->id,
                        $guru,
                        $record->tahunajaran_idtahunajaran,
                        $record->semester_idsemester,
                        $record->kelas_idkelas,
                        $record->tanggal
                    );
                    $record->refresh();

                    if ($record->diubah_oleh == $guru->id) {
                        $this->line('   ✓ diubah_oleh correctly set to guru ID after edit');
                    } else {
                        $this->error('   MASALAH: diubah_oleh tidak sesuai!');
                    }
                } catch (\Exception $e) {
                    $this->line("   Exception: {$e->getMessage()}");
                }
            }

            // TEST 7: SQL Injection in catatan
            $this->newLine();
            $this->line('7. TEST: SQL Injection in catatan field');

            if ($siswaAktif) {
                try {
                    $tanggalInjection = Carbon::now()->subDays(15)->format('Y-m-d');
                    $sqlPayload = "'; DROP TABLE absensi_harian; --";

                    $absensiData = [
                        $siswaAktif->siswa->idsiswa => [
                            'status' => 'hadir',
                            'catatan' => $sqlPayload,
                        ],
                    ];

                    $result = $service->saveAbsensiMassal(
                        $tahunAjaran->idtahunajaran,
                        $semester->idsemester,
                        $kelas->idkelas,
                        $tanggalInjection,
                        $absensiData,
                        $superadmin->id,
                        $superadmin
                    );

                    // Check table still exists
                    if (\Schema::hasTable('absensi_harian')) {
                        $this->line('   ✓ Table still exists (Eloquent using parameterized queries)');
                    } else {
                        $this->error('   KRITIS: Table was dropped!');
                    }

                    $record = AbsensiHarian::where('siswa_idsiswa', $siswaAktif->siswa->idsiswa)
                        ->where('tanggal', $tanggalInjection)
                        ->first();

                    if ($record && $record->catatan === $sqlPayload) {
                        $this->line('   ✓ Payload stored as literal string (safe)');
                    }
                } catch (\Exception $e) {
                    $this->line("   ✓ Exception: {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->info('==================== AUDIT SELESAI ====================');

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ AUDIT ERROR: {$e->getMessage()}");
            $this->line("File: {$e->getFile()}");
            $this->line("Line: {$e->getLine()}");
        } finally {
            DB::rollBack();
            $this->newLine();
            $this->line('[Transaction rolled back]');
        }
    }
}
