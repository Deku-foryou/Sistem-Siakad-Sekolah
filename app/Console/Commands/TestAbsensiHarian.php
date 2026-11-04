<?php

namespace App\Console\Commands;

use App\Models\AbsensiHarian;
use App\Models\Appmenunew;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Semester;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiHarianService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestAbsensiHarian extends Command
{
    protected $signature = 'test:absensi-harian';

    protected $description = 'Test Absensi Harian implementation';

    public function handle()
    {
        DB::beginTransaction();

        try {
            $this->info('================== ABSENSI HARIAN TEST ==================');
            $this->newLine();

            // 1. Verify table exists
            $this->info('1. Verifying absensi_harian table...');
            if (Schema::hasTable('absensi_harian')) {
                $this->line('   ✓ Table exists');
            } else {
                throw new \Exception('Table absensi_harian does not exist');
            }

            // 2. Verify model
            $this->info('2. Verifying AbsensiHarian model...');
            $testRecord = new AbsensiHarian;
            $this->line('   ✓ Model instantiated');

            // 3. Verify service
            $this->info('3. Verifying AbsensiHarianService...');
            $service = app(AbsensiHarianService::class);
            $this->line('   ✓ Service instantiated');

            // 4. Get test data
            $this->info('4. Loading test data...');
            $tahunAjaran = TahunAjaran::first();
            $semester = Semester::first();
            $kelas = Kelas::first();
            $siswaAktif = Kelassiswa::where('status', 'aktif')->first();

            if (! $tahunAjaran || ! $semester || ! $kelas || ! $siswaAktif) {
                throw new \Exception('Missing test data (tahunajaran, semester, kelas, or aktif siswa)');
            }

            $this->line("   ✓ TahunAjaran: {$tahunAjaran->tahun}");
            $this->line("   ✓ Semester: {$semester->nosemester}");
            $this->line("   ✓ Kelas: {$kelas->namakelas}");
            $this->line("   ✓ Siswa Aktif: {$siswaAktif->siswa->nama}");

            // 5. Test getSiswaAktif
            $this->info('5. Testing getSiswaAktif()...');
            $siswaList = $service->getSiswaAktif($kelas->idkelas);
            $this->line("   ✓ Retrieved {$siswaList->count()} active students");

            // 6. Test validateContext
            $this->info('6. Testing validateContext()...');
            $tanggalValid = Carbon::today()->format('Y-m-d');
            $service->validateContext($tahunAjaran->idtahunajaran, $semester->idsemester, $kelas->idkelas, $tanggalValid);
            $this->line('   ✓ Context validation passed');

            // 7. Test checkGuruAccess
            $this->info('7. Testing checkGuruAccess() for superadmin...');
            $superadmin = User::where('level', 'superadmin')->first();
            if ($service->checkGuruAccess($superadmin, $kelas->idkelas)) {
                $this->line('   ✓ Superadmin has access');
            } else {
                throw new \Exception('Superadmin should have access');
            }

            // 8. Test checkDuplicate
            $this->info('8. Testing checkDuplicate()...');
            $hasDuplicate = $service->checkDuplicate(
                $tahunAjaran->idtahunajaran,
                $semester->idsemester,
                $kelas->idkelas,
                $tanggalValid,
                $siswaAktif->siswa->idsiswa
            );
            $this->line('   ✓ Duplicate check: '.($hasDuplicate ? 'exists' : 'none'));

            // 9. Test saveAbsensiMassal
            $this->info('9. Testing saveAbsensiMassal()...');
            $absensiData = [];
            foreach ($siswaList->take(2) as $ks) {
                $absensiData[$ks->siswa->idsiswa] = [
                    'status' => 'hadir',
                    'catatan' => 'Test absensi',
                ];
            }

            $result = $service->saveAbsensiMassal(
                $tahunAjaran->idtahunajaran,
                $semester->idsemester,
                $kelas->idkelas,
                $tanggalValid,
                $absensiData,
                $superadmin->id
            );

            $this->line("   ✓ Saved {$result['count']} records");
            $this->line("   ✓ Message: {$result['message']}");

            // 10. Verify records
            $this->info('10. Verifying created records...');
            $countRecords = AbsensiHarian::where('kelas_idkelas', $kelas->idkelas)
                ->where('tanggal', $tanggalValid)
                ->count();
            $this->line("   ✓ Found {$countRecords} records in database");

            // 11. Test editAbsensi
            $this->info('11. Testing editAbsensi()...');
            $firstAbsensi = AbsensiHarian::where('kelas_idkelas', $kelas->idkelas)
                ->where('tanggal', $tanggalValid)
                ->first();

            if ($firstAbsensi) {
                $updated = $service->editAbsensi(
                    $firstAbsensi->idabsensi_harian,
                    'sakit',
                    'Edited for test',
                    $superadmin->id
                );
                $this->line("   ✓ Updated record: {$updated->status}");
            }

            // 12. Test getAbsensiKelasHari
            $this->info('12. Testing getAbsensiKelasHari()...');
            $absensiRecords = $service->getAbsensiKelasHari($kelas->idkelas, $tanggalValid);
            $this->line("   ✓ Retrieved {$absensiRecords->count()} records for kelas & hari");

            // 13. Verify menu
            $this->info('13. Verifying menu entry...');
            $menu = Appmenunew::where('url', 'absensi-harian')->first();
            if ($menu) {
                $this->line("   ✓ Menu 'Absensi Harian' created with ID: {$menu->idappmenunew}");
                $this->line("   ✓ URL: {$menu->url}");
                $this->line("   ✓ Icon: {$menu->icon}");
            } else {
                throw new \Exception('Menu entry not found');
            }

            // 14. Verify route
            $this->info('14. Verifying route...');
            $routes = collect(\Route::getRoutes())
                ->filter(fn ($route) => $route->getName() === 'absensi-harian')
                ->values();

            if ($routes->count() > 0) {
                $this->line("   ✓ Route 'absensi-harian' registered");
            } else {
                throw new \Exception("Route 'absensi-harian' not found");
            }

            // 15. Verify Livewire component
            $this->info('15. Verifying Livewire component...');
            if (class_exists('App\\Livewire\\AbsensiHarianComponent')) {
                $this->line('   ✓ AbsensiHarianComponent class exists');
            } else {
                throw new \Exception('AbsensiHarianComponent not found');
            }

            $this->newLine();
            $this->info('================== ALL TESTS PASSED ✓ ==================');
            $this->newLine();
            $this->line('Test Summary:');
            $this->line('  - Database table: OK');
            $this->line('  - Model & Service: OK');
            $this->line('  - Business logic: OK');
            $this->line('  - Menu entry: OK');
            $this->line('  - Route: OK');
            $this->line('  - Livewire component: OK');
            $this->line("  - Created test records: {$countRecords} absensi entries");

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ TEST FAILED: '.$e->getMessage());
            $this->line('File: '.$e->getFile());
            $this->line('Line: '.$e->getLine());
        } finally {
            DB::rollBack();
            $this->newLine();
            $this->line('[Transaction rolled back - no permanent changes made]');
        }
    }
}
