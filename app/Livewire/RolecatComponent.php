<?php

namespace App\Livewire;

use App\Models\Rolecat;
use Barryvdh\DomPDF\Facade\Pdf;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RolecatComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $idrolecat;

    public $rolecat;

    public $rolecatName;

    public $searchlov = '';

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

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

    public function storeRolecat()
    {
        $this->validate([
            // 'idrolecat' => 'required ',
            'rolecat' => 'required ',
        ]);

        $rolecat = new Rolecat;
        // $rolecat->idrolecat = $this->idrolecat;
        $rolecat->rolecat = $this->rolecat;
        $rolecat->save();
        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'New rolecat has been added successfully');

        $this->dispatch('close-rolecat-modal');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatch('close-rolecat-modal');
    }

    public function editRolecat($idrolecat)
    {
        $rolecat = rolecat::where('idrolecat', $idrolecat)->first();

        $this->idrolecat = $rolecat->idrolecat;
        $this->rolecat = $rolecat->rolecat;
        // untuk edit jika menggunakan LOV  isi  tabelName LOV
        // $this->rolecatName = $rolecat ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-rolecat-modal');
    }

    public function updateRolecat()
    {
        $this->validate([
            'idrolecat' => 'required ',
            //      'rolecat' => 'required ',
        ]);
        $rolecat = Rolecat::where('idrolecat', $this->idrolecat)->first();
        $rolecat->idrolecat = $this->idrolecat;
        $rolecat->rolecat = $this->rolecat;
        $rolecat->save();

        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'Update data rolecat has been added successfully');

        $this->dispatch('close-rolecat-modal');
    }

    public function deleteConfirmationRolecat($idrolecat)
    {
        $this->idrolecat = $idrolecat;
        $this->dispatch('show-delete-confirmation-rolecat-modal');
    }

    public function deleteRolecat()
    {
        $rolecat = Rolecat::where('idrolecat', $this->idrolecat)->first();

        try {
            $rolecat->delete();
            $this->alert('success', 'Data rolecat Berhasil Di hapus');
        } catch (\Throwable $th) {
            // throw $th;
            $this->alert('error', 'Data Tidak bisa dihapus, Terdapat data yang terkait !');

        }
        $this->dispatch('close-rolecat-modal');
        $this->idrolecat = '';
    }

    public function exportToExcel()
    {
        $queryResult = Rolecat::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [['idrolecat', 'rolecat']];
        foreach ($queryResult as $result) {
            $data[] = [$result->idrolecat, $result->rolecat];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $filename = 'exported-rolecat-data.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportToPdf()
    {
        $headers = ['idrolecat', 'rolecat'];
        $queryResult = Rolecat::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [];
        foreach ($queryResult as $result) {
            $data[] = [$result->idrolecat, $result->rolecat];
        }
        $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers'));
        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'rolecat.pdf');
    }

    public function rolecatOption($id = null)
    {
        $result = Rolecat::get();
        $options = '';
        foreach ($result as $row) {
            $options .= '<option value='.$row->idrolecat.'';
            if ($id == $row->idrolecat) {
                $options .= ' selected';
            }
            $options .= '>'.$row->rolecat.'</option>';
        }

        return $options;
    }

    public function rolecatLOV($id)
    {
        $rolecat = Rolecat::where('idrolecat', $id)->first();
        // $this->nama_relasi = $rolecat ->idrolecat;
        $this->rolecatName = $rolecat->rolecat;
        $this->alert('success', 'Terpilih : '.$rolecat->rolecat);
        $this->resetPage('pageLOV');

        $this->dispatch('close-modal-lov');
    }

    public function render()
    {

        // aktifkan dan sesuaikan jika menggunakan LOV
        //  $namalov = ModelLOV::search($this->searchlov)->paginate($this->perPage, ['*'], 'pageLOV');

        $rolecat = Rolecat::latest()
            ->search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->paginate($this->perPage);

        return view('livewire.rolecat-component', [
            'rolecats' => $rolecat,

        ]);
    }
}
