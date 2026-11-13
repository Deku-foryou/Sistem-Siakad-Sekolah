<?php

namespace App\Livewire;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class RoleComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $role_id = [];

    public $idrole;

    public $rolecat_idrolecat;

    public $appmenunew_idappmenunew;

    public $roleName;

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

    public $id;

    protected function saveValueCek()
    {
        $this->role_id = Role::where('rolecat_idrolecat', $this->id)
            ->pluck('appmenunew_idappmenunew')
            ->all();

        return $this->role_id;
    }

    public function mount($id)
    {
        $this->id = $id;
        $this->role_id = $this->saveValueCek();
    }

    public function tambahMenuRole()
    {
        $appmenus = Appmenunew::whereIn('idappmenunew', $this->role_id)->get();

        if ($appmenus->isEmpty()) {
            $this->alert('error', 'Menu tidak ditemukan!');

            return;
        }

        foreach ($appmenus as $appmenu) {
            Role::updateOrCreate(
                [
                    'rolecat_idrolecat' => $this->id,
                    'appmenunew_idappmenunew' => $appmenu->idappmenunew,
                ],
                [
                    'rolecat_idrolecat' => $this->id,
                    'appmenunew_idappmenunew' => $appmenu->idappmenunew,
                ]
            );
        }

        $this->alert('success', 'Menu berhasil ditambahkan pada pengguna!');

        $this->js('window.location.reload();');
    }

    public function deleteConfirmationRolemenu($idrole)
    {
        $this->idrole = $idrole;
        $this->dispatch('show-delete-confirmation-rolemenus-modal');
    }

    public function deleteRolemenu()
    {
        $rolemenu = Role::where('idrole', $this->idrole)->first();

        try {
            $rolemenu->delete();
            $this->alert('success', 'Data rolemenu Berhasil Di hapus');
        } catch (\Throwable $th) {
            // throw $th;
            $this->alert('error', 'Data Tidak Bisa di Hapus');
        }

        $this->dispatch('close-rolemenus-modal');
        $this->idrole = '';

        $this->js('window.location.reload();');

    }

    public function render()
    {

        $appMenuCat = Appmenucat::get();
        $appMenu = Appmenunew::with(['appmenucat'])->get();

        // Master
        $rolecat = Rolecat::where('idrolecat', $this->id)->first();

        $role = Role::with(['rolecat', 'appmenunew'])
            ->where('rolecat_idrolecat', $this->id)
            ->search($this->search)
            ->paginate($this->perPage);

        return view('livewire.role-component', [
            'roles' => $role,
            'rolecat' => $rolecat,
            'appmenu' => $appMenu,
            'appMenuCat' => $appMenuCat,
        ]);
    }
}
