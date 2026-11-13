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

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

    private function pastikanBolehKelola(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
    }

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

    public function updatingPerpage()
    {
        $this->resetPage();
    }

    public function storeDocscat()
    {
        $this->pastikanBolehKelola();

        $this->validate([
            // 'iddocscat' => 'required ',
            'docscat' => 'required|string|max:255|unique:docscat,docscat',
            'urut' => 'required|integer|min:1|unique:docscat,urut',
        ]);

        $docscat = new Docscat;
        // $docscat->iddocscat = $this->iddocscat;
        $docscat->docscat = $this->docscat;
        $docscat->urut = $this->urut;
        $docscat->save();
        $this->close();

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
        $this->pastikanBolehKelola();

        $docscat = Docscat::query()->findOrFail((int) $iddocscat);

        $this->iddocscat = $docscat->iddocscat;
        $this->docscat = $docscat->docscat;
        $this->urut = $docscat->urut;
        // $this->docscatName = $docscat ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-docscat-modal');
    }

    public function updateDocscat()
    {
        $this->pastikanBolehKelola();

        $this->validate([
            // 'iddocscat' => 'required ',
            'docscat' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('docscat', 'docscat')->ignore((int) $this->iddocscat, 'iddocscat'),
            ],
            'urut' => [
                'required',
                'integer',
                'min:1',
                \Illuminate\Validation\Rule::unique('docscat', 'urut')->ignore((int) $this->iddocscat, 'iddocscat'),
            ],
        ]);

        $docscat = Docscat::query()->findOrFail((int) $this->iddocscat);
        $docscat->fill([
            'docscat' => $this->docscat,
            'urut' => (int) $this->urut,
        ])->save();

        $this->close();

        $this->alert('success', 'Update data docscat has been added successfully');

        $this->dispatch('close-docscat-modal');
    }

    public function deleteConfirmationDocscat($iddocscat)
    {
        $this->pastikanBolehKelola();

        $this->iddocscat = $iddocscat;
        $this->dispatch('show-delete-confirmation-docscat-modal');
    }

    public function deleteDocscat()
    {
        $this->pastikanBolehKelola();

        $docscat = Docscat::query()->findOrFail((int) $this->iddocscat);
        try {
            // Kategori yang masih memiliki dokumen tidak dapat dihapus (RESTRICT FK).
            if ($docscat->docs()->exists()) {
                $this->alert('error', 'Kategori masih memiliki dokumen. Pindahkan atau hapus dokumennya terlebih dahulu.');
                $this->dispatch('close-docscat-modal');
                $this->iddocscat = '';

                return;
            }

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
        $this->pastikanBolehKelola();
        $queryResult = $this->dataDocscat();
        $data = [['iddocscat', 'docscat', 'urut']];
        foreach ($queryResult as $result) {
            $data[] = [$result->iddocscat, $result->docscat, $result->urut];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $filename = 'exported-docscat-data.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportToPdf()
    {
        $this->pastikanBolehKelola();
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
            $options .= '<option value="'.(int) $row->iddocscat.'"';
            if ($id == $row->iddocscat) {
                $options .= ' selected';
            }
            $options .= '>'.e($row->docscat).'</option>';
        }

        return $options;
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

        // mengambil data render dari function data
        $docscat = $this->dataDocscat();

        return view('livewire.docscat-component', [
            'docscats' => $docscat,
        ])->layout('layout.docslayout');

    }
}
