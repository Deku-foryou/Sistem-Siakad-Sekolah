<?php

namespace App\Livewire;

use App\Models\Roles;
use Barryvdh\DomPDF\Facade\Pdf;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RolesComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $id;

    public $name;

    public $guard_name;

    public $rolesName;

    public $searchlov = '';

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

    public function mount()
    {
        $this->guard_name = 'web';
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

    public function storeRoles()
    {
        $this->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required',
        ]);

        $roles = new Roles;
        $roles->name = $this->name;
        $roles->guard_name = $this->guard_name;
        $roles->save();
        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'New roles has been added successfully');

        $this->dispatch('close-roles-modal');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->guard_name = 'web';

        $this->dispatch('close-roles-modal');
    }

    public function editRoles($id)
    {
        $roles = Roles::where('id', $id)->first();

        $this->id = $roles->id;
        $this->name = $roles->name;
        $this->guard_name = $roles->guard_name;
        // untuk edit jika menggunakan LOV  isi  tabelName LOV
        // $this->rolesName = $roles ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-roles-modal');
    }

    public function updateRoles()
    {
        $this->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required',
        ]);
        $roles = Roles::where('id', $this->id)->first();
        $roles->name = $this->name;
        $roles->guard_name = $this->guard_name;
        $roles->save();

        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'Update data roles has been added successfully');

        $this->guard_name = 'web';
        $this->dispatch('close-roles-modal');

    }

    public function deleteConfirmationRoles($id)
    {
        $this->id = $id;
        $this->dispatch('show-delete-confirmation-roles-modal');
    }

    public function deleteRoles()
    {
        $roles = Roles::where('id', $this->id)->first();
        $roles->delete();
        $this->alert('success', 'Data roles Berhasil Di hapus');
        $this->guard_name = 'web';
        $this->dispatch('close-roles-modal');
        $this->id = '';
    }

    public function exportToExcel()
    {
        $queryResult = Roles::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [['id', 'name', 'guard_name']];
        foreach ($queryResult as $result) {
            $data[] = [$result->id, $result->name, $result->guard_name];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $writer = new Xlsx($spreadsheet);
        $filename = 'exported-roles-data.xlsx';
        $writer->save($filename);

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function exportToPdf()
    {
        $headers = ['id', 'name', 'guard_name'];
        $queryResult = Roles::latest()
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
        }, 'Roles.pdf');
    }

    public function rolesOption($id = null)
    {
        $result = Roles::get();
        $options = '';
        foreach ($result as $row) {
            $options .= '<option value='.$row->id.'';
            if ($id == $row->id) {
                $options .= ' selected';
            }
            $options .= '>'.$row->roles.'</option>';
        }

        return $options;
    }

    public function rolesLOV($id)
    {
        $roles = Roles::where('id', $id)->first();
        // $this->nama_relasi = $roles ->id;
        $this->rolesName = $roles->roles;
        $this->alert('success', 'Terpilih : '.$roles->roles);
        $this->resetPage('pageLOV');

        $this->dispatch('close-modal-lov');
    }

    public function render()
    {

        // aktifkan dan sesuaikan jika menggunakan LOV
        //  $namalov = ModelLOV::search($this->searchlov)->paginate($this->perPage, ['*'], 'pageLOV');

        $roles = Roles::latest()
            ->search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->paginate($this->perPage);

        return view('livewire.roles-component', [
            'roless' => $roles,
        ]);
    }
}
