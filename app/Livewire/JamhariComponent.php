<?php

namespace App\Livewire;

use App\Models\Jamhari;
use App\Models\Jurusan;
use App\Services\JamHariService;
use App\Support\Hari;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class JamhariComponent extends Component
{
    use LivewireAlert;

    public $hari = 1;

    public $filterJurusan = '';

    public $filterKelas = '';

    public $idjamhari;

    public $jenis = 'pelajaran';

    public $jam_ke;

    public $mulai;

    public $selesai;

    public $keterangan;

    public $hariTujuan = [];

    protected $rules = [
        'jenis' => 'required|in:pelajaran,istirahat',
        'jam_ke' => 'nullable|integer|min:1',
        'mulai' => 'required|date_format:H:i',
        'selesai' => 'required|date_format:H:i',
        'keterangan' => 'nullable|string|max:100',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }

    public function updatedFilterJurusan(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if ($this->filterJurusan !== '' && $this->filterJurusan !== 'none') {
            $jurusanId = filter_var($this->filterJurusan, FILTER_VALIDATE_INT);
            abort_if(
                $jurusanId === false
                    || ! Jurusan::query()->where('idjurusan', $jurusanId)->where('aktif', true)->exists(),
                404
            );
        }

        $this->filterKelas = '';
        $this->resetForm();
    }

    public function updatedFilterKelas(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if ($this->filterKelas !== '') {
            $this->kelasIdTerpilih();
        }

        $this->resetForm();
    }

    public function updatedHari(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $hari = filter_var($this->hari, FILTER_VALIDATE_INT);
        abort_if($hari === false || $hari < 1 || $hari > 7, 404);
        $this->hari = $hari;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->reset(['idjamhari', 'jenis', 'jam_ke', 'mulai', 'selesai', 'keterangan']);
        $this->resetValidation();
        $this->hariTujuan = [];
        $this->jenis = 'pelajaran';

        if ($this->filterKelas !== '') {
            $awal = JamHariService::nilaiAwal($this->kelasIdTerpilih(), (int) $this->hari);
            $this->jam_ke = $awal['jam_ke'];
            $this->mulai = $awal['mulai'];
        }
    }

    public function openAdd(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if ($this->filterKelas === '') {
            $this->alert('error', 'Pilih kelas aktif terlebih dahulu.');

            return;
        }

        $this->resetForm();
        $this->dispatch('show-add-jamhari-modal');
    }

    public function store(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            $kelasId = $this->kelasIdTerpilih();
            JamHariService::buat($kelasId, array_merge($this->validate(), [
                'hari' => (int) $this->hari,
            ]));

            $this->alert('success', 'Jam berhasil ditambah');
            $this->dispatch('close-jamhari-modal');
            $this->resetForm();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $jam = JamHariService::jamUntukKelas($this->kelasIdTerpilih(), $id);
        $this->idjamhari = $jam->idjamhari;
        $this->jenis = $jam->jenis;
        $this->jam_ke = $jam->jam_ke;
        $this->mulai = substr((string) $jam->mulai, 0, 5);
        $this->selesai = substr((string) $jam->selesai, 0, 5);
        $this->keterangan = $jam->keterangan;
        $this->dispatch('show-edit-jamhari-modal');
    }

    public function update(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            JamHariService::ubah(
                $this->kelasIdTerpilih(),
                (int) $this->idjamhari,
                $this->validate()
            );

            $this->alert('success', 'Jam berhasil diubah');
            $this->dispatch('close-jamhari-modal');
            $this->resetForm();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function deleteConfirm(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $jam = JamHariService::jamUntukKelas($this->kelasIdTerpilih(), $id);
        $this->idjamhari = $jam->idjamhari;
        $this->dispatch('show-delete-jamhari-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            JamHariService::hapus($this->kelasIdTerpilih(), (int) $this->idjamhari);
            $this->alert('success', 'Jam berhasil dihapus');
            $this->dispatch('close-jamhari-modal');
            $this->resetForm();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function salinHari(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if (! is_array($this->hariTujuan) || $this->hariTujuan === []) {
            $this->alert('error', 'Pilih setidaknya satu hari tujuan.');

            return;
        }

        try {
            $hasil = JamHariService::salin(
                $this->kelasIdTerpilih(),
                (int) $this->hari,
                $this->hariTujuan
            );
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());

            return;
        }

        $berhasil = count(array_filter(
            $hasil,
            fn (array $status): bool => $status['status'] === 'berhasil'
        ));
        $ditolak = array_filter(
            $hasil,
            fn (array $status): bool => $status['status'] === 'ditolak'
        );

        if ($ditolak !== []) {
            $alasan = implode('; ', array_map(
                fn (int|string $hariTujuan, array $status): string => Hari::nama((int) $hariTujuan).': '.$status['alasan'],
                array_keys($ditolak),
                $ditolak
            ));
            $this->alert('error', $alasan);

            return;
        }

        $this->hariTujuan = [];
        $this->alert('success', "Jam berhasil disalin ke {$berhasil} hari pada kelas terpilih.");
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $jurusans = Jurusan::query()->where('aktif', true)->orderBy('nama')->get();
        $kelases = $this->filterJurusan !== ''
            ? $this->kelasPilihanQuery()->orderBy('nama')->get()
            : collect();
        $kelasId = $this->filterKelas !== '' && $this->kelasPilihanQuery()->where('idkelas', $this->filterKelas)->exists()
            ? (int) $this->filterKelas
            : null;

        $jam = $kelasId
            ? Jamhari::query()
                ->where('kelas_id', $kelasId)
                ->where('hari', $this->hari)
                ->urut()
                ->get()
            : collect();

        $jumlah = $kelasId
            ? Jamhari::query()
                ->select('hari', DB::raw('count(*) as total'))
                ->where('kelas_id', $kelasId)
                ->groupBy('hari')
                ->pluck('total', 'hari')
            : collect();

        return view('livewire.jamhari-component', [
            'jam' => $jam,
            'jumlah' => $jumlah,
            'hariNama' => Hari::NAMA,
            'jurusans' => $jurusans,
            'kelases' => $kelases,
            'kelasTerpilih' => $kelasId ? $kelases->firstWhere('idkelas', $kelasId) : null,
        ]);
    }

    private function kelasPilihanQuery(): Builder
    {
        $query = JamHariService::kelasAktifQuery();

        if ($this->filterJurusan === 'none') {
            return $query->whereNull('jurusan_id');
        }

        $jurusanId = filter_var($this->filterJurusan, FILTER_VALIDATE_INT);

        return $jurusanId === false
            ? $query->whereRaw('1 = 0')
            : $query->where('jurusan_id', $jurusanId);
    }

    private function kelasIdTerpilih(): int
    {
        $kelasId = filter_var($this->filterKelas, FILTER_VALIDATE_INT);
        abort_if($kelasId === false || $kelasId <= 0, 404);
        abort_unless($this->kelasPilihanQuery()->where('idkelas', $kelasId)->exists(), 404);
        JamHariService::pastikanKelasAktif($kelasId);

        return $kelasId;
    }
}
