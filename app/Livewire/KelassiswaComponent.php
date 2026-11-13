<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesImport;
use App\Livewire\Concerns\LovSiswa;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Services\LovService;
use App\Services\PenempatanService;
use App\Support\Lov;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class KelassiswaComponent extends Component
{
    use HandlesImport;
    use LivewireAlert;
    use LovSiswa;
    use WithPagination;

    // siswaId/siswaTerpilih/siswaLabel disediakan trait LovSiswa (mode multi karena $siswaIds ada)
    public array $siswaIds = [];

    public $tahunajaran_id;

    public $kelas_id;

    public $perPage = 10;

    public function mount()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $aktif = Tahunajaran::aktif();
        $this->tahunajaran_id = $aktif?->idtahunajaran;
    }

    public function boot(): void
    {
        // Livewire 3: dipanggil pada SETIAP request (termasuk update) — otorisasi tidak pernah terlewati.
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }

    public function updatedTahunajaranId()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->kelas_id = null;
        $this->resetPage();
        $this->lovReset();
    }

    public function updatedKelasId()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
        $this->lovReset();
    }

    public function tambahSiswa()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if (! $this->kelas_id || empty($this->siswaIds)) {
            $this->alert('error', 'Pilih kelas dan siswa');

            return;
        }

        $kelas = Kelas::find($this->kelas_id);
        if (! $kelas) {
            $this->alert('error', 'Kelas tidak ditemukan');

            return;
        }

        // TA yang dipilih harus valid & dimiliki kelas tersebut.
        if ($kelas->tahunajaran_idtahunajaran != $this->tahunajaran_id) {
            $this->alert('error', 'Kelas tidak sesuai dengan tahun ajaran yang dipilih');

            return;
        }

        foreach ($this->siswaIds as $sid) {
            // Validasi ulang di backend — jangan percaya ID dari browser & siswa harus AKTIF.
            $siswa = LovService::selected(Lov::KEY_SISWA, (int) $sid);
            if (! $siswa instanceof Siswa) {
                $this->alert('error', 'Siswa tidak ditemukan atau tidak aktif (ID '.(int) $sid.')');

                continue;
            }

            // Kebijakan existing (sebelumnya filter UI): siswa yang sudah punya penempatan
            // non-'pindah' pada TA ini tidak bisa ditempatkan lagi. Kini ditegakkan di backend.
            $sudahAda = Kelassiswa::where('siswa_idsiswa', $siswa->idsiswa)
                ->where('status', '!=', 'pindah')
                ->whereHas('kelas', fn ($q) => $q->where('tahunajaran_idtahunajaran', $this->tahunajaran_id))
                ->exists();
            if ($sudahAda) {
                $this->alert('error', $siswa->nama.' sudah memiliki penempatan pada tahun ajaran ini');

                continue;
            }

            $result = PenempatanService::tambah($kelas, $siswa);

            if (! $result['success']) {
                $this->alert('error', 'Gagal penempatan '.$siswa->nama.': '.implode(', ', array_values($result['errors'] ?? [])));

                continue;
            }

            $this->alert('success', 'Siswa berhasil ditempatkan ke kelas');
        }

        $this->lovReset();
    }

    public function keluarkan($idkelassiswa)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $row = Kelassiswa::find($idkelassiswa);
        if ($row) {
            $row->delete();
            $this->resetPage();
            $this->alert('success', 'Siswa dikeluarkan dari kelas');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $tahunajarans = Tahunajaran::orderBy('nama')->get();
        $kelasList = $this->tahunajaran_id
            ? Kelas::where('tahunajaran_idtahunajaran', $this->tahunajaran_id)->orderBy('nama')->get()
            : collect();

        $members = null;
        if ($this->kelas_id) {
            $members = Kelassiswa::with('siswa')
                ->where('kelas_idkelas', $this->kelas_id)
                ->paginate($this->perPage);
        }

        return view('livewire.kelassiswa-component', [
            'tahunajarans' => $tahunajarans,
            'kelasList' => $kelasList,
            'members' => $members,
        ]);
    }
}
