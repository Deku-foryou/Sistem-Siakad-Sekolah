<?php

namespace App\Livewire;

use App\Models\Docs;
use App\Models\Docscat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DocsComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $iddocs;

    public $title;

    public $content;

    public $urut;

    public $docscat_iddocscat;

    public $docsName;

    public $searchlov = '';

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

    public function storeDocs()
    {
        $this->pastikanBolehKelola();

        $this->validate([
            // 'iddocs' => 'required ',
            'title' => 'required|string|max:255',
            'content' => 'required|file|mimes:pdf|max:10000',
            'urut' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('docs', 'urut')->where(function ($query) {
                    return $query->where('docscat_iddocscat', $this->docscat_iddocscat);
                }),
            ],
            'docscat_iddocscat' => 'required|integer|exists:docscat,iddocscat',
        ]);

        $namaFile = 'docs_'.now()->format('YmdHis').'_'.uniqid('', true).'.'.$this->content->extension();

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($namaFile) {
                $docs = new Docs;
                $docs->title = $this->title;

                $this->content->storeAs('tutorial-website', $namaFile);
                $docs->content = $namaFile;

                $docs->urut = (int) $this->urut;
                $docs->docscat_iddocscat = (int) $this->docscat_iddocscat;
                $docs->save();
            });
        } catch (\Throwable $e) {
            // Jangan meninggalkan file yatim bila transaksi gagal.
            try {
                Storage::delete('tutorial-website/'.$namaFile);
            } catch (\Throwable) {
                \Illuminate\Support\Facades\Log::warning('docs-store: gagal menghapus file yatim', ['nama' => $namaFile]);
            }

            throw $e;
        }

        $this->close();

        $this->resetPage('pageLOV');

        $this->alert('success', 'New docs has been added successfully');

        $this->dispatch('close-docs-modal');
        $this->js('window.location.reload();');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->tglstart = now()->firstOfMonth()->format('Y-m-d');
        $this->tglend = now()->format('Y-m-d');
        $this->dispatch('close-docs-modal');
    }

    public function editDocs($iddocs)
    {
        $this->pastikanBolehKelola();

        $docs = Docs::query()->findOrFail((int) $iddocs);

        $this->iddocs = $docs->iddocs;
        $this->title = $docs->title;
        $this->content = $docs->content;
        $this->urut = $docs->urut;
        $this->docscat_iddocscat = $docs->docscat_iddocscat;
        // untuk edit jika menggunakan LOV  isi  tabelName LOV
        // $this->docsName = $docs ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-docs-modal');
    }

    public function updateDocs()
    {
        $this->pastikanBolehKelola();

        // Validasi hanya bila ada file baru; nilai string lama bukan UploadedFile.
        $this->validate([
            'iddocs' => 'required|integer',
            'title' => 'required|string|max:255',
            'docscat_iddocscat' => 'required|integer|exists:docscat,iddocscat',
            'urut' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('docs', 'urut')
                    ->where(fn ($query) => $query->where('docscat_iddocscat', $this->docscat_iddocscat))
                    ->ignore($this->iddocs, 'iddocs'),
            ],
        ]);

        if ($this->content instanceof \Illuminate\Http\UploadedFile) {
            $this->validate(['content' => 'file|mimes:pdf|max:10000']);
        }

        $docs = Docs::query()->findOrFail((int) $this->iddocs);

        $baru = [
            'title' => $this->title,
            'urut' => (int) $this->urut,
            'docscat_iddocscat' => (int) $this->docscat_iddocscat,
        ];

        $namaLama = $docs->content;
        $namaBaru = null;

        if ($this->content instanceof \Illuminate\Http\UploadedFile) {
            $namaBaru = 'docs_'.now()->format('YmdHis').'_'.uniqid('', true).'.'.$this->content->extension();
            $baru['content'] = $namaBaru;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($docs, $baru, $namaBaru) {
                if ($namaBaru !== null) {
                    $this->content->storeAs('tutorial-website', $namaBaru);
                }
                $docs->fill($baru)->save();
            });
        } catch (\Throwable $e) {
            if ($namaBaru !== null) {
                try {
                    Storage::delete('tutorial-website/'.$namaBaru);
                } catch (\Throwable) {
                    \Illuminate\Support\Facades\Log::warning('docs-update: gagal menghapus file yatim', ['nama' => $namaBaru]);
                }
            }

            throw $e;
        }

        if ($namaBaru !== null && $namaLama) {
            try {
                Storage::delete('tutorial-website/'.$namaLama);
            } catch (\Throwable) {
                \Illuminate\Support\Facades\Log::warning('docs-update: gagal menghapus file lama', ['nama' => $namaLama]);
            }
        }

        $this->close();

        $this->resetPage('pageLOV');

        $this->alert('success', 'Update data docs has been added successfully');

        $this->dispatch('close-docs-modal');

        $this->js('window.location.reload();');
    }

    public function deleteConfirmationDocs($iddocs)
    {
        $this->pastikanBolehKelola();

        $this->iddocs = $iddocs;
        $this->dispatch('show-delete-confirmation-docs-modal');
    }

    public function deleteDocs()
    {
        $this->pastikanBolehKelola();

        $docs = Docs::query()->findOrFail((int) $this->iddocs);
        try {
            $content = $docs->content;
            $docs->delete();

            if (! is_null($content)) {
                try {
                    Storage::delete('tutorial-website/'.$content);
                } catch (\Throwable) {
                    \Illuminate\Support\Facades\Log::warning('docs-delete: gagal menghapus file', ['nama' => $content]);
                }
            }

            $this->alert('success', 'Data docs Berhasil Di hapus');
        } catch (\Throwable) {
            $this->alert('error', 'Ada data yang terkait, Data docs Gagal Di hapus');
        }
        $this->dispatch('close-docs-modal');
        $this->iddocs = '';
    }

    public function exportToExcel()
    {
        $this->pastikanBolehKelola();
        $queryResult = $this->dataDocs();
        $data = [['iddocs', 'title', 'content', 'urut', 'docscat_iddocscat']];
        foreach ($queryResult as $result) {
            $data[] = [$result->iddocs, $result->title, $result->content, $result->urut, $result->docscat_iddocscat];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');

        // Spreadsheet di-stream dari output buffer; tidak ada file yang ditulis ke root proyek.
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'exported-docs-data.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportToPdf()
    {
        $this->pastikanBolehKelola();
        $headers = ['iddocs', 'title', 'content', 'urut', 'docscat_iddocscat'];
        $title = 'Export Data Docs';
        $queryResult = $this->dataDocs();
        $data = [];
        foreach ($queryResult as $result) {
            $data[] = [$result->iddocs, $result->title, $result->content, $result->urut, $result->docscat_iddocscat];
        }
        $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers', 'title'));
        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Docs.pdf');
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

    public function docsOption($id = null)
    {
        $result = Docs::get();
        $options = '';
        foreach ($result as $row) {
            $options .= '<option value="'.(int) $row->iddocs.'"';
            if ($id == $row->iddocs) {
                $options .= ' selected';
            }
            $options .= '>'.e($row->docs).'</option>';
        }

        return $options;
    }

    public function docsLOV($id)
    {
        $docs = Docs::where('iddocs', $id)->first();
        // $this->nama_relasi = $docs ->iddocs;
        $this->docsName = $docs->docs;
        $this->alert('success', 'Terpilih : '.$docs->docs);
        $this->resetPage('pageLOV');

        $this->dispatch('close-modal-lov');
    }

    // untuk mencetak data yang natinya akan di tampilkan di render (memidah dari render agar flexible)
    public function dataDocs()
    {

        $docs = Docs::search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->simplePaginate($this->perPage);

        return $docs;

    }

    public function render()
    {

        // aktifkan dan sesuaikan jika menggunakan LOV
        //  $namalov = ModelLOV::search($this->searchlov)->simplePaginate($this->perPage, ['*'], 'pageLOV');

        $docscatOption = $this->docscatOption();

        // mengambil data render dari function data
        $docs = $this->dataDocs();

        return view('livewire.docs-component', [
            'docss' => $docs,
            'docscatOption' => $docscatOption,
        ])->layout('layout.docslayout');

    }
}
