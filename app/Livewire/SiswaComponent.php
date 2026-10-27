<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesImport;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use App\Services\PaketMenuService;
use App\Services\SiswaService;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class SiswaComponent extends Component
{
    use HandlesImport;
    use LivewireAlert;
    use WithPagination;

    public $idsiswa;

    public $detailId;

    public $user_iduser;

    public $nisn;

    public $nis;

    public $nama;

    public $jenis_kelamin = 'L';

    public $tempat_lahir;

    public $tanggal_lahir;

    public $agama;

    public $alamat;

    public $nama_ayah;

    public $nama_ibu;

    public $telepon_ortu;

    public $tahun_masuk;

    public $foto;

    public $status = 'aktif';

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

    public function detailSiswa($id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->detailId = (int) $id;
        $this->dispatch('show-detail-siswa-modal');
    }

    public function tutupDetailSiswa(): void
    {
        $this->detailId = null;
        $this->dispatch('close-detail-siswa-modal');
    }

    public function sinkronkanPaket(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $result = PaketMenuService::sinkronkanSemua('siswa');
        $this->dispatch('close-sync-siswa-modal');
        if ($result['paket_tidak_ada']) {
            $this->alert('error', 'Paket menu Siswa belum tersedia.');

            return;
        }

        $this->alert(
            'success',
            $result['processed'].' user diproses, '.$result['added'].' baris menu ditambahkan.'
        );
    }

    public function storeSiswa()
    {
        $this->validate([
            'nisn' => 'required|digits:10|unique:siswa,nisn|unique:users,username',
            'nama' => 'required',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'status' => 'required|in:aktif,lulus,pindah,keluar',
        ]);

        $data = [
            'nisn' => $this->nisn,
            'nis' => $this->nis,
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir,
            'agama' => $this->agama,
            'alamat' => $this->alamat,
            'nama_ayah' => $this->nama_ayah,
            'nama_ibu' => $this->nama_ibu,
            'telepon_ortu' => $this->telepon_ortu,
            'tahun_masuk' => $this->tahun_masuk,
            'foto' => $this->foto,
            'status' => $this->status,
        ];

        $result = SiswaService::buat($data);

        if (! $result['success']) {
            $this->alert('error', 'Gagal menambah siswa: '.implode(', ', array_values($result['errors'] ?? [])));

            return;
        }

        // Result already contains user, siswa, and paket menu applied within transaction
        $this->reset(['idsiswa', 'user_iduser', 'nisn', 'nis', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'nama_ayah', 'nama_ibu', 'telepon_ortu', 'tahun_masuk', 'foto', 'status']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->alert('success', 'Siswa berhasil ditambahkan');
        $this->dispatch('close-siswa-modal');
    }

    public function close()
    {
        $this->reset(['idsiswa', 'user_iduser', 'nisn', 'nis', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'nama_ayah', 'nama_ibu', 'telepon_ortu', 'tahun_masuk', 'foto', 'status']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->resetValidation();
        $this->dispatch('close-siswa-modal');
    }

    public function editSiswa($id)
    {
        $siswa = Siswa::where('idsiswa', $id)->first();
        $this->idsiswa = $siswa->idsiswa;
        $this->user_iduser = $siswa->user_iduser;
        $this->nisn = $siswa->nisn;
        $this->nis = $siswa->nis;
        $this->nama = $siswa->nama;
        $this->jenis_kelamin = $siswa->jenis_kelamin;
        $this->tempat_lahir = $siswa->tempat_lahir;
        $this->tanggal_lahir = optional($siswa->tanggal_lahir)->format('Y-m-d');
        $this->agama = $siswa->agama;
        $this->alamat = $siswa->alamat;
        $this->nama_ayah = $siswa->nama_ayah;
        $this->nama_ibu = $siswa->nama_ibu;
        $this->telepon_ortu = $siswa->telepon_ortu;
        $this->tahun_masuk = $siswa->tahun_masuk;
        $this->foto = $siswa->foto;
        $this->status = $siswa->status;
        $this->dispatch('show-edit-siswa-modal');
    }

    public function updateSiswa()
    {
        $this->validate([
            'nisn' => 'required|digits:10|unique:siswa,nisn,'.$this->idsiswa.',idsiswa|unique:users,username,'.$this->user_iduser.',id',
            'nama' => 'required',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'status' => 'required|in:aktif,lulus,pindah,keluar',
        ]);

        DB::transaction(function () {
            $siswa = Siswa::where('idsiswa', $this->idsiswa)->first();
            $siswa->nisn = $this->nisn;
            $siswa->nis = $this->nis;
            $siswa->nama = $this->nama;
            $siswa->jenis_kelamin = $this->jenis_kelamin;
            $siswa->tempat_lahir = $this->tempat_lahir;
            $siswa->tanggal_lahir = $this->tanggal_lahir;
            $siswa->agama = $this->agama;
            $siswa->alamat = $this->alamat;
            $siswa->nama_ayah = $this->nama_ayah;
            $siswa->nama_ibu = $this->nama_ibu;
            $siswa->telepon_ortu = $this->telepon_ortu;
            $siswa->tahun_masuk = $this->tahun_masuk;
            $siswa->foto = $this->foto;
            $siswa->status = $this->status;
            $siswa->save();

            $user = User::find($this->user_iduser);
            $user->username = $this->nisn;
            $user->name = $this->nama;
            $user->save();
        });

        $this->reset(['idsiswa', 'user_iduser', 'nisn', 'nis', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'nama_ayah', 'nama_ibu', 'telepon_ortu', 'tahun_masuk', 'foto', 'status']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->alert('success', 'Siswa berhasil diperbarui');
        $this->dispatch('close-siswa-modal');
    }

    public function deleteConfirmationSiswa($id)
    {
        $this->idsiswa = $id;
        $this->dispatch('show-delete-confirmation-siswa-modal');
    }

    public function deleteSiswa()
    {
        $siswa = Siswa::where('idsiswa', $this->idsiswa)->first();
        if ($siswa->kelassiswa()->count() > 0) {
            $this->alert('error', 'Siswa memiliki relasi penempatan, tidak dapat dihapus');
            $this->dispatch('close-siswa-modal');
            $this->idsiswa = '';

            return;
        }
        DB::transaction(function () use ($siswa) {
            $user = $siswa->user;
            $siswa->delete();
            if ($user) {
                $user->delete();
            }
        });
        $this->alert('success', 'Siswa berhasil dihapus');
        $this->dispatch('close-siswa-modal');
        $this->idsiswa = '';
    }

    public function render()
    {
        $siswa = Siswa::latest()
            ->search($this->search)
            ->paginate($this->perPage);

        $detailSiswa = null;
        $tahunAjaranAktif = null;
        $kelasIndukAktif = null;
        $kelasPilihanAktif = collect();
        $riwayatKelas = collect();

        if ($this->detailId && auth()->user()?->level === 'superadmin') {
            $tahunAjaranAktif = Tahunajaran::query()
                ->select(['idtahunajaran', 'nama'])
                ->where('aktif', true)
                ->first();

            $detailSiswa = Siswa::query()
                ->select([
                    'idsiswa', 'user_iduser', 'nisn', 'nis', 'nama', 'jenis_kelamin',
                    'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'nama_ayah',
                    'nama_ibu', 'telepon_ortu', 'tahun_masuk', 'status',
                ])
                ->with([
                    'user:id,username,wajib_ganti_password',
                    'kelassiswa' => fn ($query) => $query
                        ->select(['idkelassiswa', 'siswa_idsiswa', 'kelas_idkelas'])
                        ->orderByDesc('idkelassiswa'),
                    'kelassiswa.kelas' => fn ($query) => $query
                        ->select([
                            'idkelas', 'tahunajaran_idtahunajaran', 'nama', 'tingkat',
                            'jenis', 'guru_idguru',
                        ]),
                    'kelassiswa.kelas.tahunajaran:idtahunajaran,nama',
                    'kelassiswa.kelas.wali:idguru,nama',
                ])
                ->find($this->detailId);

            if ($detailSiswa) {
                $riwayatKelas = $detailSiswa->kelassiswa
                    ->sortByDesc(fn ($penempatan) => $penempatan->kelas?->tahunajaran?->nama ?? '')
                    ->values();

                if ($tahunAjaranAktif) {
                    $kelasTahunAktif = $detailSiswa->kelassiswa->filter(
                        fn ($penempatan) => (int) $penempatan->kelas?->tahunajaran_idtahunajaran === (int) $tahunAjaranAktif->idtahunajaran
                    );
                    $kelasIndukAktif = $kelasTahunAktif->first(
                        fn ($penempatan) => $penempatan->kelas?->jenis === 'induk'
                    )?->kelas;
                    $kelasPilihanAktif = $kelasTahunAktif
                        ->filter(fn ($penempatan) => $penempatan->kelas?->jenis === 'mapel_pilihan')
                        ->map(fn ($penempatan) => $penempatan->kelas)
                        ->values();
                }
            }
        }

        return view('livewire.siswa-component', [
            'siswas' => $siswa,
            'detailSiswa' => $detailSiswa,
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'kelasIndukAktif' => $kelasIndukAktif,
            'kelasPilihanAktif' => $kelasPilihanAktif,
            'riwayatKelas' => $riwayatKelas,
        ]);
    }
}
