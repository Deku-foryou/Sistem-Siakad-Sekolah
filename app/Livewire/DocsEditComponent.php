<?php

namespace App\Livewire;

use App\Models\Docs;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Edit dokumen — khusus superadmin & admin (2B.15, keputusan resmi).
 */
class DocsEditComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $id;

    public $title;

    public $content;

    public $iddocs;

    public $docscat_iddocscat;

    public $urut;

    private function pastikanBolehKelola(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function mount($id): void
    {
        $this->pastikanBolehKelola();

        $docs = Docs::query()->findOrFail((int) $id);
        $this->id = (int) $id;
        $this->iddocs = $docs->iddocs;
        $this->title = $docs->title;
        $this->content = $docs->content;
        $this->docscat_iddocscat = $docs->docscat_iddocscat;
        $this->urut = $docs->urut;
    }

    public function updateDocs()
    {
        $this->pastikanBolehKelola();

        $this->validate([
            'title' => 'required|string|max:255',
            'docscat_iddocscat' => 'required|integer|exists:docscat,iddocscat',
            'urut' => [
                'required',
                'integer',
                'min:1',
                \Illuminate\Validation\Rule::unique('docs', 'urut')
                    ->where(fn ($query) => $query->where('docscat_iddocscat', $this->docscat_iddocscat))
                    ->ignore($this->iddocs, 'iddocs'),
            ],
        ]);

        if ($this->content instanceof \Illuminate\Http\UploadedFile) {
            $this->validate(['content' => 'file|mimes:pdf|max:10000']);
        }

        $docs = Docs::query()->findOrFail($this->iddocs);

        $baru = [
            'title' => $this->title,
            'docscat_iddocscat' => (int) $this->docscat_iddocscat,
            'urut' => (int) $this->urut,
        ];

        $namaLama = $docs->content;
        $namaBaru = null;

        if ($this->content instanceof \Illuminate\Http\UploadedFile) {
            $namaBaru = 'docs_'.now()->format('YmdHis').'_'.uniqid('', true).'.'.$this->content->extension();
            $baru['content'] = $namaBaru;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($docs, $baru, $namaBaru) {
                if ($namaBaru !== null) {
                    $this->content->storeAs('tutorial-website', $namaBaru);
                }
                $docs->fill($baru)->save();
            });
        } catch (\Throwable $e) {
            if ($namaBaru !== null) {
                try {
                    Storage::delete('tutorial-website/'.$namaBaru);
                } catch (\Throwable) {
                    \Illuminate\Support\Facades\Log::warning('docs-edit: gagal menghapus file yatim', ['nama' => $namaBaru]);
                }
            }

            throw $e;
        }

        if ($namaBaru !== null && $namaLama) {
            try {
                Storage::delete('tutorial-website/'.$namaLama);
            } catch (\Throwable) {
                \Illuminate\Support\Facades\Log::warning('docs-edit: gagal menghapus file lama', ['nama' => $namaLama]);
            }
        }

        $this->alert('success', 'Dokumentasi Berhasil di Ubah');

        return redirect('/docs');
    }

    public function render()
    {
        $this->pastikanBolehKelola();

        $getdocs = Docs::query()->findOrFail($this->id);
        $kategori = \App\Models\Docscat::query()->orderBy('urut')->orderBy('docscat')->get();

        return view('livewire.docs-edit-component', [
            'getdocs' => $getdocs,
            'kategori' => $kategori,
        ])->layout('layout.docslayout');
    }
}
