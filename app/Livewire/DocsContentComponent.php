<?php

namespace App\Livewire;

use App\Models\Docs;
use Livewire\Component;

class DocsContentComponent extends Component
{

    public $id;

    public function mount($id)
    {
        $this->id = $id;
    }


    public function render()
    {

        $getDocs = Docs::where('iddocs', $this->id)->first();

        return view('livewire.docs-content-component', [
            'getDocs' => $getDocs,
        ])->layout('layout.docslayout');
    }
}