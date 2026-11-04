<?php

namespace App\Livewire;

use App\Models\AbsensiPerjam;
use App\Models\JadwalPertemuan;
use App\Models\Kelassiswa;
use App\Services\AbsensiPerjamService;
use App\Services\JadwalPertemuanService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class AbsensiPerjamComponent extends Component
{
    public $tanggal;

    public $guruId = null;

    public $guruNama = null;

    /** @var array<int, array<string, mixed>> */
    public $pertemuanList = [];

    /** @var array<int, array<string, mixed>> */
    public $siswaList = [];

    /** @var array<int, array{status: string, keterangan: string, idabsensi_perjam: ?int}> */
    public $absensiData = [];

    public $pertemuanTerpilih = null;

    public $pertemuanId = null;

    public $errorMessage = '';

    public $successMessage = '';

    public $isLoaded = false;

    public function mount()
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        $guru = auth()->user()->guru;
        $this->guruId = $guru?->idguru;
        $this->guruNama = $guru?->nama;

        $this->tanggal = Carbon::today()->format('Y-m-d');
        $this->loadPertemuan();
    }

    public function render()
    {
        return view('livewire.absensi-perjam-component', [
            'tanggal' => $this->tanggal,
            'guruId' => $this->guruId,
            'guruNama' => $this->guruNama,
            'pertemuanList' => $this->pertemuanList,
            'siswaList' => $this->siswaList,
            'absensiData' => $this->absensiData,
            'pertemuanTerpilih' => $this->pertemuanTerpilih,
            'pertemuanId' => $this->pertemuanId,
            'errorMessage' => $this->errorMessage,
            'successMessage' => $this->successMessage,
            'isLoaded' => $this->isLoaded,
        ]);
    }

    public function updatedTanggal()
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        $parsed = \DateTime::createFromFormat('Y-m-d', (string) $this->tanggal);
        if ($parsed === false || $this->tanggal !== $parsed->format('Y-m-d')) {
            $this->tanggal = Carbon::today()->format('Y-m-d');
        }

        $this->errorMessage = '';
        $this->successMessage = '';
        $this->resetPilihan();
        $this->loadPertemuan();
    }

    public function loadPertemuan()
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        if ($this->guruId === null) {
            $this->pertemuanList = [];
            $this->errorMessage = 'Data guru untuk akun Anda tidak ditemukan. Hubungi administrator.';

            return;
        }

        try {
            app(JadwalPertemuanService::class)->generateUntukTanggal($this->tanggal);
        } catch (DomainException $e) {
            $this->pertemuanList = [];
            $this->errorMessage = $e->getMessage();

            return;
        }

        $pertemuan = JadwalPertemuan::query()
            ->with(['kelas', 'mapel', 'jadwalkelas.jamhari'])
            ->where('guru_idguru', $this->guruId)
            ->where('tanggal', $this->tanggal)
            ->get()
            ->sortBy(fn ($p) => [(int) $p->hari, (int) ($p->jam_ke ?? 999), (string) $p->waktu_mulai])
            ->values();

        $hariIni = Carbon::today();

        $this->pertemuanList = $pertemuan->map(function ($p) use ($hariIni) {
            $status = (string) $p->status;
            $isFuture = Carbon::parse($p->tanggal)->isAfter($hariIni);
            $bisaDiabsen = $status === 'terjadwal' && ! $isFuture;

            return [
                'idjadwal_pertemuan' => $p->idjadwal_pertemuan,
                'jam_ke' => $p->jam_ke,
                'waktu_mulai' => $p->waktu_mulai,
                'waktu_selesai' => $p->waktu_selesai,
                'kelas_nama' => $p->kelas?->nama,
                'mapel_nama' => $p->mapel?->nama,
                'status' => $status,
                'bisa_diabsen' => $bisaDiabsen,
            ];
        })->toArray();
    }

    public function pilihPertemuan($pertemuanId)
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        $this->errorMessage = '';
        $this->successMessage = '';

        $pertemuan = JadwalPertemuan::query()
            ->with(['kelas', 'mapel', 'jadwalkelas.jamhari'])
            ->find($pertemuanId);

        // Guru hanya boleh mengakses pertemuan yang ditugaskan kepadanya.
        abort_unless($pertemuan && (int) $pertemuan->guru_idguru === (int) $this->guruId, 403);

        $this->pertemuanId = $pertemuan->idjadwal_pertemuan;
        $this->pertemuanTerpilih = [
            'idjadwal_pertemuan' => $pertemuan->idjadwal_pertemuan,
            'jam_ke' => $pertemuan->jam_ke,
            'waktu_mulai' => $pertemuan->waktu_mulai,
            'waktu_selesai' => $pertemuan->waktu_selesai,
            'kelas_nama' => $pertemuan->kelas?->nama,
            'mapel_nama' => $pertemuan->mapel?->nama,
            'status' => $pertemuan->status,
            'tanggal' => Carbon::parse($pertemuan->tanggal)->format('d-m-Y'),
            'bisa_diabsen' => $pertemuan->status === 'terjadwal' && ! Carbon::parse($pertemuan->tanggal)->isAfter(Carbon::today()),
            'deadline_lewat' => Carbon::today()->gt(Carbon::parse($pertemuan->tanggal)->addDay()),
        ];

        $kelassiswa = Kelassiswa::query()
            ->where('kelas_idkelas', $pertemuan->kelas_idkelas)
            ->where('status', 'aktif')
            ->with('siswa')
            ->orderBy('siswa_idsiswa')
            ->get();

        $this->siswaList = $kelassiswa->map(fn ($ks) => [
            'idsiswa' => $ks->siswa->idsiswa,
            'nama' => $ks->siswa->nama,
        ])->toArray();

        $existing = AbsensiPerjam::query()
            ->where('jadwal_pertemuan_idjadwal_pertemuan', $pertemuan->idjadwal_pertemuan)
            ->get()
            ->keyBy('siswa_idsiswa');

        $this->absensiData = [];
        foreach ($this->siswaList as $siswa) {
            $record = $existing->get($siswa['idsiswa']);
            $this->absensiData[$siswa['idsiswa']] = [
                'status' => $record?->status ?? '',
                'keterangan' => $record?->keterangan ?? '',
                'idabsensi_perjam' => $record?->idabsensi_perjam,
            ];
        }

        $this->isLoaded = true;
    }

    public function simpanAbsensi()
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        if (! $this->pertemuanId || ! $this->isLoaded) {
            $this->errorMessage = 'Pilih pertemuan terlebih dahulu.';

            return;
        }

        $user = auth()->user();
        $service = app(AbsensiPerjamService::class);

        $jumlah = 0;
        DB::beginTransaction();
        try {
            foreach ($this->absensiData as $siswaId => $data) {
                $status = (string) ($data['status'] ?? '');
                $keterangan = trim((string) ($data['keterangan'] ?? ''));
                $keterangan = $keterangan === '' ? null : $keterangan;
                $idAbs = $data['idabsensi_perjam'] ?? null;

                if ($idAbs === null) {
                    // Belum pernah diabsen dan status belum dipilih: lewati (belum diabsen).
                    if ($status === '') {
                        continue;
                    }
                    $service->simpan((int) $this->pertemuanId, (int) $siswaId, $status, $keterangan, $user);
                    $jumlah++;

                    continue;
                }

                if ($status === '') {
                    throw new DomainException('Catatan yang sudah tersimpan tidak dapat dikosongkan. Pilih status yang sesuai.');
                }

                $service->edit((int) $idAbs, $status, $keterangan, $user);
                $jumlah++;
            }

            DB::commit();
        } catch (DomainException $e) {
            DB::rollBack();
            $this->errorMessage = $e->getMessage();
            $this->successMessage = '';

            return;
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            $this->errorMessage = 'Terjadi kesalahan sistem. Perubahan tidak disimpan.';
            $this->successMessage = '';

            return;
        }

        // Muat ulang data agar id record tersimpan tercermin.
        $this->pilihPertemuan($this->pertemuanId);
        $this->successMessage = "Absensi berhasil disimpan ({$jumlah} siswa).";
        $this->errorMessage = '';
    }

    public function resetPilihan()
    {
        $this->pertemuanId = null;
        $this->pertemuanTerpilih = null;
        $this->siswaList = [];
        $this->absensiData = [];
        $this->isLoaded = false;
    }
}
