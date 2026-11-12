<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesImport;
use App\Models\Guru;
use App\Models\Tahunajaran;
use App\Models\User;
use App\Services\GuruService;
use App\Services\PaketMenuService;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class GuruComponent extends Component
{
    use HandlesImport;
    use LivewireAlert;
    use WithPagination;

    public $idguru;

    public $detailId;

    public $user_iduser;

    public $nip;

    public $kode_guru;

    public $nama;

    public $jenis_kelamin = 'L';

    public $tempat_lahir;

    public $tanggal_lahir;

    public $telepon;

    public $alamat;

    public $foto;

    public $status = 'aktif';

    public $mapel_ids = [];

    public $mapelSearch = '';

    public $search = '';

    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        $this->resetPage();
    }

    public function detailGuru($id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->detailId = (int) $id;
        $this->dispatch('show-detail-guru-modal');
    }

    public function tutupDetailGuru(): void
    {
        $this->detailId = null;
        $this->dispatch('close-detail-guru-modal');
    }

    public function sinkronkanPaket(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $result = PaketMenuService::sinkronkanSemua('guru');
        $this->dispatch('close-sync-guru-modal');
        if ($result['paket_tidak_ada']) {
            $this->alert('error', 'Paket menu Guru belum tersedia.');

            return;
        }

        $this->alert(
            'success',
            $result['processed'].' user diproses, '.$result['added'].' baris menu ditambahkan.'
        );
    }

    public function storeGuru()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->validate([
            'nip' => 'required|digits_between:1,25|unique:guru,nip|unique:users,username',
            'kode_guru' => 'nullable|string|max:50|unique:guru,kode_guru',
            'nama' => 'required',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'status' => 'required|in:aktif,nonaktif',
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'mapel_ids' => 'nullable|array',
            'mapel_ids.*' => 'exists:mapel,idmapel',
        ]);

        $data = [
            'nip' => $this->nip,
            'kode_guru' => $this->kode_guru,
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir,
            'telepon' => $this->telepon,
            'alamat' => $this->alamat,
            'foto' => $this->foto,
            'status' => $this->status,
        ];

        try {
            DB::transaction(function () use ($data) {
                $result = GuruService::buat($data);
                if ($result['success']) {
                    GuruService::sinkronMapel($result['guru'], $this->mapel_ids);
                } else {
                    throw new \Exception(implode(', ', array_values($result['errors'] ?? [])));
                }
            });
        } catch (\Throwable $e) {
            \Log::error('GuruService::buat error', ['exception' => get_class($e), 'message' => $e->getMessage()]);
            $this->alert('error', 'Terjadi kesalahan saat menambah guru: '.$e->getMessage());

            return;
        }

        $this->reset(['idguru', 'user_iduser', 'nip', 'kode_guru', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'alamat', 'foto', 'status', 'mapel_ids']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->alert('success', 'Guru berhasil ditambahkan');
        $this->dispatch('close-guru-modal');
    }

    public function close()
    {
        $this->reset(['idguru', 'user_iduser', 'nip', 'kode_guru', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'alamat', 'foto', 'status', 'mapel_ids', 'mapelSearch']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->resetValidation();
        $this->dispatch('close-guru-modal');
    }

    public function editGuru($id)
    {
        $guru = Guru::where('idguru', $id)->first();
        $this->idguru = $guru->idguru;
        $this->user_iduser = $guru->user_iduser;
        $this->nip = $guru->nip;
        $this->kode_guru = $guru->kode_guru;
        $this->nama = $guru->nama;
        $this->jenis_kelamin = $guru->jenis_kelamin;
        $this->tempat_lahir = $guru->tempat_lahir;
        $this->tanggal_lahir = optional($guru->tanggal_lahir)->format('Y-m-d');
        $this->telepon = $guru->telepon;
        $this->alamat = $guru->alamat;
        $this->foto = $guru->foto;
        $this->status = $guru->status;
        $this->mapel_ids = $guru->mapel()->pluck('mapel.idmapel')->toArray();
        $this->dispatch('show-edit-guru-modal');
    }

    public function updateGuru()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->validate([
            'nip' => 'required|digits_between:1,25|unique:guru,nip,'.$this->idguru.',idguru|unique:users,username,'.$this->user_iduser.',id',
            'kode_guru' => 'nullable|string|max:50|unique:guru,kode_guru,'.$this->idguru.',idguru',
            'nama' => 'required',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'status' => 'required|in:aktif,nonaktif',
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'mapel_ids' => 'nullable|array',
            'mapel_ids.*' => 'exists:mapel,idmapel',
        ]);

        DB::transaction(function () {
            $guru = Guru::where('idguru', $this->idguru)->first();
            $guru->nip = $this->nip;
            $guru->kode_guru = $this->kode_guru;
            $guru->nama = $this->nama;
            $guru->jenis_kelamin = $this->jenis_kelamin;
            $guru->tempat_lahir = $this->tempat_lahir;
            $guru->tanggal_lahir = $this->tanggal_lahir;
            $guru->telepon = $this->telepon;
            $guru->alamat = $this->alamat;
            $guru->foto = $this->foto;
            $guru->status = $this->status;
            $guru->save();

            $user = User::find($this->user_iduser);
            $user->username = $this->nip;
            $user->name = $this->nama;
            $user->save();

            GuruService::sinkronMapel($guru, $this->mapel_ids);
        });

        $this->reset(['idguru', 'user_iduser', 'nip', 'kode_guru', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'alamat', 'foto', 'status', 'mapel_ids']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->alert('success', 'Guru berhasil diperbarui');
        $this->dispatch('close-guru-modal');
    }

    public function deleteConfirmationGuru($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->idguru = $id;
        $this->dispatch('show-delete-confirmation-guru-modal');
    }

    public function deleteGuru()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $guru = Guru::where('idguru', $this->idguru)->first();
        if ($guru->kelas()->count() > 0) {
            $this->alert('error', 'Guru memiliki relasi kelas, tidak dapat dihapus');
            $this->dispatch('close-guru-modal');
            $this->idguru = '';

            return;
        }
        DB::transaction(function () use ($guru) {
            // Hapus gurumapel dulu (karena FK restrict)
            $guru->mapel()->detach();

            $user = $guru->user;
            $guru->delete();
            if ($user) {
                $user->delete();
            }
        });
        $this->alert('success', 'Guru berhasil dihapus');
        $this->dispatch('close-guru-modal');
        $this->idguru = '';
    }

    public function render()
    {
        $guru = Guru::latest()
            ->with('mapel')
            ->search($this->search)
            ->paginate($this->perPage);

        $detailGuru = null;
        $tahunAjaranAktif = null;

        if ($this->detailId && auth()->user()?->level === 'superadmin') {
            $tahunAjaranAktif = Tahunajaran::query()
                ->select(['idtahunajaran', 'nama'])
                ->where('aktif', true)
                ->first();

            $detailGuru = Guru::query()
                ->select([
                    'idguru', 'user_iduser', 'nip', 'nama', 'jenis_kelamin',
                    'tempat_lahir', 'tanggal_lahir', 'telepon', 'alamat', 'status',
                ])
                ->with([
                    'user:id,username,wajib_ganti_password',
                    'mapel',
                    'kelas' => fn ($query) => $query
                        ->select([
                            'idkelas', 'tahunajaran_idtahunajaran', 'nama', 'tingkat',
                            'jenis', 'guru_idguru',
                        ])
                        ->where('jenis', 'induk')
                        ->orderByDesc('tahunajaran_idtahunajaran'),
                    'kelas.tahunajaran:idtahunajaran,nama',
                ])
                ->find($this->detailId);
        }

        return view('livewire.guru-component', [
            'gurus' => $guru,
            'detailGuru' => $detailGuru,
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'mapels' => \App\Models\Mapel::orderBy('nama')->get(),
        ]);
    }
}
