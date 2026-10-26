<?php

namespace App\Livewire;

use Livewire\Component;

class HomedocsComponent extends Component
{
    public function render()
    {
        return view('livewire.homedocs-component')->layout('layout.docslayout');
    }
}
