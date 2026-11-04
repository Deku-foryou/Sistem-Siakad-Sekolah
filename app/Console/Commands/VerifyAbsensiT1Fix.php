<?php

namespace App\Console\Commands;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Semester;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiHarianService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAbsensiT1Fix extends Command
{
    protected $signature = 'verify:absensi-t1-fix';

    protected $description = 'Verify T1 fix: guru access validation in service';

    public function handle()
    {
        DB::beginTransaction();

        try {
            $this->info('==================== VERIFY T1 FIX ====================');
            $this->newLine();

            $service = app(AbsensiHarianService::class);
            $superadmin = User::where('level', 'superadmin')->first();

            if (! $superadmin) {
                $this->error('Superadmin user not found');

                return;
            }

            $tahunAjaran = TahunAjaran::first();
            $semester = Semester::first();

            // Get kelas dengan siswa aktif
            $kelasWithSiswa = Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                ->whereHas('kelassiswa', fn ($q) => $q->where('status', 'aktif'))
                ->get();

            if ($kelasWithSiswa->count() < 1) {
                $this->line('ℹ️ No classes with active students');

                return;
            }

            $kelas1 = $kelasWithSiswa->first();
            $siswaKelas1 = Kelassiswa::where('kelas_idkelas', $kelas1->idkelas)
                ->where('status', 'aktif')
                ->first();

            // Create temp guru tidak terkait dengan kelas manapun
            $tempGuru = User::create([
                'name' => 'Temp Guru T1 Test',
                'username' => 'temp_guru_t1_'.time(),
                'email' => 'temp_guru_t1_'.time().'@test.local',
                'level' => 'guru',
                'password' => bcrypt('temp123'),
                'wajib_ganti_password' => false,
            ]);

            // Create guru model for this user
            $guruModel = Guru::create([
                'nama' => 'Temp Guru T1 Test',
                'nip' => 'TEMP'.time(),
                'tanggal_lahir' => '2000-01-01',
                'user_iduser' => $tempGuru->id,
            ]);

            $this->line("Created temp guru: {$tempGuru->username} (idguru: {$guruModel->idguru})");
            $this->line("Testing access to kelas: {$kelas1->namakelas}");
            $this->newLine();

            $tanggalTest = Carbon::now()->subDays(10)->format('Y-m-d');

            // TEST 1: Temp guru (no access) tries to save absensi
            $this->line('TEST 1: Unauthorized guru tries saveAbsensiMassal');

            try {
                $absensiData = [
                    $siswaKelas1->siswa->idsiswa => [
                        'status' => 'hadir',
                        'catatan' => 'Test T1 Fix',
                    ],
                ];

                $result = $service->saveAbsensiMassal(
                    $tahunAjaran->idtahunajaran,
                    $semester->idsemester,
                    $kelas1->idkelas,
                    $tanggalTest,
                    $absensiData,
                    $tempGuru->id,
                    $tempGuru  // Pass user - service should validate access
                );

                $this->error('   ❌ FAIL: Service accepted unauthorized guru!');
                $this->line("   Result: {$result['message']}");
            } catch (\Exception $e) {
                $this->line('   ✓ PASS: Service rejected unauthorized access');
                $this->line("   Error: {$e->getMessage()}");
            }

            // TEST 2: Superadmin can still access (sanity check)
            $this->newLine();
            $this->line('TEST 2: Superadmin can access all classes (sanity check)');

            try {
                $tanggalTest2 = Carbon::now()->subDays(9)->format('Y-m-d');

                $absensiData = [
                    $siswaKelas1->siswa->idsiswa => [
                        'status' => 'hadir',
                    ],
                ];

                $result = $service->saveAbsensiMassal(
                    $tahunAjaran->idtahunajaran,
                    $semester->idsemester,
                    $kelas1->idkelas,
                    $tanggalTest2,
                    $absensiData,
                    $superadmin->id,
                    $superadmin
                );

                $this->line("   ✓ PASS: {$result['message']}");
            } catch (\Exception $e) {
                $this->error("   ❌ FAIL: {$e->getMessage()}");
            }

            $this->newLine();
            $this->info('==================== T1 VERIFICATION COMPLETE ====================');

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ ERROR: {$e->getMessage()}");
            $this->line("Line: {$e->getLine()}");
        } finally {
            DB::rollBack();
            $this->newLine();
            $this->line('[Transaction rolled back]');
        }
    }
}
