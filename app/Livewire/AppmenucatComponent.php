<?php

namespace App\Livewire;

use App\Models\Appmenucat;
use Barryvdh\DomPDF\Facade\Pdf;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AppmenucatComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $idappmenucat;

    public $appmenucat;

    public $urut;

    public $appmenucatName;

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

    public function storeAppmenucat()
    {
        $this->validate([
            // 'idappmenucat' => 'required ',
            'appmenucat' => 'required ',
            'urut' => 'required|unique:appmenucat,urut|numeric',
        ]);

        $appmenucat = new Appmenucat;
        // $appmenucat->idappmenucat = $this->idappmenucat;
        $appmenucat->appmenucat = $this->appmenucat;
        $appmenucat->urut = $this->urut;
        $appmenucat->save();
        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'New appmenucat has been added successfully');

        $this->dispatch('close-appmenucat-modal');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatch('close-appmenucat-modal');
    }

    public function editAppmenucat($idappmenucat)
    {
        $appmenucat = Appmenucat::where('idappmenucat', $idappmenucat)->first();

        $this->idappmenucat = $appmenucat->idappmenucat;
        $this->appmenucat = $appmenucat->appmenucat;
        $this->urut = $appmenucat->urut;
        // untuk edit jika menggunakan LOV  isi  tabelName LOV
        // $this->appmenucatName = $appmenucat ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-appmenucat-modal');
    }

    public function updateAppmenucat()
    {
        $this->validate([
            // 'idappmenucat' => 'required ',
            'appmenucat' => 'required ',
            //      'urut' => 'required ',
        ]);
        $appmenucat = Appmenucat::where('idappmenucat', $this->idappmenucat)->first();
        $appmenucat->idappmenucat = $this->idappmenucat;
        $appmenucat->appmenucat = $this->appmenucat;
        $appmenucat->urut = $this->urut;
        $appmenucat->save();

        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'Update data appmenucat has been added successfully');

        $this->dispatch('close-appmenucat-modal');
    }

    public function deleteConfirmationAppmenucat($idappmenucat)
    {
        $this->idappmenucat = $idappmenucat;
        $this->dispatch('show-delete-confirmation-appmenucat-modal');
    }

    public function deleteAppmenucat()
    {
        $appmenucat = Appmenucat::where('idappmenucat', $this->idappmenucat)->first();

        try {
            $appmenucat->delete();
            $this->alert('success', 'Data appmenucat Berhasil Di hapus');
        } catch (\Throwable $th) {
            // throw $th;
            $this->alert('error', 'Terdapat Data yang terkait, Data Tidak Bisa di Hapus');

        }

        $this->dispatch('close-appmenucat-modal');
        $this->idappmenucat = '';
    }

    public function exportToExcel()
    {
        $queryResult = Appmenucat::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [['idappmenucat', 'appmenucat', 'urut']];
        foreach ($queryResult as $result) {
            $data[] = [$result->idappmenucat, $result->appmenucat, $result->urut];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $filename = 'exported-appmenucat-data.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportToPdf()
    {
        $headers = ['idappmenucat', 'appmenucat', 'urut'];
        $queryResult = Appmenucat::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [];
        foreach ($queryResult as $result) {
            $data[] = [$result->idappmenucat, $result->appmenucat, $result->urut];
        }
        $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers'));
        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Appmenucat.pdf');
    }

    public function appmenucatOption($id = null)
    {
        $result = Appmenucat::get();
        $options = '';
        foreach ($result as $row) {
            $options .= '<option value="'.(int) $row->idappmenucat.'"';
            if ($id == $row->idappmenucat) {
                $options .= ' selected';
            }
            $options .= '>'.e($row->appmenucat).'</option>';
        }

        return $options;
    }

    public function appmenucatLOV($id)
    {
        $appmenucat = Appmenucat::where('idappmenucat', $id)->first();
        // $this->nama_relasi = $appmenucat ->idappmenucat;
        $this->appmenucatName = $appmenucat->appmenucat;
        $this->alert('success', 'Terpilih : '.$appmenucat->appmenucat);
        $this->resetPage('pageLOV');

        $this->dispatch('close-modal-lov');
    }

    public function render()
    {

        // aktifkan dan sesuaikan jika menggunakan LOV
        //  $namalov = ModelLOV::search($this->searchlov)->paginate($this->perPage, ['*'], 'pageLOV');

        $appmenucat = Appmenucat::latest()
            ->search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->paginate($this->perPage);

        return view('livewire.appmenucat-component', [
            'appmenucats' => $appmenucat,
        ]);
    }
}
