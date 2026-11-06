<?php

namespace App\Console\Commands;

use App\Models\AbsensiHarian;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Semester;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiHarianService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditAbsensiHarian extends Command
{
    protected $signature = 'audit:absensi-harian';

    protected $description = 'Audit keamanan implementasi Absensi Harian';

    public function handle()
    {
        DB::beginTransaction();

        try {
            $this->info('==================== AUDIT KEAMANAN ====================');
            $this->newLine();

            $service = app(AbsensiHarianService::class);
            $superadmin = User::where('level', 'superadmin')->first();
            $guru = User::where('level', 'guru')->first();

            if (! $superadmin) {
                throw new \Exception('Superadmin user not found');
            }

            $tahunAjaran = TahunAjaran::first();
            $semester = Semester::first();
            $kelas = Kelas::first();
            $tanggalHari = Carbon::today()->format('Y-m-d');

            // TEST 1: Validasi siswa_idsiswa di kelas
            $this->line('1. TEST: Validasi siswa_idsiswa di kelas');
            $this->line('   Ambil siswa AKTIF di kelas...');
            $siswaAktif = Kelassiswa::where('kelas_idkelas', $kelas->idkelas)
                ->where('status', 'aktif')
                ->first();

            if (! $siswaAktif) {
                $this->line('   ⚠️ Tidak ada siswa aktif di kelas, skip test');
            } else {
                $this->line("   ✓ Siswa aktif: {$siswaAktif->siswa->nama}");

                $siswaIdle = Kelassiswa::where('kelas_idkelas', $kelas->idkelas)
                    ->where('status', '!=', 'aktif')
                    ->first();

                if ($siswaIdle) {
                    $this->line("   Found non-active siswa: {$siswaIdle->siswa->nama}");
                    $this->line('   Coba submit absensi untuk siswa tidak aktif...');

                    try {
                        $absensiData = [
                            $siswaIdle->siswa->idsiswa => [
                                'status' => 'hadir',
                                'catatan' => 'Test bypass',
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

                        $this->line('   ⚠️ MASALAH: Service tidak validasi siswa aktif dalam saveAbsensiMassal!');
                        $this->line("      Result: {$result['message']}");

                        $created = AbsensiHarian::where('siswa_idsiswa', $siswaIdle->siswa->idsiswa)
                            ->where('kelas_idkelas', $kelas->idkelas)
                            ->where('tanggal', $tanggalHari)
                            ->exists();

                        if ($created) {
                            $this->error('   ⚠️ KRITIS: Absensi untuk siswa non-aktif berhasil disimpan!');
                        } else {
                            $this->line('   ✓ Database FK constraint menolak');
                        }
                    } catch (\Exception $e) {
                        $this->line("   ✓ Exception: {$e->getMessage()}");
                    }
                }
            }

            // TEST 2: Akses guru
            $this->newLine();
            $this->line('2. TEST: Akses guru ke kelas');
            if (! $guru) {
                $this->line('   ℹ️ Tidak ada guru user, skip test');
            } else {
                $hasAccess = $service->checkGuruAccess(
                    $guru,
                    $tahunAjaran->idtahunajaran,
                    $semester->idsemester,
                    $kelas->idkelas
                );
                $this->line('   Result: '.($hasAccess ? 'AKSES DIBERIKAN' : 'AKSES DITOLAK'));
            }

            // TEST 3: Status validation
            $this->newLine();
            $this->line('3. TEST: Status validation');
            $this->line('   Coba simpan dengan status invalid: tidak_hadir...');
            try {
                if ($siswaAktif) {
                    $absensiData = [
                        $siswaAktif->siswa->idsiswa => [
                            'status' => 'tidak_hadir',
                            'catatan' => 'Test',
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

                    $this->error('   ⚠️ MASALAH: Status invalid diterima!');
                }
            } catch (\Exception $e) {
                $this->line("   ✓ Ditolak: {$e->getMessage()}");
            }

            // TEST 4: Tanggal masa depan
            $this->newLine();
            $this->line('4. TEST: Tanggal masa depan');
            $tanggalMasaDepan = Carbon::tomorrow()->format('Y-m-d');
            $this->line("   Coba validateContext dengan tanggal: {$tanggalMasaDepan}...");
            try {
                $service->validateContext(
                    $tahunAjaran->idtahunajaran,
                    $semester->idsemester,
                    $kelas->idkelas,
                    $tanggalMasaDepan,
                    $superadmin
                );
                $this->error('   ⚠️ MASALAH: Tanggal masa depan diterima!');
            } catch (\Exception $e) {
                $this->line("   ✓ Ditolak: {$e->getMessage()}");
            }

            // TEST 5: Tanggal invalid format
            $this->newLine();
            $this->line('5. TEST: Tanggal invalid format');
            try {
                $service->validateContext(
                    $tahunAjaran->idtahunajaran,
                    $semester->idsemester,
                    $kelas->idkelas,
                    'invalid-date',
                    $superadmin
                );
                $this->error('   ⚠️ MASALAH: Format tanggal invalid diterima!');
            } catch (\Exception $e) {
                $this->line("   ✓ Ditolak: {$e->getMessage()}");
            }

            // TEST 6: Konteks validasi
            $this->newLine();
            $this->line('6. TEST: Konteks validasi (semester != tahunajaran)');
            $semesterBeda = Semester::where('tahunajaran_idtahunajaran', '!=', $tahunAjaran->idtahunajaran)->first();
            if ($semesterBeda) {
                try {
                    $service->validateContext(
                        $tahunAjaran->idtahunajaran,
                        $semesterBeda->idsemester,
                        $kelas->idkelas,
                        $tanggalHari,
                        $superadmin
                    );
                    $this->error('   ⚠️ MASALAH: Konteks tidak sesuai diterima!');
                } catch (\Exception $e) {
                    $this->line("   ✓ Ditolak: {$e->getMessage()}");
                }
            } else {
                $this->line('   ℹ️ Tidak ada semester dari tahun ajaran berbeda');
            }

            // TEST 7: H+1 deadline
            $this->newLine();
            $this->line('7. TEST: H+1 deadline calculation');
            $hari1Lalu = Carbon::yesterday()->format('Y-m-d');
            $hari2Lalu = Carbon::now()->subDays(2)->format('Y-m-d');

            if ($siswaAktif) {
                try {
                    $absensi1 = AbsensiHarian::create([
                        'tahunajaran_idtahunajaran' => $tahunAjaran->idtahunajaran,
                        'semester_idsemester' => $semester->idsemester,
                        'kelas_idkelas' => $kelas->idkelas,
                        'tanggal' => $hari1Lalu,
                        'siswa_idsiswa' => $siswaAktif->siswa->idsiswa,
                        'status' => 'hadir',
                        'catatan' => 'Audit test 1',
                        'diinput_oleh' => $superadmin->id,
                    ]);

                    if ($guru) {
                        $canEdit = $service->checkEditDeadline($guru, $absensi1);
                        $this->line('   Guru bisa edit kemarin: '.($canEdit ? 'YA' : 'TIDAK'));
                        if ($canEdit) {
                            $this->line('   ✓ H+1 allows yesterday');
                        }
                    }

                    $absensi2 = AbsensiHarian::create([
                        'tahunajaran_idtahunajaran' => $tahunAjaran->idtahunajaran,
                        'semester_idsemester' => $semester->idsemester,
                        'kelas_idkelas' => $kelas->idkelas,
                        'tanggal' => $hari2Lalu,
                        'siswa_idsiswa' => $siswaAktif->siswa->idsiswa,
                        'status' => 'hadir',
                        'diinput_oleh' => $superadmin->id,
                    ]);

                    if ($guru) {
                        $canEdit2 = $service->checkEditDeadline($guru, $absensi2);
                        $this->line('   Guru bisa edit 2 hari lalu: '.($canEdit2 ? 'YA' : 'TIDAK'));
                        if (! $canEdit2) {
                            $this->line('   ✓ H+1 blocks 2+ days');
                        }
                    }
                } catch (\Exception $e) {
                    $this->line("   Exception: {$e->getMessage()}");
                }
            }

            // TEST 8: Duplikasi
            $this->newLine();
            $this->line('8. TEST: Duplikasi prevention');
            if ($siswaAktif) {
                try {
                    $tanggalUnik = Carbon::now()->subDays(5)->format('Y-m-d');

                    $dataFirst = [
                        $siswaAktif->siswa->idsiswa => [
                            'status' => 'hadir',
                            'catatan' => 'First',
                        ],
                    ];

                    $result1 = $service->saveAbsensiMassal(
                        $tahunAjaran->idtahunajaran,
                        $semester->idsemester,
                        $kelas->idkelas,
                        $tanggalUnik,
                        $dataFirst,
                        $superadmin->id,
                        $superadmin
                    );

                    $this->line('   First save: OK');

                    try {
                        $result2 = $service->saveAbsensiMassal(
                            $tahunAjaran->idtahunajaran,
                            $semester->idsemester,
                            $kelas->idkelas,
                            $tanggalUnik,
                            $dataFirst,
                            $superadmin->id,
                            $superadmin
                        );
                        $this->error('   ⚠️ MASALAH: Duplikasi diterima!');
                    } catch (\Exception $e) {
                        $this->line('   ✓ Duplikasi ditolak');
                    }
                } catch (\Exception $e) {
                    $this->line("   First save failed: {$e->getMessage()}");
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
            $this->line('[Transaction rolled back - no changes persisted]');
        }
    }
}
