<?php

namespace App\Livewire;

use App\Models\Docs;
use App\Models\Docscat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Buat dokumen — khusus superadmin & admin (2B.15, keputusan resmi).
 * Upload dibatasi: PDF saja, maks 10 MB, nama file dibuat server (tidak dari pengguna).
 */
class DocsCreateComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $title;

    public $content;

    public $docscat_iddocscat;

    public $urut;

    public $iddocs;

    private function pastikanBolehKelola(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function mount(): void
    {
        $this->pastikanBolehKelola();
    }

    public function storeDocs()
    {
        $this->pastikanBolehKelola();

        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|file|mimes:pdf|max:10000',
            'urut' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('docs', 'urut')->where(fn ($query) => $query->where('docscat_iddocscat', $this->docscat_iddocscat)),
            ],
            'docscat_iddocscat' => 'required|integer|exists:docscat,iddocscat',
        ]);

        $namaFile = 'docs_'.now()->format('YmdHis').'_'.uniqid('', true).'.'.$this->content->extension();

        try {
            DB::transaction(function () use ($namaFile) {
                $docs = new Docs;
                $docs->title = $this->title;

                $this->content->storeAs('tutorial-website', $namaFile);
                $docs->content = $namaFile;

                $docs->urut = (int) $this->urut;
                $docs->docscat_iddocscat = (int) $this->docscat_iddocscat;
                $docs->save();
            });
        } catch (\Throwable $e) {
            // Jangan meninggalkan file yatim bila transaksi gagal.
            try {
                \Illuminate\Support\Facades\Storage::delete('tutorial-website/'.$namaFile);
            } catch (\Throwable) {
                \Illuminate\Support\Facades\Log::warning('docs-create: gagal menghapus file yatim', ['nama' => $namaFile]);
            }

            throw $e;
        }

        $this->alert('success', 'Berhasil Menambahkan Dokumentasi');

        $this->close();
    }

    public function close()
    {
        $this->pastikanBolehKelola();

        $this->reset(['title', 'content', 'docscat_iddocscat', 'urut']);
        $this->resetValidation();

        return redirect('/docs');
    }

    public function render()
    {
        $this->pastikanBolehKelola();

        $kategori = Docscat::query()->orderBy('urut')->orderBy('docscat')->get();

        return view('livewire.docs-create-component', ['kategori' => $kategori])
            ->layout('layout.docslayout');
    }
}
