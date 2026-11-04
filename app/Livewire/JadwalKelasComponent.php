<?php

namespace App\Livewire;

use App\Models\Jamhari;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Semester;
use App\Services\JadwalKelasService;
use App\Support\Hari;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class JadwalKelasComponent extends Component
{
    use LivewireAlert;

    public $semester;

    public $filterJurusan = '';

    public $filterKelas = '';

    public $idjadwalkelas;

    public $jamhari_id;

    public $mapel_id;

    public $guru_id;

    /** ID kelas yang dibuka dari halaman Kelas (mode lihat, hanya baca). */
    public $kelasLihat;

    /** True bila halaman dibuka dari tombol "Lihat Jadwal" pada halaman Kelas. */
    public $modeLihat = false;

    protected $rules = [
        'mapel_id' => 'required|integer|exists:mapel,idmapel',
        'guru_id' => 'required|integer|exists:guru,idguru',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->semester = Semester::where('aktif', true)->value('idsemester')
            ?? Semester::query()->orderByDesc('idsemester')->value('idsemester');

        $this->muatKelasDariQuery();
    }

    /**
     * Mode "Lihat Jadwal" dari halaman Kelas: /jadwalkelas?kelas={idkelas}.
     *
     * Kelas dan semester langsung ditetapkan sehingga pengguna tidak perlu
     * memilih kelas manual. ID yang tidak valid ditolak dengan 404.
     */
    private function muatKelasDariQuery(): void
    {
        if (! request()->query->has('kelas')) {
            return;
        }

        $nilai = request()->query('kelas');

        if ($nilai === '') {
            return;
        }

        $idkelas = filter_var($nilai, FILTER_VALIDATE_INT);

        if ($idkelas === false || $idkelas <= 0) {
            abort(404);
        }

        $kelas = Kelas::with('tahunajaran')->where('idkelas', $idkelas)->first();

        if (! $kelas) {
            abort(404);
        }

        $this->modeLihat = true;
        $this->kelasLihat = (int) $kelas->idkelas;
        $this->filterKelas = $kelas->idkelas;
        $this->filterJurusan = $kelas->jurusan_id ?? '';
        $this->semester = $this->semesterUntukKelas($kelas);
    }

    /**
     * Semester yang ditampilkan saat masuk dari halaman Kelas.
     *
     * Semester aktif dipakai bila satu tahun ajaran dengan kelas. Bila tidak,
     * dipakai semester milik tahun ajaran kelas agar jadwal lama tetap terbaca.
     */
    private function semesterUntukKelas(Kelas $kelas): ?int
    {
        $semesterAktif = $this->semester ? Semester::find($this->semester) : null;

        if ($semesterAktif && (int) $semesterAktif->tahunajaran_idtahunajaran === (int) $kelas->tahunajaran_idtahunajaran) {
            return (int) $semesterAktif->idsemester;
        }

        return Semester::query()
            ->where('tahunajaran_idtahunajaran', $kelas->tahunajaran_idtahunajaran)
            ->orderByDesc('aktif')
            ->orderByDesc('idsemester')
            ->value('idsemester');
    }

    public function updatedSemester(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_if($this->modeLihat, 403);
        $this->filterKelas = '';
        $this->resetForm();
    }

    public function updatedFilterJurusan(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_if($this->modeLihat, 403);
        $this->filterKelas = '';
        $this->resetForm();
    }

    public function updatedFilterKelas(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_if($this->modeLihat, 403);
        $this->resetForm();
    }

    public function updatedMapelId(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->guru_id = '';
    }

    public function resetForm(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idjadwalkelas', 'jamhari_id', 'mapel_id', 'guru_id']);
    }

    public function store(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_if($this->modeLihat, 403);
        $this->validate();
        try {
            JadwalKelasService::simpan(
                (int) $this->semester,
                (int) $this->jamhari_id,
                (int) $this->mapel_id,
                (int) $this->guru_id,
                $this->idjadwalkelas ? (int) $this->idjadwalkelas : null
            );
            $this->alert('success', 'Jadwal berhasil disimpan');
            $this->dispatch('close-jadwalkelas-modal');
            $this->resetForm();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function edit(int $id, int $jamhariId): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_if($this->modeLihat, 403);
        $jamhari = Jamhari::query()
            ->where('idjamhari', $jamhariId)
            ->where('kelas_id', $this->filterKelas)
            ->firstOrFail();

        $jadwal = $id > 0
            ? DB::table('jadwalkelas')
                ->where('idjadwalkelas', $id)
                ->where('semester_idsemester', $this->semester)
                ->where('jamhari_idjamhari', $jamhari->idjamhari)
                ->first()
            : null;

        if ($id > 0 && ! $jadwal) {
            abort(404);
        }

        if ($jadwal) {
            $this->idjadwalkelas = $jadwal->idjadwalkelas;
            $this->mapel_id = $jadwal->mapel_idmapel;
            $this->guru_id = $jadwal->guru_idguru;
        } else {
            $this->resetForm();
        }
        $this->jamhari_id = $jamhariId;
        $this->dispatch('show-jadwalkelas-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_if($this->modeLihat, 403);
        if ($this->idjadwalkelas) {
            JadwalKelasService::hapus($this->idjadwalkelas);
            $this->alert('success', 'Jadwal berhasil dihapus');
            $this->dispatch('close-jadwalkelas-modal');
            $this->resetForm();
        }
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $semesters = Semester::all();
        $jurusans = Jurusan::where('aktif', true)->orderBy('nama')->get();

        $semesterTerpilih = $this->semester
            ? Semester::with('tahunajaran')->where('idsemester', $this->semester)->first()
            : null;
        $tahunajaranId = $semesterTerpilih?->tahunajaran_idtahunajaran;

        $kelases = collect();
        $kelasTerpilih = null;

        if ($this->modeLihat) {
            // Kelas sudah ditetapkan dari halaman Kelas; tidak perlu pilih manual.
            $kelasTerpilih = $this->kelasLihat
                ? Kelas::with('tahunajaran')->where('idkelas', $this->kelasLihat)->first()
                : null;
        } else {
            $kelases = $this->filterJurusan && $tahunajaranId
                ? Kelas::where('jurusan_id', $this->filterJurusan)
                    ->where('tahunajaran_idtahunajaran', $tahunajaranId)
                    ->orderBy('nama')
                    ->get()
                : collect();

            $kelasTerpilih = $this->filterKelas
                ? Kelas::where('idkelas', $this->filterKelas)
                    ->where('jurusan_id', $this->filterJurusan)
                    ->where('tahunajaran_idtahunajaran', $tahunajaranId)
                    ->first()
                : null;
        }

        $hari = Hari::NAMA;
        $jam = $kelasTerpilih && $semesterTerpilih
            ? Jamhari::where('kelas_id', $kelasTerpilih->idkelas)->orderBy('hari')->orderBy('mulai')->get()
            : collect();

        $jamPerHari = $jam->groupBy('hari');
        $urutanJamPerHari = $jamPerHari->map(
            fn ($jamHari) => $jamHari->sortBy('mulai')->values()
        );
        $jumlahBarisJam = $urutanJamPerHari->max(fn ($jamHari) => $jamHari->count()) ?? 0;

        $mapels = DB::table('mapel')
            ->select('idmapel', 'kode', 'nama')
            ->orderBy('nama')
            ->get();
        $gurus = collect();
        if ($this->mapel_id) {
            $gurus = DB::table('gurumapel as gm')
                ->join('guru as g', 'gm.guru_idguru', '=', 'g.idguru')
                ->where('gm.mapel_idmapel', $this->mapel_id)
                ->where('g.status', 'aktif')
                ->select('g.idguru', 'g.nama', 'g.kode_guru')
                ->distinct()
                ->orderBy('g.nama')
                ->get();
        }

        $jadwalData = collect();
        if ($kelasTerpilih && $semesterTerpilih) {
            $jadwalData = DB::table('jadwalkelas as j')
                ->join('jamhari as jh', 'j.jamhari_idjamhari', '=', 'jh.idjamhari')
                ->join('mapel as m', 'j.mapel_idmapel', '=', 'm.idmapel')
                ->join('guru as g', 'j.guru_idguru', '=', 'g.idguru')
                ->where('j.semester_idsemester', $semesterTerpilih->idsemester)
                ->where('jh.kelas_id', $kelasTerpilih->idkelas)
                ->select('j.*', 'jh.jam_ke', 'jh.hari', 'm.nama as mapel_nama', 'g.nama as guru_nama', 'g.kode_guru')
                ->get();
        }

        return view('livewire.jadwal-kelas-component', [
            'semesters' => $semesters,
            'jurusans' => $jurusans,
            'kelases' => $kelases,
            'jamPerHari' => $urutanJamPerHari,
            'jumlahBarisJam' => $jumlahBarisJam,
            'hari' => $hari,
            'jadwalData' => $jadwalData,
            'hariNama' => $hari,
            'mapels' => $mapels,
            'gurus' => $gurus,
            'modeLihat' => $this->modeLihat,
            'kelasTerpilih' => $kelasTerpilih,
            'semesterTerpilih' => $semesterTerpilih,
        ]);
    }
}
