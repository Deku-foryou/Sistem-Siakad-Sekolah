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

    public $telepon_siswa;

    public $email;

    public $nik;

    public $asal_sekolah;

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
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->validate([
            'nisn' => 'required|digits:10|unique:siswa,nisn|unique:users,username',
            'nik' => 'nullable|digits:16|unique:siswa,nik',
            'email' => 'nullable|email',
            'nama' => 'required',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'status' => 'required|in:aktif,lulus,pindah,keluar',
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $data = [
            'nisn' => $this->nisn,
            'nis' => $this->nis,
            'nik' => $this->nik,
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir,
            'agama' => $this->agama,
            'alamat' => $this->alamat,
            'telepon_siswa' => $this->telepon_siswa,
            'email' => $this->email,
            'asal_sekolah' => $this->asal_sekolah,
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
        $this->reset(['idsiswa', 'user_iduser', 'nisn', 'nis', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'telepon_siswa', 'email', 'asal_sekolah', 'nama_ayah', 'nama_ibu', 'telepon_ortu', 'tahun_masuk', 'foto', 'status']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->alert('success', 'Siswa berhasil ditambahkan');
        $this->dispatch('close-siswa-modal');
    }

    public function close()
    {
        $this->reset(['idsiswa', 'user_iduser', 'nisn', 'nis', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'telepon_siswa', 'email', 'asal_sekolah', 'nama_ayah', 'nama_ibu', 'telepon_ortu', 'tahun_masuk', 'foto', 'status']);
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
        $this->nik = $siswa->nik;
        $this->nama = $siswa->nama;
        $this->jenis_kelamin = $siswa->jenis_kelamin;
        $this->tempat_lahir = $siswa->tempat_lahir;
        $this->tanggal_lahir = optional($siswa->tanggal_lahir)->format('Y-m-d');
        $this->agama = $siswa->agama;
        $this->alamat = $siswa->alamat;
        $this->telepon_siswa = $siswa->telepon_siswa;
        $this->email = $siswa->email;
        $this->asal_sekolah = $siswa->asal_sekolah;
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
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->validate([
            'nisn' => 'required|digits:10|unique:siswa,nisn,'.$this->idsiswa.',idsiswa|unique:users,username,'.$this->user_iduser.',id',
            'nik' => 'nullable|digits:16|unique:siswa,nik,'.$this->idsiswa.',idsiswa',
            'email' => 'nullable|email',
            'nama' => 'required',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'status' => 'required|in:aktif,lulus,pindah,keluar',
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        DB::transaction(function () {
            $siswa = Siswa::where('idsiswa', $this->idsiswa)->first();
            $siswa->nisn = $this->nisn;
            $siswa->nis = $this->nis;
            $siswa->nik = $this->nik;
            $siswa->nama = $this->nama;
            $siswa->jenis_kelamin = $this->jenis_kelamin;
            $siswa->tempat_lahir = $this->tempat_lahir;
            $siswa->tanggal_lahir = $this->tanggal_lahir;
            $siswa->agama = $this->agama;
            $siswa->alamat = $this->alamat;
            $siswa->nik = $this->nik;
            $siswa->telepon_siswa = $this->telepon_siswa;
            $siswa->email = $this->email;
            $siswa->asal_sekolah = $this->asal_sekolah;
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

        $this->reset(['idsiswa', 'user_iduser', 'nisn', 'nis', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'telepon_siswa', 'email', 'asal_sekolah', 'nama_ayah', 'nama_ibu', 'telepon_ortu', 'tahun_masuk', 'foto', 'status']);
        $this->status = 'aktif';
        $this->jenis_kelamin = 'L';
        $this->alert('success', 'Siswa berhasil diperbarui');
        $this->dispatch('close-siswa-modal');
    }

    public function deleteConfirmationSiswa($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->idsiswa = $id;
        $this->dispatch('show-delete-confirmation-siswa-modal');
    }

    public function deleteSiswa()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
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
        $tahunAjaranAktif = Tahunajaran::where('aktif', true)->first();

        $siswa = Siswa::latest()
            ->search($this->search)
            ->with(['kelassiswa.kelas' => function ($query) use ($tahunAjaranAktif) {
                if ($tahunAjaranAktif) {
                    $query->where('tahunajaran_idtahunajaran', $tahunAjaranAktif->idtahunajaran)
                        ->where('jenis', 'induk');
                }
            }])
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
                    'idsiswa', 'user_iduser', 'nisn', 'nis', 'nik', 'nama', 'jenis_kelamin',
                    'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'telepon_siswa',
                    'email', 'asal_sekolah', 'nama_ayah', 'telepon_ayah', 'nama_ibu',
                    'telepon_ibu', 'nama_wali', 'telepon_wali', 'telepon_ortu', 'tahun_masuk', 'status',
                ])
                ->with([
                    'user:id,username,wajib_ganti_password',
                    'kelassiswa' => fn ($query) => $query
                        ->select(['idkelassiswa', 'siswa_idsiswa', 'kelas_idkelas'])
                        ->orderByDesc('idkelassiswa'),
                    'kelassiswa.kelas' => fn ($query) => $query
                        ->select([
                            'idkelas', 'tahunajaran_idtahunajaran', 'nama', 'tingkat',
                            'jenis', 'guru_idguru', 'jurusan_id', 'rombel',
                        ]),
                    'kelassiswa.kelas.tahunajaran:idtahunajaran,nama',
                    'kelassiswa.kelas.jurusan:idjurusan,nama',
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
