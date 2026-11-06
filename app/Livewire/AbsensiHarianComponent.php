<?php

namespace App\Livewire;

use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Tahunajaran;
use App\Services\AbsensiHarianService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
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
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        $this->service = app(AbsensiHarianService::class);
    }

    public function mount()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        $this->tanggal = Carbon::today()->format('Y-m-d');
        $this->loadTahunAjaran();
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        return view('livewire.absensi-harian-component');
    }

    private function loadTahunAjaran()
    {
        $this->tahunAjaranList = Tahunajaran::query()
            ->orderBy('idtahunajaran', 'desc')
            ->get()
            ->mapWithKeys(fn ($ta) => [$ta->idtahunajaran => $ta->nama])
            ->toArray();

        if (! $this->tahunAjaranId && count($this->tahunAjaranList)) {
            $this->tahunAjaranId = array_key_first($this->tahunAjaranList);
            $this->loadSemester();
        }
    }

    public function updatedTahunAjaranId()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        $this->tahunAjaranId = (int) $this->tahunAjaranId;
        $this->semesterId = null;
        $this->kelasId = null;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->isLoaded = false;
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
            ->mapWithKeys(fn ($semester) => [$semester->idsemester => "Semester {$semester->nama}"])
            ->toArray();

        if (! $this->semesterId && count($this->semesterList)) {
            $this->semesterId = array_key_first($this->semesterList);
            $this->loadKelas();
        }
    }

    public function updatedSemesterId()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        $this->semesterId = (int) $this->semesterId;
        $this->kelasId = null;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->isLoaded = false;
        $this->loadKelas();
    }

    private function loadKelas()
    {
        $this->kelasList = [];
        $this->kelasId = null;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->isLoaded = false;

        if (! $this->tahunAjaranId || ! $this->semesterId) {
            return;
        }

        try {
            $kelas = $this->service->getKelasTersedia(
                auth()->user(),
                (int) $this->tahunAjaranId,
                (int) $this->semesterId
            );
            $this->kelasList = $kelas
                ->mapWithKeys(fn (Kelas $item) => [$item->idkelas => $item->nama])
                ->toArray();

            if (count($this->kelasList)) {
                $this->kelasId = array_key_first($this->kelasList);
                $this->loadSiswaAbsensi();
            }
        } catch (DomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function updatedKelasId()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        $this->kelasId = (int) $this->kelasId;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->loadSiswaAbsensi();
    }

    public function updatedTanggal()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

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

        $user = auth()->user();

        try {
            $this->service->validateContext(
                (int) $this->tahunAjaranId,
                (int) $this->semesterId,
                (int) $this->kelasId,
                (string) $this->tanggal,
                $user
            );

            $kelassiswa = $this->service->getSiswaAktif(
                (int) $this->kelasId,
                $user,
                (int) $this->tahunAjaranId,
                (int) $this->semesterId
            );
            $this->siswaList = $kelassiswa->map(fn ($item) => [
                'idsiswa' => $item->siswa->idsiswa,
                'nama' => $item->siswa->nama,
            ])->toArray();

            $absensiRecords = $this->service->getAbsensiKelasHari(
                (int) $this->tahunAjaranId,
                (int) $this->semesterId,
                (int) $this->kelasId,
                (string) $this->tanggal,
                $user
            );
            $this->absensiData = [];

            foreach ($this->siswaList as $siswa) {
                $record = $absensiRecords->firstWhere('siswa_idsiswa', $siswa['idsiswa']);
                $this->absensiData[$siswa['idsiswa']] = [
                    'status' => $record?->status ?? 'hadir',
                    'catatan' => $record?->catatan ?? '',
                    'idabsensi_harian' => $record?->idabsensi_harian,
                ];
            }

            $this->isLoaded = true;
            $this->errorMessage = '';
        } catch (DomainException $exception) {
            $this->errorMessage = $exception->getMessage();
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

        try {
            DB::transaction(function () use ($user) {
                if (! is_array($this->absensiData)) {
                    throw new DomainException('Data absensi tidak valid.');
                }

                $dataToCreate = [];
                $dataToUpdate = [];

                foreach ($this->absensiData as $siswaId => $data) {
                    if (! is_array($data)) {
                        throw new DomainException("Data absensi siswa {$siswaId} tidak valid.");
                    }

                    $absensiId = $data['idabsensi_harian'] ?? null;
                    if ($absensiId) {
                        $dataToUpdate[$absensiId] = $data;
                    } else {
                        $dataToCreate[$siswaId] = $data;
                    }
                }

                if ($dataToCreate) {
                    $this->service->saveAbsensiMassal(
                        (int) $this->tahunAjaranId,
                        (int) $this->semesterId,
                        (int) $this->kelasId,
                        (string) $this->tanggal,
                        $dataToCreate,
                        (int) $user->getKey(),
                        $user
                    );
                }

                foreach ($dataToUpdate as $absensiId => $data) {
                    $this->service->authorizeRecordForEdit(
                        $user,
                        (int) $absensiId,
                        (int) $this->tahunAjaranId,
                        (int) $this->semesterId,
                        (int) $this->kelasId,
                        (string) $this->tanggal
                    );
                    $this->service->editAbsensi(
                        (int) $absensiId,
                        $data['status'] ?? '',
                        $data['catatan'] ?? null,
                        (int) $user->getKey(),
                        $user,
                        (int) $this->tahunAjaranId,
                        (int) $this->semesterId,
                        (int) $this->kelasId,
                        (string) $this->tanggal
                    );
                }
            });

            $this->successMessage = 'Absensi berhasil disimpan.';
            $this->errorMessage = '';
            $this->loadSiswaAbsensi();
        } catch (DomainException $exception) {
            $this->errorMessage = 'Error: '.$exception->getMessage();
        }
    }

    public function toggleEditAbsensi($absensiId)
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        if (! is_numeric($absensiId) || (int) $absensiId < 1) {
            abort(404);
        }

        if ($this->isEditMode && $this->editAbsensiId === (int) $absensiId) {
            $this->isEditMode = false;
            $this->editAbsensiId = null;

            return;
        }

        try {
            $this->service->authorizeRecordForEdit(
                auth()->user(),
                (int) $absensiId,
                (int) $this->tahunAjaranId,
                (int) $this->semesterId,
                (int) $this->kelasId,
                (string) $this->tanggal
            );
        } catch (DomainException $exception) {
            $this->errorMessage = $exception->getMessage();

            return;
        }

        $this->isEditMode = true;
        $this->editAbsensiId = (int) $absensiId;
    }

    public function resetForm()
    {
        abort_unless(auth()->user()?->level === 'superadmin' || auth()->user()?->level === 'guru', 403);

        $this->absensiData = [];
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->isEditMode = false;
        $this->editAbsensiId = null;
        $this->loadSiswaAbsensi();
    }
}
