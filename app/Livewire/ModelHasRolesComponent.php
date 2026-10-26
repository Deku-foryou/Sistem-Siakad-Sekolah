<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Roles;
use Spatie\Permission\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Model_has_roles;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ModelHasRolesComponent extends Component
{
  use WithPagination;
  use LivewireAlert;

  public $role_id = [];

  public $model_id;

  public $search = '';

  public $perPage = 10;

  public $id;


  protected function saveValueCek()
  {

    $this->role_id = Model_has_roles::where('model_id', $this->id)
      ->pluck('role_id')
      ->all();

    return $this->role_id;
  }


  public function mount($id)
  {
    $this->id = $id;
    $this->role_id = $this->saveValueCek();
  }


  public function updatingSearch()
  {
    $this->resetPage();
  }


  public function storeModel_has_roles()
  {
    $user = User::findOrFail($this->id);

    $role = Role::findOrFail($this->role_id);

    if ($role && $user) {
      $user->syncRoles($role);
    } else {
      $this->alert('error', 'User atau Role Tidak Ada!');
    }

    $this->alert('success', 'Role Baru Berhasil Ditambahakan pada Users !');

    $this->dispatch('close-modal');
  }


  public function render()
  {

    $roles = Roles::get();

    $model_has_roles = Model_has_roles::with(['roles', 'users'])
      ->where('model_id', $this->id)
      ->search($this->search)
      ->paginate($this->perPage);


    return view('livewire.model-has-roles-component', ['model_has_roles' => $model_has_roles, 'roles' => $roles]);
  }


}