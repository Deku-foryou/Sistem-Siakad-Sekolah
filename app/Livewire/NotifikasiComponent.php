<?php

namespace App\Livewire;

use App\Models\Notifikasi;
use App\Services\NotifikasiService;
use DomainException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman /notifikasi: daftar notifikasi milik user login.
 * Guru, siswa & wali; identitas pemilik selalu dari akun login.
 */
class NotifikasiComponent extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Tab: 'all' (Semua) | 'unread' (Belum Dibaca) */
    public $tab = 'all';

    /** Filter jenis ('' = semua jenis) */
    public $filterJenis = '';

    public $galat = '';

    public $sukses = '';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'siswa', 'wali'], true), 403);
    }

    public function updatedTab(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'siswa', 'wali'], true), 403);

        $this->resetPage('page');
    }

    public function updatedFilterJenis(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'siswa', 'wali'], true), 403);

        $this->resetPage('page');
    }

    public function tandaiDibaca(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'siswa', 'wali'], true), 403);

        $this->galat = '';
        $this->sukses = '';

        try {
            $ditemukan = NotifikasiService::tandaiDibaca(auth()->user(), $id);
            $this->sukses = $ditemukan ? 'Notifikasi ditandai sudah dibaca.' : 'Notifikasi tidak ditemukan.';
        } catch (DomainException $exception) {
            $this->galat = $exception->getMessage();
        }

        $this->resetPage('page');
    }

    public function tandaiSemuaDibaca(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'siswa', 'wali'], true), 403);

        $this->galat = '';
        $this->sukses = '';

        $jumlah = NotifikasiService::tandaiSemuaDibaca(auth()->user());
        $this->sukses = $jumlah.' notifikasi ditandai sudah dibaca.';
        $this->resetPage('page');
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'siswa', 'wali'], true), 403);

        $tab = in_array($this->tab, ['all', 'unread'], true) ? $this->tab : 'all';
        $filterJenis = trim((string) $this->filterJenis);

        if (! in_array($filterJenis, Notifikasi::JENIS_LIST, true)) {
            $filterJenis = '';
        }

        $daftar = NotifikasiService::daftar(auth()->user(), $filterJenis !== '' ? $filterJenis : null, $tab, 10);

        return view('livewire.notifikasi-component', [
            'daftar' => $daftar,
            'jenisLabel' => [
                Notifikasi::JENIS_PENGUMUMAN => 'Pengumuman',
                Notifikasi::JENIS_RAPOR => 'Rapor',
                Notifikasi::JENIS_KOREKSI => 'Koreksi',
                Notifikasi::JENIS_UMUM => 'Umum',
            ],
            'tab' => $tab,
            'filterJenis' => $filterJenis,
            'jumlahBelumDibaca' => NotifikasiService::hitungBelumDibaca(auth()->user()),
        ]);
    }
}
