<?php

namespace App\Livewire;

use App\Models\Docs;
use Livewire\Component;

/**
 * Detail dokumen: seluruh role pengguna aplikasi boleh membaca.
 * Nama file divalidasi whitelist — mencegah path traversal dari data tersimpan.
 */
class DocsContentComponent extends Component
{
    public $id;

    public function mount($id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin', 'guru', 'siswa', 'wali'], true), 403);

        $this->id = (int) $id;
        abort_if($this->id < 1, 404);
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin', 'guru', 'siswa', 'wali'], true), 403);

        $getDocs = Docs::query()->find($this->id);

        abort_if(! $getDocs, 404);
        abort_if(! preg_match('/^[A-Za-z0-9._-]+$/', (string) $getDocs->content), 404);

        return view('livewire.docs-content-component', [
            'getDocs' => $getDocs,
        ])->layout('layout.docslayout');
    }
}
