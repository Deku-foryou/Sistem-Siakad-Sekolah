<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AkunService;
use App\Services\PaketMenuService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        $this->resetPage();
    }

    public function storeUsers()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
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
        $users->status_akun = 'aktif';
        $users->password = Hash::make($this->password);
        $users->wajib_ganti_password = true;

        $users->save();
        PaketMenuService::terapkanDefault($users);
        $this->reset();

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
        // $this->usersName = $users ->nama_relasi_yang_di_jadikan_LOV;

        $this->dispatch('show-edit-users-modal');
    }

    public function updateUsers()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->validate([
            'username' => 'required|unique:users,username,'.$this->id,
            'name' => 'required ',
            'level' => 'required ',
        ]);
        $users = User::where('id', $this->id)->first();
        $levelLama = $users->level;
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

        if ($levelLama !== $this->level) {
            PaketMenuService::terapkanDefault($users);
        }

        $this->reset();

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
        abort_unless(auth()->user()?->level === 'superadmin', 403);
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
        $filename = 'exported-users-data.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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

    public function resetPasswordUser($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $user = User::find($id);
        if (! $user) {
            $this->alert('error', 'User tidak ditemukan');

            return;
        }

        if ($user->level === 'guru') {
            $guru = Guru::where('user_iduser', $user->id)->first();
            if (! $guru) {
                $this->alert('error', 'Data guru tidak ditemukan');

                return;
            }
            AkunService::resetPassword($user, Carbon::parse($guru->tanggal_lahir));
        } elseif ($user->level === 'siswa') {
            $siswa = Siswa::where('user_iduser', $user->id)->first();
            if (! $siswa) {
                $this->alert('error', 'Data siswa tidak ditemukan');

                return;
            }
            AkunService::resetPassword($user, Carbon::parse($siswa->tanggal_lahir));
        } else {
            $this->alert('error', 'Reset password hanya untuk guru/siswa');

            return;
        }

        $this->alert('success', 'Password direset ke tanggal lahir (ddmmyyyy)');
    }

    public function render()
    {

        $users = User::latest()
            ->search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->paginate($this->perPage);

        return view('livewire.users-component', [
            'userss' => $users,
        ]);
    }
}
