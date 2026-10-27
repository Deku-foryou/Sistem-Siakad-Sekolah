<?php

namespace App\Livewire;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Models\Polajamhari;
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

    public $pengampu_idpengampu = '';

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
    }

    public function bukaSlot(int $hari, int $jamKe): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        if (! array_key_exists($hari, Hari::NAMA)) {
            throw new DomainException('Hari harus Senin sampai Jumat.');
        }
        $this->hari = $hari;
        $this->jam_ke = $jamKe;
        $this->idjadwal = Jadwal::query()
            ->where('hari', $hari)
            ->where('jam_ke', $jamKe)
            ->whereHas('pengampu', fn ($q) => $q
                ->where('semester_idsemester', $this->filterSemester)
                ->where('kelas_idkelas', $this->filterKelas))
            ->value('idjadwal') ?? '';
        $this->pengampu_idpengampu = $this->idjadwal
            ? (string) Jadwal::findOrFail($this->idjadwal)->pengampu_idpengampu
            : '';
        $this->dispatch('show-jadwal-modal');
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JadwalService::simpan(
                (int) $this->pengampu_idpengampu,
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

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['hari', 'jam_ke', 'pengampu_idpengampu', 'idjadwal']);
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
            ->where('hari', '>=', 1)
            ->where('hari', '<=', 5)
            ->whereHas('pengampu', fn ($q) => $q
                ->where('semester_idsemester', $this->filterSemester ?: 0)
                ->when($this->filterKelas, fn ($sub) => $sub->where('kelas_idkelas', $this->filterKelas)))
            ->get()
            ->keyBy(fn (Jadwal $row) => $row->hari.'|'.$row->jam_ke);

        $pengampus = Pengampu::with(['mapel', 'guru'])
            ->where('semester_idsemester', $this->filterSemester ?: 0)
            ->where('kelas_idkelas', $this->filterKelas ?: 0)
            ->orderBy('mapel_idmapel')
            ->get();

        return view('livewire.jadwal-component', [
            'semesters' => $semesters,
            'kelasOptions' => $kelas,
            'mappings' => $mappings,
            'periods' => $periods,
            'times' => $times,
            'jadwals' => $jadwals,
            'pengampus' => $pengampus,
            'hariOptions' => Hari::NAMA,
        ]);
    }
}
