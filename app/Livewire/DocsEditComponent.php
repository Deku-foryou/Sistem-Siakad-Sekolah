<?php

namespace App\Livewire;

use App\Models\Docs;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DocsEditComponent extends Component
{

    use WithPagination;
    use WithFileUploads;
    use LivewireAlert;
    public $id;

    public $title, $content, $iddocs;


    public function mount($id)
    {
        $docs = Docs::findOrFail($id);
        $this->iddocs = $docs->id;
        $this->title = $docs->title;
        $this->content = $docs->content;
    }

    public function updateDocs()
    {
        // Validate title as required
        $this->validate([
            'title' => 'required',
        ]);

        // Fetch the document by ID
        $docs = Docs::find($this->id);

        // Update title
        $docs->title = $this->title;

        // Check if content is being updated
        if ($this->content != $docs->content) {

            // Validate the content file if it's being updated
            $this->validate([
                'content' => 'nullable|file|mimes:pdf|max:10000',
            ]);

            // Delete the old content if it exists
            if (!is_null($docs->content)) {
                Storage::delete('tutorial-website/' . $docs->content);
            }

            // Generate new filename and store the updated content
            $namaFile = 'docs_' . now()->format('YmdHis') . '.' . $this->content->extension();
            $this->content->storeAs('tutorial-website', $namaFile);

            // Update content field
            $docs->content = $namaFile;
        }

        // Save the updated document
        $docs->save();

        // Alert success and redirect
        $this->alert('success', 'Dokumentasi Berhasil di Ubah');
        return redirect('/docs');
    }



    public function render()
    {

        $getdocs = Docs::where('iddocs', $this->id)->first();

        return view('livewire.docs-edit-component', [
            'getdocs' => $getdocs,
        ])->layout('layout.docslayout');
    }
}