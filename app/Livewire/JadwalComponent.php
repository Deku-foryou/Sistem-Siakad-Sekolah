<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Polajamhari;
use App\Models\Publikasijadwal;
use App\Models\Semester;
use App\Services\JadwalService;
use App\Support\Hari;
use DomainException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class JadwalComponent extends Component
{
    use LivewireAlert {
        alert as protected;
        confirm as protected;
        flash as protected;
    }

    public $filterSemester = '';

    public $filterKelas = '';

    public $hari = '';

    public $jam_ke = '';

    public $mapelId = '';

    public $guruId = '';

    public $idjadwal = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->filterSemester = Semester::where('aktif', true)->value('idsemester')
            ?? Semester::query()->orderByDesc('idsemester')->value('idsemester')
            ?? '';
        $tahunajaranId = $this->filterSemester
            ? Semester::where('idsemester', $this->filterSemester)->value('tahunajaran_idtahunajaran')
            : null;
        $this->filterKelas = $tahunajaranId
            ? Kelas::where('tahunajaran_idtahunajaran', $tahunajaranId)->orderBy('nama')->value('idkelas') ?? ''
            : '';
    }

    public function updatedFilterSemester(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->filterKelas = '';
        $this->mapelId = '';
        $this->guruId = '';
    }

    public function updatedFilterKelas(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->mapelId = '';
        $this->guruId = '';
    }

    public function updatedMapelId(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $pengampu = $this->mapelId && $this->filterSemester && $this->filterKelas
            ? Pengampu::where('semester_idsemester', $this->filterSemester)
                ->where('kelas_idkelas', $this->filterKelas)
                ->where('mapel_idmapel', $this->mapelId)
                ->first()
            : null;
        $this->guruId = $pengampu ? (string) $pengampu->guru_idguru : '';
    }

    public function bukaSlot(int $hari, int $jamKe): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        if (! array_key_exists($hari, Hari::NAMA)) {
            throw new DomainException('Hari harus Senin sampai Minggu.');
        }
        $this->hari = $hari;
        $this->jam_ke = $jamKe;
        $jadwal = Jadwal::query()
            ->with('pengampu')
            ->where('hari', $hari)
            ->where('jam_ke', $jamKe)
            ->whereHas('pengampu', fn ($q) => $q
                ->where('semester_idsemester', $this->filterSemester)
                ->where('kelas_idkelas', $this->filterKelas))
            ->first();
        $this->idjadwal = $jadwal?->idjadwal ?? '';
        $this->mapelId = $jadwal ? (string) $jadwal->pengampu->mapel_idmapel : '';
        $this->guruId = $jadwal ? (string) $jadwal->pengampu->guru_idguru : '';
        $this->dispatch('show-jadwal-modal');
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JadwalService::simpanDenganMapel(
                (int) $this->filterSemester,
                (int) $this->filterKelas,
                (int) $this->mapelId,
                (int) $this->guruId,
                (int) $this->hari,
                (int) $this->jam_ke,
                $this->idjadwal ? (int) $this->idjadwal : null
            );
            $this->alert('success', 'Jadwal berhasil disimpan.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function hapus(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JadwalService::hapus((int) $this->idjadwal);
            $this->alert('success', 'Jadwal berhasil dihapus.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function publikasikan(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JadwalService::publikasikan((int) $this->filterSemester, (int) $this->filterKelas);
            $this->alert('success', 'Jadwal berhasil dipublikasikan.');
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function tarikPublikasi(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JadwalService::tarikPublikasi((int) $this->filterSemester, (int) $this->filterKelas);
            $this->alert('success', 'Publikasi jadwal ditarik.');
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['hari', 'jam_ke', 'mapelId', 'guruId', 'idjadwal']);
        $this->resetValidation();
        $this->dispatch('close-jadwal-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $semesters = Semester::with('tahunajaran')->orderByDesc('idsemester')->get();
        $semester = $this->filterSemester ? Semester::with('tahunajaran')->find($this->filterSemester) : null;
        $kelas = $semester
            ? Kelas::where('tahunajaran_idtahunajaran', $semester->tahunajaran_idtahunajaran)->orderBy('nama')->get()
            : collect();
        if ($this->filterKelas && ! $kelas->contains('idkelas', (int) $this->filterKelas)) {
            $this->filterKelas = '';
        }

        $mappings = Polajamhari::with('pola.jamPelajaran')->get()->keyBy('hari');
        $gridHariOptions = array_intersect_key(Hari::NAMA, $mappings->all());
        $periods = [];
        $times = [];
        foreach ($mappings as $mapping) {
            foreach ($mapping->pola->jamPelajaran as $period) {
                if ($period->jenis === 'pelajaran') {
                    $periods[(int) $period->jam_ke] = [
                        'jam_ke' => (int) $period->jam_ke,
                    ];
                    $times[$mapping->hari.'|'.$period->jam_ke] = substr($period->mulai, 0, 5).'–'.substr($period->selesai, 0, 5);
                }
            }
        }
        ksort($periods);

        $jadwals = Jadwal::with(['pengampu.kelas', 'pengampu.mapel', 'pengampu.guru'])
            ->whereIn('hari', array_keys($gridHariOptions))
            ->whereHas('pengampu', fn ($q) => $q
                ->where('semester_idsemester', $this->filterSemester ?: 0)
                ->when($this->filterKelas, fn ($sub) => $sub->where('kelas_idkelas', $this->filterKelas)))
            ->get()
            ->keyBy(fn (Jadwal $row) => $row->hari.'|'.$row->jam_ke);

        $mapels = Mapel::orderBy('nama')->get();
        $activeGurus = Guru::where('status', 'aktif')->orderBy('nama')->get();
        $selectedPengampu = $this->mapelId && $this->filterSemester && $this->filterKelas
            ? Pengampu::with('guru')
                ->where('semester_idsemester', $this->filterSemester)
                ->where('kelas_idkelas', $this->filterKelas)
                ->where('mapel_idmapel', $this->mapelId)
                ->first()
            : null;

        $publikasiAktif = ($this->filterSemester && $this->filterKelas)
            ? Publikasijadwal::where('semester_idsemester', $this->filterSemester)
                ->where('kelas_idkelas', $this->filterKelas)
                ->where('status', 'published')
                ->orderByDesc('idpublikasijadwal')
                ->first()
            : null;
        $publikasiTerakhir = ($this->filterSemester && $this->filterKelas)
            ? Publikasijadwal::where('semester_idsemester', $this->filterSemester)
                ->where('kelas_idkelas', $this->filterKelas)
                ->orderByDesc('idpublikasijadwal')
                ->first()
            : null;

        return view('livewire.jadwal-component', [
            'semesters' => $semesters,
            'publikasiAktif' => $publikasiAktif,
            'publikasiTerakhir' => $publikasiTerakhir,
            'kelasOptions' => $kelas,
            'mappings' => $mappings,
            'periods' => $periods,
            'times' => $times,
            'jadwals' => $jadwals,
            'mapels' => $mapels,
            'activeGurus' => $activeGurus,
            'selectedPengampu' => $selectedPengampu,
            'gridHariOptions' => $gridHariOptions,
            'hariOptions' => Hari::NAMA,
        ]);
    }
}
