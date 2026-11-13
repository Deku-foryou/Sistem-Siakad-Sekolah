<?php

namespace App\Livewire;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AppmenunewComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $idappmenunew;

    public $appmenunew;

    public $url;

    public $created_by;

    public $updated_by;

    public $urut;

    public $appmenucat_idappmenucat;

    public $icon;

    public $appmenuName;

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

    // icons -------------------------
    public $searchIcons = '';

    public function updatingSearchIcons()
    {
        $this->resetPage('pageIcons');
    }
    // icons -------------------------

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        $this->resetPage();
    }

    public function storeAppmenu()
    {
        $this->validate([
            //  'idappmenu' => 'required ',
            'appmenunew' => 'required ',
            'url' => 'required',
            'urut' => [
                'required',
                'numeric',
                Rule::unique('appmenunew', 'urut')->where(function ($query) {
                    return $query->where('appmenucat_idappmenucat', $this->appmenucat_idappmenucat);
                }),
            ],
            'appmenucat_idappmenucat' => 'required',
            //      'created_by' => 'required ',
            //      'updated_by' => 'required ',
        ]);

        $appmenu = new Appmenunew;
        // $appmenu->idappmenu=$this->idappmenu;
        $appmenu->appmenunew = $this->appmenunew;
        $appmenu->url = $this->url;
        $appmenu->urut = $this->urut;
        $appmenu->icon = $this->icon;
        $appmenu->appmenucat_idappmenucat = $this->appmenucat_idappmenucat;
        $appmenu->created_by = $this->created_by;
        $appmenu->updated_by = $this->updated_by;
        $appmenu->save();
        $this->reset();

        $this->alert('success', 'New appmenu has been added successfully');

        $this->dispatch('close-appmenu-modal');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatch('close-appmenu-modal');
    }

    public function editAppmenu($idappmenunew)
    {
        $appmenu = Appmenunew::where('idappmenunew', $idappmenunew)->first();

        $this->idappmenunew = $appmenu->idappmenunew;
        $this->appmenunew = $appmenu->appmenunew;
        $this->url = $appmenu->url;
        $this->icon = $appmenu->icon;
        $this->urut = $appmenu->urut;
        $this->appmenucat_idappmenucat = $appmenu->appmenucat_idappmenucat;
        $this->created_by = $appmenu->created_by;
        $this->updated_by = $appmenu->updated_by;
        // $this->appmenuName = $appmenu ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-appmenu-modal');
    }

    public function updateAppmenu()
    {
        $this->validate([
            // 'idappmenu' => 'required ',
            'appmenunew' => 'required ',
            'url' => 'required ',
            'appmenucat_idappmenucat' => 'required',

            //      'created_by' => 'required ',
            //      'updated_by' => 'required ',
        ]);
        $appmenu = Appmenunew::where('idappmenunew', $this->idappmenunew)->first();
        $appmenu->idappmenunew = $this->idappmenunew;
        $appmenu->appmenunew = $this->appmenunew;
        $appmenu->url = $this->url;
        $appmenu->icon = $this->icon;
        $appmenu->urut = $this->urut;
        $appmenu->appmenucat_idappmenucat = $this->appmenucat_idappmenucat;
        $appmenu->created_by = $this->created_by;
        $appmenu->updated_by = $this->updated_by;
        $appmenu->save();

        $this->reset();

        $this->alert('success', 'Update data appmenu has been added successfully');

        $this->dispatch('close-appmenu-modal');
    }

    public function deleteConfirmationAppmenu($idappmenunew)
    {
        $this->idappmenunew = $idappmenunew;
        $this->dispatch('show-delete-confirmation-appmenu-modal');
    }

    public function deleteAppmenu()
    {
        $appmenu = Appmenunew::where('idappmenunew', $this->idappmenunew)->first();

        try {
            $appmenu->delete();
            $this->alert('success', 'Data appmenu Berhasil Di hapus');
        } catch (\Throwable $th) {
            // throw $th;
            $this->alert('error', 'Ada data Yang terkait , Data Tidak dapat Dihapus');

        }

        $this->dispatch('close-appmenu-modal');
        $this->idappmenunew = '';
    }

    public function exportToExcel()
    {
        $queryResult = Appmenunew::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [['idappmenunew', 'appmenunew', 'url', 'created_by', 'updated_by']];
        foreach ($queryResult as $result) {
            $data[] = [$result->idappmenunew, $result->appmenunew, $result->url, $result->created_by, $result->updated_by];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $filename = 'exported-appmenu-data.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportToPdf()
    {
        $headers = ['idappmenu', 'appmenu', 'url', 'created_by', 'updated_by'];
        $queryResult = Appmenunew::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [];
        foreach ($queryResult as $result) {
            $data[] = [$result->idappmenunew, $result->appmenunew, $result->url, $result->created_by, $result->updated_by];
        }
        $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers'));
        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Appmenu.pdf');
    }

    public function appmenuOption($id = null)
    {
        $result = Appmenunew::get();
        $options = '';
        foreach ($result as $row) {
            $options .= '<option value="'.(int) $row->idappmenunew.'"';
            if ($id == $row->idappmenunew) {
                $options .= ' selected';
            }
            $options .= '>'.e($row->appmenunew).'</option>';
        }

        return $options;
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

    public function selectIcon($iconName)
    {
        $this->icon = $iconName;
        $this->alert('success', 'Terpilih : '.$iconName);
        $this->resetPage('pageIcons');
        $this->dispatch('close-modal-lov');

    }

    public function render()
    {

        $appmenucatOption = $this->appmenucatOption();

        // Icon LOV -------------------------------------------------------------------------
        // Daftar nama icon di-cache; key memasukkan mtime direktori sumber sehingga perubahan
        // (file ditambah/dihapus) otomatis menghasilkan key baru â€” icon baru pasti muncul.
        // Cache hanya menampung array<string> nama file (bukan state pengguna), TTL 30 menit.
        $iconDir = public_path('asset_offline/icon');
        $iconDirMtime = is_dir($iconDir) ? (string) @filemtime($iconDir) : 'missing';

        $filenames = Cache::remember('appmenunew.icons.'.$iconDirMtime, now()->addMinutes(30), function () use ($iconDir) {
            if (! is_dir($iconDir)) {
                return [];
            }

            return collect(File::files($iconDir))
                ->map(fn ($file) => $file->getFilename())
                ->sort()
                ->values()
                ->all();
        });

        if ($this->searchIcons) {
            $items = collect($filenames)->filter(function ($filename) {
                return stripos($filename, $this->searchIcons) !== false;
            })->values();
        } else {
            $items = collect($filenames);
        }

        $currentPageIcons = Paginator::resolveCurrentPage('pageIcons') ?: 1;
        $slicedItemsIcons = $items->slice(($currentPageIcons - 1) * 84, 84)->all();
        $dataIcons = new LengthAwarePaginator($slicedItemsIcons, count($items), 84, $currentPageIcons, [
            'pageName' => 'pageIcons',
        ]);
        $dataIcons->setPath(Paginator::resolveCurrentPath());

        // ------------------------------------------------------------------------------------

        $appmenu = Appmenunew::latest()
            ->search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->paginate($this->perPage);

        return view('livewire.appmenunew-component', [
            'appmenus' => $appmenu,
            'appmenucatOption' => $appmenucatOption,
            'dataIcons' => $dataIcons,
        ]);
    }
}
