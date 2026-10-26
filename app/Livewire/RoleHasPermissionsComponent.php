<?php
namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Permissions;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Permission\Models\Role;
use App\Models\Role_has_permissions;
use Spatie\Permission\Models\Permission;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class RoleHasPermissionsComponent extends Component
{

  use WithPagination;
  use WithFileUploads;
  use LivewireAlert;

  public $id, $role_id;

  public $permission_id = [];
  public $role_has_permissionsName;
  public $searchlov = '';

  public $search = '';
  public $tglstart = '';
  public $tglend = '';
  public $perPage = 10;

  protected function saveValueCek()
  {

    $this->permission_id = Role_has_permissions::where('role_id', $this->id)
      ->pluck('permission_id')
      ->all();

    return $this->permission_id;
  }

  public function selectAll()
  {
    $this->permission_id = Permissions::with(['gruppermissions'])->pluck('id')->toArray();
  }


  public function mount($id)
  {
    $this->id = $id;
    $this->permission_id = $this->saveValueCek();

  }

  public function updatingSearch()
  {
    $this->resetPage();
  }


  public function updatingSearchlov()
  {
    $this->resetPage();
    $this->resetPage('pageLOV');
  }


  public function updatingPerpage()
  {
    $this->resetPage();
    $this->resetPage('pageLOV');
  }


  public function storeRole_has_permissions()
  {
    $permission = Permission::find($this->permission_id);
    $role = Role::find($this->id);

    if ($role && $permission) {
      $role->syncPermissions($permission);
    } else {
      $this->alert('error', 'Pilih Minimal satu Permissions');
    }

    $this->alert('success', 'Permissions Baru Berhasil Ditambahkan pada Roles !');

    $this->dispatch('close-modal');
  }


  public function close()
  {
    $this->reset();
    $this->resetValidation();
    $this->dispatch('close-role_has_permissions-modal');
  }




  public function exportToExcel()
  {
    $queryResult = Role_has_permissions::latest()
      ->search($this->search)
      ->paginate($this->perPage);
    $data = [['permission_id', 'role_id']];
    foreach ($queryResult as $result) {
      $data[] = [$result->permission_id, $result->role_id];
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($data, null, 'A1');
    $writer = new Xlsx($spreadsheet);
    $filename = 'exported-role_has_permissions-data.xlsx';
    $writer->save($filename);
    return response()->download($filename)->deleteFileAfterSend(true);
  }


  public function exportToPdf()
  {
    $headers = ['permission_id', 'role_id'];
    $queryResult = Role_has_permissions::latest()
      ->search($this->search)
      ->paginate($this->perPage);
    $data = [];
    foreach ($queryResult as $result) {
      $data[] = [$result->permission_id, $result->role_id];
    }
    $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers'));
    $pdf->setPaper('A4', 'portrait');
    return response()->streamDownload(function () use ($pdf) {
      echo $pdf->stream();
    }, 'Role_has_permissions.pdf');
  }


  public function role_has_permissionsOption($id = null)
  {
    $result = Role_has_permissions::get();
    $options = '';
    foreach ($result as $row) {
      $options .= '<option value=' . $row->idrole_has_permissions . '';
      if ($id == $row->idrole_has_permissions) {
        $options .= ' selected';
      }
      $options .= '>' . $row->role_has_permissions . '</option>';
    }
    return $options;
  }


  public function role_has_permissionsLOV($id)
  {
    $role_has_permissions = Role_has_permissions::where('idrole_has_permissions', $id)->first();
    // $this->nama_relasi = $role_has_permissions ->idrole_has_permissions;  
    $this->role_has_permissionsName = $role_has_permissions->role_has_permissions;
    $this->alert('success', 'Terpilih : ' . $role_has_permissions->role_has_permissions);
    $this->resetPage('pageLOV');

    $this->dispatch('close-modal-lov');
  }


  public function render()
  {

    // aktifkan dan sesuaikan jika menggunakan LOV
    //  $namalov = ModelLOV::search($this->searchlov)->paginate($this->perPage, ['*'], 'pageLOV');  

    $permissions = Permissions::get();

    $role_has_permissions = Role_has_permissions::with(['roles', 'permissions'])
      ->where('role_id', $this->id)
      ->search($this->search)
      ->get();

    //->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal 
    // ->paginate($this->perPage);
    return view('livewire.role-has-permissions-component', [
      'role_has_permissionss' => $role_has_permissions,
      'permissions' => $permissions,
    ]);
  }
}