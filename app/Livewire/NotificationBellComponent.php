<?php

namespace App\Livewire;

use App\Services\NotifikasiService;
use Livewire\Component;

/**
 * Bell navbar: badge jumlah notifikasi belum dibaca milik user login.
 *
 * Hanya tampil untuk guru, siswa & wali; lainnya mengembalikan respons 403.
 * Tautan menuju halaman /notifikasi.
 */
class NotificationBellComponent extends Component
{
    public function mount(): void
    {
        // Guard pointer: halaman tetap dimuat; bell tidak tampil untuk role lain.
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'siswa', 'wali'], true), 403);

        $belumDibaca = NotifikasiService::hitungBelumDibaca(auth()->user());

        return view('livewire.notification-bell-component', [
            'belumDibaca' => $belumDibaca,
        ]);
    }
}
