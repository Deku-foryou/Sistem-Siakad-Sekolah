<?php

namespace App\Livewire;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UsersComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $id;

    public $username;

    public $name;

    public $email;

    public $level;

    public $password;

    public $usersName;

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

    public function storeUsers()
    {
        $this->validate([
            'username' => 'required|unique:users,username',
            'name' => 'required ',
            'level' => 'required ',
            'password' => 'required|min:8',
        ]);

        $users = new User;
        $users->username = $this->username;
        $users->name = $this->name;
        $users->email = $this->email;
        $users->level = $this->level;
        $users->password = Hash::make($this->password);
        $users->wajib_ganti_password = true;

        $users->save();
        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'New users has been added successfully');

        $this->dispatch('close-users-modal');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatch('close-users-modal');
    }

    public function editUsers($id)
    {
        $users = User::where('id', $id)->first();

        $this->id = $users->id;
        $this->username = $users->username;
        $this->name = $users->name;
        $this->email = $users->email;
        $this->level = $users->level;
        $this->password = '*****';
        // untuk edit jika menggunakan LOV  isi  tabelName LOV
        // $this->usersName = $users ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-users-modal');
    }

    public function updateUsers()
    {
        $this->validate([
            'username' => 'required|unique:users,username,'.$this->id,
            'name' => 'required ',
            'level' => 'required ',
        ]);
        $users = User::where('id', $this->id)->first();
        $users->username = $this->username;
        $users->name = $this->name;
        $users->email = $this->email;
        $users->level = $this->level;

        if ($this->password != '*****' && $this->password != '') {
            if (strlen($this->password) < 8) {
                $this->alert('error', 'Password minimal 8 karakter');

                return;
            }
            $users->password = Hash::make($this->password);
            $users->wajib_ganti_password = true;
        }

        $users->save();

        $this->reset();

        $this->resetPage('pageLOV');

        $this->alert('success', 'Update data users has been added successfully');

        $this->dispatch('close-users-modal');
    }

    public function deleteConfirmationUsers($id)
    {
        $this->id = $id;
        $this->dispatch('show-delete-confirmation-users-modal');
    }

    public function deleteUsers()
    {
        $users = User::where('id', $this->id)->first();

        try {
            $users->delete();
            $this->alert('success', 'Data users Berhasil Di hapus');
        } catch (\Throwable $th) {
            // throw $th;
            $this->alert('error', 'Ada data yang terkait, Data tidak dapat Dihapus');

        }

        $this->dispatch('close-users-modal');
        $this->id = '';
    }

    public function exportToExcel()
    {
        $queryResult = User::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [['id', 'username', 'name', 'email', 'level']];
        foreach ($queryResult as $result) {
            $data[] = [$result->id, $result->username, $result->name, $result->email, $result->level];
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $writer = new Xlsx($spreadsheet);
        $filename = 'exported-users-data.xlsx';
        $writer->save($filename);

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function exportToPdf()
    {
        $headers = ['id', 'username', 'name', 'email', 'level'];
        $queryResult = User::latest()
            ->search($this->search)
            ->paginate($this->perPage);
        $data = [];
        foreach ($queryResult as $result) {
            $data[] = [$result->id, $result->username, $result->name, $result->email, $result->level];
        }
        $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers'));
        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Users.pdf');
    }

    public function usersOption($id = null)
    {
        $result = User::get();
        $options = '';
        foreach ($result as $row) {
            $options .= '<option value='.$row->id.'';
            if ($id == $row->id) {
                $options .= ' selected';
            }
            $options .= '>'.$row->users.'</option>';
        }

        return $options;
    }

    public function usersLOV($id)
    {
        $users = User::where('id', $id)->first();
        // $this->nama_relasi = $users ->id;
        $this->usersName = $users->users;
        $this->alert('success', 'Terpilih : '.$users->users);
        $this->resetPage('pageLOV');

        $this->dispatch('close-modal-lov');
    }

    public function render()
    {

        // aktifkan dan sesuaikan jika menggunakan LOV
        //  $namalov = ModelLOV::search($this->searchlov)->paginate($this->perPage, ['*'], 'pageLOV');

        $users = User::latest()
            ->search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->paginate($this->perPage);

        return view('livewire.users-component', [
            'userss' => $users,
        ]);
    }
}
