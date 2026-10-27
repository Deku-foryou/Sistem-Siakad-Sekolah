<?php

namespace App\Livewire;

use App\Support\BerandaUrl;
use Livewire\Component;

class DashboardComponent extends Component
{
    public function mount()
    {
        $user = auth()->user();

        if ($user && $user->level !== 'superadmin') {
            return $this->redirect(BerandaUrl::untuk($user));
        }
    }

    public function render()
    {
        return view('livewire.dashboard-component');
    }
}
