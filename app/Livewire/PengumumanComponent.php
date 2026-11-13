<?php

namespace App\Livewire;

use App\Livewire\Concerns\LovSiswa;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pengumuman;
use App\Models\Siswa;
use App\Services\PengumumanService;
use DomainException;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manajemen pengumuman untuk superadmin.
 */
class PengumumanComponent extends Component
{
    use LivewireAlert;
    use LovSiswa;
    use WithPagination;

    // Filter
    public string $search = '';

    public string $filterStatus = '';

    public string $filterKategori = '';

    public string $filterPrioritas = '';

    public int $perPage = 15;

    // Form
    public ?int $editId = null;

    public string $judul = '';

    public string $isi = '';

    public string $kategori = 'akademik';

    public string $prioritas = 'sedang';

    public string $target = 'semua';

    public $berlakuDari = null;

    public $berlakuSampai = null;

    /** @var array<int, int> */
    public array $jurusanIds = [];

    /** @var array<int, int> */
    public array $kelasIds = [];

    /** @var array<int, int> */
    public array $siswaIds = [];

    // Detail
    public ?int $detailId = null;

    public function boot()
    {
        // Livewire 3: dipanggil pada SETIAP request (termasuk update) — otorisasi tidak pernah terlewati.
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }

    public function updatingSearch()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
    }

    public function updatingFilterKategori()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
    }

    public function updatingFilterPrioritas()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
    }

    public function resetFilter()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->reset(['search', 'filterStatus', 'filterKategori', 'filterPrioritas']);
        $this->resetPage();
    }

    public function tambahPengumuman()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->reset(['editId', 'judul', 'isi', 'kategori', 'prioritas', 'target', 'berlakuDari', 'berlakuSampai', 'jurusanIds', 'kelasIds', 'siswaIds']);
        $this->lovReset();
        $this->dispatch('show-pengumuman-modal');
    }

    public function editPengumuman(int $id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $pengumuman = Pengumuman::query()->with('targets')->findOrFail($id);

        if ($pengumuman->status !== 'draf') {
            $this->alert('error', 'Hanya pengumuman draf yang dapat diedit. Buat pengumuman baru untuk revisi.');

            return;
        }

        $this->editId = $pengumuman->idpengumuman;
        $this->judul = $pengumuman->judul;
        $this->isi = $pengumuman->isi;
        $this->kategori = $pengumuman->kategori;
        $this->prioritas = $pengumuman->prioritas;
        $this->target = $pengumuman->target;
        $this->berlakuDari = $pengumuman->berlaku_dari?->format('Y-m-d');
        $this->berlakuSampai = $pengumuman->berlaku_sampai?->format('Y-m-d');
        $this->jurusanIds = $pengumuman->targets->pluck('jurusan_idjurusan')->filter()->values()->all();
        $this->kelasIds = $pengumuman->targets->pluck('kelas_idkelas')->filter()->values()->all();
        $this->siswaIds = $pengumuman->targets->pluck('siswa_idsiswa')->filter()->values()->all();
        // Tampilkan hanya siswa AKTIF di chips; yang nonaktif tidak bisa dipilih ulang.
        $this->isiTerpilihDariIds($this->siswaIds);

        $this->dispatch('show-pengumuman-modal');
    }

    public function storePengumuman()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        // Deduplikat siswaIds dari client (Livewire bisa mengirim array); ID palsu ditolak via Rule::exists + status aktif.
        $this->siswaIds = array_values(array_unique(array_filter($this->siswaIds, fn ($x): bool => is_numeric($x))));

        $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'kategori' => ['required', 'in:'.implode(',', Pengumuman::KATEGORI)],
            'prioritas' => ['required', 'in:'.implode(',', Pengumuman::PRIORITAS)],
            'target' => ['required', 'in:'.implode(',', Pengumuman::TARGET)],
            'berlakuDari' => ['nullable', 'date'],
            'berlakuSampai' => ['nullable', 'date', 'after_or_equal:berlakuDari'],
            'jurusanIds' => ['nullable', 'array'],
            'jurusanIds.*' => ['integer', Rule::exists('jurusan', 'idjurusan')],
            'kelasIds' => ['nullable', 'array'],
            'kelasIds.*' => ['integer', Rule::exists('kelas', 'idkelas')],
            'siswaIds' => ['nullable', 'array'],
            'siswaIds.*' => ['integer', Rule::exists('siswa', 'idsiswa')->where('status', 'aktif')],
        ]);

        $service = app(PengumumanService::class);
        $data = [
            'judul' => $this->judul,
            'isi' => $this->isi,
            'kategori' => $this->kategori,
            'prioritas' => $this->prioritas,
            'target' => $this->target,
            'berlaku_dari' => $this->berlakuDari,
            'berlaku_sampai' => $this->berlakuSampai,
            'jurusan_ids' => $this->jurusanIds,
            'kelas_ids' => $this->kelasIds,
            'siswa_ids' => $this->siswaIds,
        ];

        try {
            if ($this->editId) {
                $service->ubahDraf($this->editId, $data, auth()->user());
                $pesan = 'Pengumuman draf berhasil diubah.';
            } else {
                $service->buatDraf($data, auth()->user());
                $pesan = 'Pengumuman berhasil dibuat sebagai draf.';
            }

            $this->dispatch('close-pengumuman-modal');
            $this->reset(['editId', 'judul', 'isi', 'kategori', 'prioritas', 'target', 'berlakuDari', 'berlakuSampai', 'jurusanIds', 'kelasIds', 'siswaIds']);
            $this->lovReset();
            $this->alert('success', $pesan);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validasi di level service (mis. target jurusan tanpa pilihan) — tampilkan pesan, bukan 500.
            $this->alert('error', $e->validator->errors()->first());
        } catch (DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function terbitkan(int $id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            app(PengumumanService::class)->terbitkan($id, auth()->user());
            $this->alert('success', 'Pengumuman berhasil diterbitkan.');
        } catch (DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function arsipkan(int $id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            app(PengumumanService::class)->arsipkan($id, auth()->user());
            $this->alert('success', 'Pengumuman berhasil diarsipkan.');
        } catch (DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function detailPengumuman(int $id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->detailId = $id;
        $this->dispatch('show-detail-pengumuman-modal');
    }

    public function tutupDetail()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->detailId = null;
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $query = Pengumuman::query()
            ->with(['targets.jurusan:idjurusan,nama', 'targets.kelas:idkelas,nama', 'targets.siswa:idsiswa,nama'])
            ->withCount('baca')
            ->orderByDesc('updated_at')
            ->orderByDesc('idpengumuman');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('judul', 'like', '%'.$this->search.'%')
                    ->orWhere('isi', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterKategori !== '') {
            $query->where('kategori', $this->filterKategori);
        }

        if ($this->filterPrioritas !== '') {
            $query->where('prioritas', $this->filterPrioritas);
        }

        $pengumumanList = $query->paginate($this->perPage);

        $detail = null;
        if ($this->detailId) {
            $detail = Pengumuman::query()
                ->with([
                    'targets.jurusan:idjurusan,nama',
                    'targets.kelas:idkelas,nama',
                    'targets.siswa:idsiswa,nama',
                    'pembuat:id,username',
                    'pengubah:id,username',
                    'baca.siswa:idsiswa,nama',
                ])
                ->withCount('baca')
                ->find($this->detailId);
        }

        return view('livewire.pengumuman-component', [
            'pengumumanList' => $pengumumanList,
            'detail' => $detail,
            'jurusanList' => Jurusan::orderBy('nama')->get(['idjurusan', 'nama']),
            'kelasList' => Kelas::orderBy('nama')->get(['idkelas', 'nama', 'jurusan_id']),
            'perPage' => $this->perPage,
            'search' => $this->search,
            'filterStatus' => $this->filterStatus,
            'filterKategori' => $this->filterKategori,
            'filterPrioritas' => $this->filterPrioritas,
            'judul' => $this->judul,
            'isi' => $this->isi,
            'kategori' => $this->kategori,
            'prioritas' => $this->prioritas,
            'target' => $this->target,
            'berlakuDari' => $this->berlakuDari,
            'berlakuSampai' => $this->berlakuSampai,
            'jurusanIds' => $this->jurusanIds,
            'kelasIds' => $this->kelasIds,
            'siswaIds' => $this->siswaIds,
            'editId' => $this->editId,
            'detailId' => $this->detailId,
        ]);
    }
}
