<?php
namespace App\Livewire;
use Carbon\Carbon;
use App\Models\Docs;
use App\Models\Docscat;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Validation\Rule;


class DocsComponent extends Component
{

  use WithPagination;
  use WithFileUploads;
  use LivewireAlert;

  public $iddocs, $title, $content, $urut, $docscat_iddocscat;
  public $docsName;
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


  public function storeDocs()
  {
    $this->validate([
      // 'iddocs' => 'required ',
      'title' => 'required ',
      'content' => 'required|file|mimes:pdf|max:10000',
      'urut' => [
        'required',
        'numeric',
        Rule::unique('docs', 'urut')->where(function ($query) {
          return $query->where('docscat_iddocscat', $this->docscat_iddocscat);
        }),
      ],
      'docscat_iddocscat' => 'required ',
    ]);


    $docs = new Docs();
    $docs->iddocs = $this->iddocs;
    $docs->title = $this->title;
    // $docs->content = $this->content;

    if ($this->content) {
      $namaFile = 'docs' . now()->format('YmdHis') . '.' . $this->content->extension();
      $this->content->storeAs('tutorial-website', $namaFile);
      $docs->content = $namaFile;
    }


    $docs->urut = $this->urut;
    $docs->docscat_iddocscat = $this->docscat_iddocscat;
    $docs->save();
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
    $docs = Docs::where('iddocs', $iddocs)->first();

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

    // Validate title as required
    $this->validate([
      'iddocs' => 'required ',
      'title' => 'required ',
      'urut' => 'required ',
      'docscat_iddocscat' => 'required ',
    ]);

    $docs = Docs::where('iddocs', $this->iddocs)->first();
    $docs->iddocs = $this->iddocs;
    $docs->title = $this->title;


    // Check if content is being updated
    if ($this->content != $docs->content) {

      // Validate the content file if it's being updated
      $this->validate([
        'content' => 'nullable|file|mimes:pdf|max:10000',
      ]);

      // Delete the old content if it exists
      if (!is_null($docs->content)) {
        Storage::delete('tutorial-website/' . $docs->content);
      }

      // Generate new filename and store the updated content
      $namaFile = 'docs_' . now()->format('YmdHis') . '.' . $this->content->extension();
      $this->content->storeAs('tutorial-website', $namaFile);

      // Update content field
      $docs->content = $namaFile;
    }

    $docs->urut = $this->urut;
    $docs->docscat_iddocscat = $this->docscat_iddocscat;

    // Save the updated document
    $docs->save();


    $this->close();

    $this->resetPage('pageLOV');

    $this->alert('success', 'Update data docs has been added successfully');

    $this->dispatch('close-docs-modal');

    $this->js('window.location.reload();');
  }


  public function deleteConfirmationDocs($iddocs)
  {
    $this->iddocs = $iddocs;
    $this->dispatch('show-delete-confirmation-docs-modal');
  }


  public function deleteDocs()
  {
    $docs = Docs::where('iddocs', $this->iddocs)->first();
    try {
      $docs->delete();

      if (!is_null($docs->content)) {
        Storage::delete('tutorial-website/' . $docs->content);
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
    $queryResult = $this->dataDocs();
    $data = [['iddocs', 'title', 'content', 'urut', 'docscat_iddocscat']];
    foreach ($queryResult as $result) {
      $data[] = [$result->iddocs, $result->title, $result->content, $result->urut, $result->docscat_iddocscat];
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($data, null, 'A1');
    $writer = new Xlsx($spreadsheet);
    $filename = 'exported-docs-data.xlsx';
    $writer->save($filename);
    return response()->download($filename)->deleteFileAfterSend(true);
  }


  public function exportToPdf()
  {
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
      $options .= '<option value=' . $row->iddocscat . '';
      if ($id == $row->iddocscat) {
        $options .= ' selected';
      }
      $options .= '>' . $row->docscat . '</option>';
    }
    return $options;
  }


  public function docsOption($id = null)
  {
    $result = Docs::get();
    $options = '';
    foreach ($result as $row) {
      $options .= '<option value=' . $row->iddocs . '';
      if ($id == $row->iddocs) {
        $options .= ' selected';
      }
      $options .= '>' . $row->docs . '</option>';
    }
    return $options;
  }


  public function docsLOV($id)
  {
    $docs = Docs::where('iddocs', $id)->first();
    // $this->nama_relasi = $docs ->iddocs;  
    $this->docsName = $docs->docs;
    $this->alert('success', 'Terpilih : ' . $docs->docs);
    $this->resetPage('pageLOV');

    $this->dispatch('close-modal-lov');
  }


  //untuk mencetak data yang natinya akan di tampilkan di render (memidah dari render agar flexible)   
  public function dataDocs()
  {


    $docs = Docs::
      search($this->search)
      //->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal 
      ->simplePaginate($this->perPage);

    return $docs;

  }


  public function render()
  {

    // aktifkan dan sesuaikan jika menggunakan LOV
    //  $namalov = ModelLOV::search($this->searchlov)->simplePaginate($this->perPage, ['*'], 'pageLOV');  

    $docscatOption = $this->docscatOption();

    //mengambil data render dari function data    
    $docs = $this->dataDocs();

    return view('livewire.docs-component', [
      'docss' => $docs,
      'docscatOption' => $docscatOption
    ])->layout('layout.docslayout');
    ;
  }
}
