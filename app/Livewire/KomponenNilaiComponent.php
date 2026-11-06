<?php

namespace App\Livewire;

use App\Models\KomponenNilai;
use App\Models\Mapel;
use App\Models\Semester;
use App\Services\KomponenNilaiService;
use App\Services\KonteksAkademikService;
use DomainException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KomponenNilaiComponent extends Component
{
    use LivewireAlert {
        alert as protected;
        confirm as protected;
        flash as protected;
    }

    public $filterSemester = '';

    public $mapelDipilih = '';

    /** @var array<int, array{idkomponen_nilai: ?int, nama: string, bobot: float|int|string, urutan: int, aktif: bool}> */
    public $baris = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->filterSemester = KonteksAkademikService::ambil()->semester?->idsemester
            ?? Semester::where('aktif', true)->value('idsemester')
            ?? Semester::query()->orderByDesc('idsemester')->value('idsemester')
            ?? '';
    }

    public function updatedFilterSemester(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->mapelDipilih = '';
        $this->baris = [];
    }

    public function updatedMapelDipilih(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->muatKonfigurasi();
    }

    public function tambahBaris(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $urutan = 1;
        foreach ($this->baris as $row) {
            $urutan = max($urutan, (int) ($row['urutan'] ?? 0) + 1);
        }

        $this->baris[] = [
            'idkomponen_nilai' => null,
            'nama' => '',
            'bobot' => 0,
            'urutan' => $urutan,
            'aktif' => true,
        ];
    }

    public function hapusBaris(int $index): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if (! array_key_exists($index, $this->baris)) {
            return;
        }

        unset($this->baris[$index]);
        $this->baris = array_values($this->baris);
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            KomponenNilaiService::simpanKonfigurasi(
                (int) $this->filterSemester,
                (int) $this->mapelDipilih,
                $this->baris
            );
            $this->alert('success', 'Konfigurasi komponen nilai berhasil disimpan.');
            $this->muatKonfigurasi();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function muatKonfigurasi(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->baris = [];

        if ((int) $this->filterSemester < 1 || (int) $this->mapelDipilih < 1) {
            return;
        }

        $tersimpan = KomponenNilai::query()
            ->where('semester_idsemester', (int) $this->filterSemester)
            ->where('mapel_idmapel', (int) $this->mapelDipilih)
            ->orderBy('urutan')
            ->orderBy('idkomponen_nilai')
            ->get();

        if ($tersimpan->isEmpty()) {
            $this->baris = [[
                'idkomponen_nilai' => null,
                'nama' => '',
                'bobot' => 100,
                'urutan' => 1,
                'aktif' => true,
            ]];

            return;
        }

        $this->baris = $tersimpan->map(fn (KomponenNilai $komponen) => [
            'idkomponen_nilai' => (int) $komponen->idkomponen_nilai,
            'nama' => (string) $komponen->nama,
            'bobot' => (float) $komponen->bobot,
            'urutan' => (int) $komponen->urutan,
            'aktif' => (bool) $komponen->aktif,
        ])->all();
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $totalAktif = 0;
        foreach ($this->baris as $row) {
            if (! empty($row['aktif']) && is_numeric($row['bobot'] ?? null)) {
                $totalAktif += (float) $row['bobot'];
            }
        }

        $terkunci = false;
        if ((int) $this->filterSemester > 0 && (int) $this->mapelDipilih > 0) {
            $terkunci = KomponenNilai::query()
                ->where('semester_idsemester', (int) $this->filterSemester)
                ->where('mapel_idmapel', (int) $this->mapelDipilih)
                ->whereHas('nilaiSiswa')
                ->exists();
        }

        return view('livewire.komponen-nilai-component', [
            'semesters' => Semester::with('tahunajaran')->orderByDesc('idsemester')->get(),
            'mapels' => Mapel::orderBy('nama')->get(),
            'totalAktif' => round($totalAktif, 2),
            'terkunci' => $terkunci,
        ]);
    }
}
