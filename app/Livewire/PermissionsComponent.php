<?php
namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Permissions;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Barryvdh\DomPDF\Facade\Pdf;

class PermissionsComponent extends Component
{

  use WithPagination;
  use WithFileUploads;
  use LivewireAlert;

  public $id, $name, $guard_name;
  public $permissionsName;
  public $searchlov = '';
  public $search = '';
  public $tglstart = '';
  public $tglend = '';
  public $perPage = 10;

  public function mount()
  {
    $this->guard_name = "web";
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


  public function storePermissions()
  {
    $this->validate([
      'name' => 'required ',
      'guard_name' => 'required ',
    ]);


    $permissions = new Permissions();
    $permissions->name = $this->name;
    $permissions->guard_name = $this->guard_name;
    $permissions->save();
    $this->reset();

    $this->resetPage('pageLOV');

    $this->alert('success', 'Permissions has been added successfully');

    $this->dispatch('close-permissions-modal');
  }


  public function close()
  {
    $this->reset();
    $this->resetValidation();
    $this->dispatch('close-permissions-modal');
  }


  public function editPermissions($id)
  {
    $permissions = Permissions::where('id', $id)->first();

    $this->id = $permissions->id;
    $this->name = $permissions->name;
    $this->guard_name = $permissions->guard_name;
    // untuk edit jika menggunakan LOV  isi  tabelName LOV
// $this->permissionsName = $permissions ->nama_relasi_yang_di_jadikan_LOV; 

    $this->dispatch('show-edit-permissions-modal');
  }


  public function updatePermissions()
  {
    $this->validate([
      'name' => 'required ',
      'guard_name' => 'required ',
    ]);
    $permissions = Permissions::where('id', $this->id)->first();
    $permissions->name = $this->name;
    $permissions->guard_name = $this->guard_name;
    $permissions->save();

    $this->reset();

    $this->resetPage('pageLOV');

    $this->alert('success', 'Update data permissions has been added successfully');

    $this->dispatch('close-permissions-modal');
  }


  public function deleteConfirmationPermissions($id)
  {
    $this->id = $id;
    $this->dispatch('show-delete-confirmation-permissions-modal');
  }


  public function deletePermissions()
  {
    $permissions = Permissions::where('id', $this->id)->first();
    $permissions->delete();
    $this->alert('success', 'Data permissions Berhasil Di hapus');
    $this->guard_name = "web";
    $this->dispatch('close-permissions-modal');
    $this->id = '';
  }


  public function exportToExcel()
  {
    $queryResult = Permissions::latest()
      ->search($this->search)
      ->paginate($this->perPage);
    $data = [['id', 'name', 'guard_name']];
    foreach ($queryResult as $result) {
      $data[] = [$result->id, $result->name, $result->guard_name];
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($data, null, 'A1');
    $writer = new Xlsx($spreadsheet);
    $filename = 'exported-permissions-data.xlsx';
    $writer->save($filename);
    return response()->download($filename)->deleteFileAfterSend(true);
  }


  public function exportToPdf()
  {
    $headers = ['id', 'name', 'guard_name'];
    $queryResult = Permissions::latest()
      ->search($this->search)
      ->paginate($this->perPage);
    $data = [];
    foreach ($queryResult as $result) {
      $data[] = [$result->id, $result->name, $result->guard_name];
    }
    $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers'));
    $pdf->setPaper('A4', 'portrait');
    return response()->streamDownload(function () use ($pdf) {
      echo $pdf->stream();
    }, 'Permissions.pdf');
  }


  public function permissionsOption($id = null)
  {
    $result = Permissions::get();
    $options = '';
    foreach ($result as $row) {
      $options .= '<option value=' . $row->id . '';
      if ($id == $row->id) {
        $options .= ' selected';
      }
      $options .= '>' . $row->permissions . '</option>';
    }
    return $options;
  }


  public function permissionsLOV($id)
  {
    $permissions = Permissions::where('id', $id)->first();
    // $this->nama_relasi = $permissions ->id;  
    $this->permissionsName = $permissions->permissions;
    $this->alert('success', 'Terpilih : ' . $permissions->permissions);
    $this->resetPage('pageLOV');

    $this->dispatch('close-modal-lov');
  }


  public function render()
  {

    // aktifkan dan sesuaikan jika menggunakan LOV
    //  $namalov = ModelLOV::search($this->searchlov)->paginate($this->perPage, ['*'], 'pageLOV');  

    $permissions = Permissions::latest()
      ->search($this->search)
      //->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal 
      ->paginate($this->perPage);
    return view('livewire.permissions-component', [
      'permissionss' => $permissions
    ]);
  }
}