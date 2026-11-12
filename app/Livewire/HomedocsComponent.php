<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Landing page dokumentasi — seluruh role pengguna aplikasi boleh membaca.
 */
class HomedocsComponent extends Component
{
    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin', 'guru', 'siswa', 'wali'], true), 403);
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin', 'guru', 'siswa', 'wali'], true), 403);

        return view('livewire.homedocs-component')->layout('layout.docslayout');
    }
}
