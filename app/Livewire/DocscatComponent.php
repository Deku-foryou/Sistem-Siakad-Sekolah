<?php

namespace App\Livewire;

use App\Models\Docscat;
use Barryvdh\DomPDF\Facade\Pdf;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DocscatComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $iddocscat;

    public $docscat;

    public $urut;

    public $docscatName;

    public $searchlov = '';

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

    public function mount()
    {
        $this->tglstart = now()->firstOfMonth()->format('Y-m-d');
        $this->tglend = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTglstart()
    {
        $this->resetPage();
    }

    public function updatingTglend()
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

    public function storeDocscat()
    {
        $this->validate([
            // 'iddocscat' => 'required ',
            'docscat' => 'required ',
            'urut' => 'required | unique:docscat,urut',
        ]);

        $docscat = new Docscat;
        // $docscat->iddocscat = $this->iddocscat;
        $docscat->docscat = $this->docscat;
        $docscat->urut = $this->urut;
        $docscat->save();
        $this->close();

        $this->resetPage('pageLOV');

        $this->alert('success', 'New docscat has been added successfully');

        $this->dispatch('close-docscat-modal');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->tglstart = now()->firstOfMonth()->format('Y-m-d');
        $this->tglend = now()->format('Y-m-d');
        $this->dispatch('close-docscat-modal');
    }

    public function editDocscat($iddocscat)
    {
        $docscat = Docscat::where('iddocscat', $iddocscat)->first();

        $this->iddocscat = $docscat->iddocscat;
        $this->docscat = $docscat->docscat;
        $this->urut = $docscat->urut;
        // untuk edit jika menggunakan LOV  isi  tabelName LOV
        // $this->docscatName = $docscat ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-docscat-modal');
    }

    public function updateDocscat()
    {
        $this->validate([
            // 'iddocscat' => 'required ',
            'docscat' => 'required ',
            'urut' => 'required',
        ]);
        $docscat = Docscat::where('iddocscat', $this->iddocscat)->first();
        $docscat->iddocscat = $this->iddocscat;
        $docscat->docscat = $this->docscat;
        $docscat->urut = $this->urut;
        $docscat->save();

        $this->close();

        $this->resetPage('pageLOV');

        $this->alert('success', 'Update data docscat has been added successfully');

        $this->dispatch('close-docscat-modal');
    }

    public function deleteConfirmationDocscat($iddocscat)
    {
        $this->iddocscat = $iddocscat;
        $this->dispatch('show-delete-confirmation-docscat-modal');
    }

    public function deleteDocscat()
    {
        $docscat = Docscat::where('iddocscat', $this->iddocscat)->first();
        try {
            $docscat->delete();
            $this->alert('success', 'Data docscat Berhasil Di hapus');
        } catch (\Throwable) {
            $this->alert('error', 'Ada data yang terkait, Data docscat Gagal Di hapus');
        }
        $this->dispatch('close-docscat-modal');
        $this->iddocscat = '';
    }

    public function exportToExcel()
    {
        $queryResult = $this->dataDocscat();
        $data = [['iddocscat', 'docscat', 'urut']];
        foreach ($queryResult as $result) {
            $data[] = [$result->iddocscat, $result->docscat, $result->urut];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $writer = new Xlsx($spreadsheet);
        $filename = 'exported-docscat-data.xlsx';
        $writer->save($filename);

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function exportToPdf()
    {
        $headers = ['iddocscat', 'docscat', 'urut'];
        $title = 'Export Data Docscat';
        $queryResult = $this->dataDocscat();
        $data = [];
        foreach ($queryResult as $result) {
            $data[] = [$result->iddocscat, $result->docscat, $result->urut];
        }
        $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers', 'title'));
        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Docscat.pdf');
    }

    public function docscatOption($id = null)
    {
        $result = Docscat::get();
        $options = '';
        foreach ($result as $row) {
            $options .= '<option value='.$row->iddocscat.'';
            if ($id == $row->iddocscat) {
                $options .= ' selected';
            }
            $options .= '>'.$row->docscat.'</option>';
        }

        return $options;
    }

    public function docscatLOV($id)
    {
        $docscat = Docscat::where('iddocscat', $id)->first();
        // $this->nama_relasi = $docscat ->iddocscat;
        $this->docscatName = $docscat->docscat;
        $this->alert('success', 'Terpilih : '.$docscat->docscat);
        $this->resetPage('pageLOV');

        $this->dispatch('close-modal-lov');
    }

    // untuk mencetak data yang natinya akan di tampilkan di render (memidah dari render agar flexible)
    public function dataDocscat()
    {

        $docscat = Docscat::search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->simplePaginate($this->perPage);

        return $docscat;

    }

    public function render()
    {

        // aktifkan dan sesuaikan jika menggunakan LOV
        //  $namalov = ModelLOV::search($this->searchlov)->simplePaginate($this->perPage, ['*'], 'pageLOV');

        // mengambil data render dari function data
        $docscat = $this->dataDocscat();

        return view('livewire.docscat-component', [
            'docscats' => $docscat,
        ])->layout('layout.docslayout');

    }
}
