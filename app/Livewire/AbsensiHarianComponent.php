<?php

namespace App\Livewire;

use App\Models\AbsensiHarian;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\TahunAjaran;
use App\Services\AbsensiHarianService;
use Carbon\Carbon;
use Livewire\Component;

class AbsensiHarianComponent extends Component
{
    public $tahunAjaranId;

    public $semesterId;

    public $kelasId;

    public $tanggal;

    public $tahunAjaranList = [];

    public $semesterList = [];

    public $kelasList = [];

    public $siswaList = [];

    public $absensiData = [];

    public $errorMessage = '';

    public $successMessage = '';

    public $isLoaded = false;

    public $isEditMode = false;

    public $editAbsensiId = null;

    protected $service;

    public function boot()
    {
        $this->service = app(AbsensiHarianService::class);
    }

    public function mount()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        // Set default tanggal ke hari ini
        $this->tanggal = Carbon::today()->format('Y-m-d');

        // Load pilihan
        $this->loadTahunAjaran();
    }

    public function render()
    {
        return view('livewire.absensi-harian-component');
    }

    private function loadTahunAjaran()
    {
        $this->tahunAjaranList = TahunAjaran::query()
            ->orderBy('idtahunajaran', 'desc')
            ->get()
            ->mapWithKeys(fn ($ta) => [$ta->idtahunajaran => "{$ta->nama}"])
            ->toArray();

        // Set ke tahun ajaran aktif / terbaru
        if (! $this->tahunAjaranId && count($this->tahunAjaranList)) {
            $this->tahunAjaranId = array_key_first($this->tahunAjaranList);
            $this->loadSemester();
        }
    }

    public function updatedTahunAjaranId()
    {
        $this->tahunAjaranId = (int) $this->tahunAjaranId;
        $this->semesterId = null;
        $this->kelasId = null;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->loadSemester();
    }

    private function loadSemester()
    {
        if (! $this->tahunAjaranId) {
            $this->semesterList = [];

            return;
        }

        $this->semesterList = Semester::query()
            ->where('tahunajaran_idtahunajaran', $this->tahunAjaranId)
            ->orderByDesc('idsemester')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->idsemester => "Semester {$s->nama}"])
            ->toArray();

        if (! $this->semesterId && count($this->semesterList)) {
            $this->semesterId = array_key_first($this->semesterList);
            $this->loadKelas();
        }
    }

    public function updatedSemesterId()
    {
        $this->semesterId = (int) $this->semesterId;
        $this->kelasId = null;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->loadKelas();
    }

    private function loadKelas()
    {
        if (! $this->tahunAjaranId || ! $this->semesterId) {
            $this->kelasList = [];

            return;
        }

        $query = Kelas::query()
            ->where('tahunajaran_idtahunajaran', $this->tahunAjaranId);

        // Jika guru (bukan superadmin), filter ke kelas yang dia ampuh
        $user = auth()->user();
        if ($user->level === 'guru' && $user->guru) {
            $guruId = $user->guru->idguru;
            $query->where(function ($q) use ($guruId) {
                $q->where('guru_idguru', $guruId);
            });
        }

        $this->kelasList = $query
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn ($k) => [$k->idkelas => $k->nama])
            ->toArray();

        if (! $this->kelasId && count($this->kelasList)) {
            $this->kelasId = array_key_first($this->kelasList);
            $this->loadSiswaAbsensi();
        }
    }

    public function updatedKelasId()
    {
        $this->kelasId = (int) $this->kelasId;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->loadSiswaAbsensi();
    }

    public function updatedTanggal()
    {
        if (! $this->kelasId) {
            return;
        }

        $this->errorMessage = '';
        $this->successMessage = '';
        $this->isEditMode = false;
        $this->editAbsensiId = null;
        $this->loadSiswaAbsensi();
    }

    private function loadSiswaAbsensi()
    {
        if (! $this->tahunAjaranId || ! $this->semesterId || ! $this->kelasId || ! $this->tanggal) {
            $this->siswaList = [];
            $this->absensiData = [];
            $this->isLoaded = false;

            return;
        }

        try {
            // Validasi context
            $this->service->validateContext($this->tahunAjaranId, $this->semesterId, $this->kelasId, $this->tanggal);

            // Load siswa aktif
            $kelassiswa = $this->service->getSiswaAktif($this->kelasId);
            $this->siswaList = $kelassiswa->map(fn ($ks) => [
                'idsiswa' => $ks->siswa->idsiswa,
                'nama' => $ks->siswa->nama,
            ])->toArray();

            // Load absensi yang sudah ada
            $absensiRecords = $this->service->getAbsensiKelasHari($this->kelasId, $this->tanggal);
            $this->absensiData = [];

            foreach ($this->siswaList as $siswa) {
                $record = $absensiRecords->firstWhere('siswa_idsiswa', $siswa['idsiswa']);
                $this->absensiData[$siswa['idsiswa']] = [
                    'status' => $record?->status ?? 'hadir',
                    'catatan' => $record?->catatan ?? '',
                    'idabsensi_harian' => $record?->idabsensi_harian ?? null,
                ];
            }

            $this->isLoaded = true;
            $this->errorMessage = '';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->isLoaded = false;
            $this->siswaList = [];
            $this->absensiData = [];
        }
    }

    public function simpanAbsensi()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        if (! $this->tahunAjaranId || ! $this->semesterId || ! $this->kelasId || ! $this->tanggal) {
            $this->errorMessage = 'Pilihan tahun ajaran, semester, kelas, dan tanggal harus lengkap.';

            return;
        }

        $user = auth()->user();

        // Cek akses guru
        if ($user->level === 'guru' && ! $this->service->checkGuruAccess($user, $this->kelasId)) {
            $this->errorMessage = 'Anda tidak berwenang mengakses kelas ini.';

            return;
        }

        // Cek deadline edit (H+1)
        if ($user->level === 'guru') {
            $tanggalObj = Carbon::parse($this->tanggal);
            $daysOld = Carbon::today()->diffInDays($tanggalObj, false);

            if ($daysOld < -1 || $daysOld > 0) {
                $this->errorMessage = 'Guru hanya dapat mencatat absensi untuk hari ini dan kemarin (H+1 deadline).';

                return;
            }
        }

        try {
            // Pisahkan data: baru dan update
            $dataToCreate = [];
            $dataToUpdate = [];

            foreach ($this->absensiData as $siswaId => $data) {
                $idabsensi = $data['idabsensi_harian'] ?? null;

                if ($idabsensi) {
                    $dataToUpdate[$idabsensi] = $data;
                } else {
                    $dataToCreate[$siswaId] = $data;
                }
            }

            // Create new records
            if (! empty($dataToCreate)) {
                $result = $this->service->saveAbsensiMassal(
                    $this->tahunAjaranId,
                    $this->semesterId,
                    $this->kelasId,
                    $this->tanggal,
                    $dataToCreate,
                    $user->id,
                    $user  // Pass user object untuk service-level validation
                );
            }

            // Update existing records
            foreach ($dataToUpdate as $idabsensi => $data) {
                $absensi = AbsensiHarian::findOrFail($idabsensi);

                // Cek akses edit (guru H+1 deadline)
                if ($user->level === 'guru' && ! $this->service->checkEditDeadline($user, $absensi)) {
                    throw new \Exception("Record absensi {$idabsensi} sudah melewati deadline edit.");
                }

                $this->service->editAbsensi(
                    $idabsensi,
                    $data['status'],
                    $data['catatan'],
                    $user->id,
                    $user  // Pass user object untuk service-level validation
                );
            }

            $this->successMessage = 'Absensi berhasil disimpan.';
            $this->errorMessage = '';
            $this->loadSiswaAbsensi();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: '.$e->getMessage();
        }
    }

    public function toggleEditAbsensi($absensiId)
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        if ($this->isEditMode && $this->editAbsensiId === (int) $absensiId) {
            $this->isEditMode = false;
            $this->editAbsensiId = null;
        } else {
            $this->isEditMode = true;
            $this->editAbsensiId = (int) $absensiId;
        }
    }

    public function resetForm()
    {
        $this->absensiData = [];
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->isEditMode = false;
        $this->editAbsensiId = null;
        $this->loadSiswaAbsensi();
    }
}
