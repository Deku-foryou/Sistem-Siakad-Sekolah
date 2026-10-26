<?php

namespace App\Livewire;

use App\Models\Docs;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DocsCreateComponent extends Component
{


    use WithPagination;
    use WithFileUploads;
    use LivewireAlert;

    public $title, $content, $iddocs;


    public function storeDocs()
    {

        $this->validate([
            'title' => 'required ',
            'content' => 'required|file|mimes:pdf|max:10000',
        ]);


        $docs = new Docs();
        $docs->title = $this->title;
        // $docs->content = $this->content;

        if ($this->content) {
            $namaFile = 'docs' . now()->format('YmdHis') . '.' . $this->content->extension();
            $this->content->storeAs('tutorial-website', $namaFile);
            $docs->content = $namaFile;
        }

        $docs->save();


        $this->alert('success', 'Berhasil Menambahkan Dokumentasi');

        $this->close();

    }

    public function close()
    {
        $this->reset(['title', 'content',]);
        $this->resetValidation();
        return redirect('/docs');
    }

    public function render()
    {
        return view('livewire.docs-create-component')->layout('layout.docslayout');
    }
}