<?php

namespace App\Livewire;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Models\User;
use App\Models\Usermenu;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class UsermenuComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $menu_id = [];

    public $rolecat_id = [];

    public $idusermenu;

    public $appmenunew_idappmenunew;

    public $user_iduser;

    public $usermenuName;

    public $id;

    public $searchlov = '';

    public $search = '';

    public $tglstart = '';

    public $tglend = '';

    public $perPage = 10;

    protected function saveValueCek()
    {
        $this->menu_id = Usermenu::where('user_iduser', $this->id)
            ->pluck('appmenunew_idappmenunew')
            ->all();

        return $this->menu_id;
    }

    public function mount($id)
    {
        $this->id = $id;
        $this->menu_id = $this->saveValueCek();
    }

    public function tambahMenuUser()
    {
        if ($this->rolecat_id) {
            // Ambil semua appmenunew_idappmenunew yang sesuai dengan rolecat_id yang diberikan
            $roleMenus = Role::whereIn('rolecat_idrolecat', $this->rolecat_id)->pluck('appmenunew_idappmenunew')->toArray();

            if (empty($roleMenus)) {
                $this->alert('error', 'Menu tidak ditemukan!');

                return;
            }

            // Tambahkan semua Usermenu yang sesuai dengan user_iduser dan appmenunew_idappmenunew dari roleMenus
            foreach ($roleMenus as $appmenuId) {
                Usermenu::updateOrCreate(
                    [
                        'user_iduser' => $this->id,
                        'appmenunew_idappmenunew' => $appmenuId,
                    ],
                    [
                        'user_iduser' => $this->id,
                        'appmenunew_idappmenunew' => $appmenuId,
                    ]
                );
            }

            $this->alert('success', 'Paket Menu berhasil ditambahkan pada pengguna!');
            $this->js('window.location.reload();');
        }

        // Ini Satuan
        $appmenus = Appmenunew::whereIn('idappmenunew', $this->menu_id)->get();

        if ($appmenus->isEmpty()) {
            $this->alert('error', 'Menu tidak ditemukan!');

            return;
        }

        foreach ($appmenus as $appmenu) {
            Usermenu::updateOrCreate(
                [
                    'user_iduser' => $this->id,
                    'appmenunew_idappmenunew' => $appmenu->idappmenunew,
                ],
                [
                    'user_iduser' => $this->id,
                    'appmenunew_idappmenunew' => $appmenu->idappmenunew,
                ]
            );
        }

        $this->alert('success', 'Menu berhasil ditambahkan pada pengguna!');

        $this->js('window.location.reload();');
    }

    public function hapusPaket()
    {
        $this->dispatch('show-delete-confirmation-hapuspaket-modal');
    }

    public function deletePaketRole()
    {
        // Ambil semua appmenunew_idappmenunew yang sesuai dengan rolecat_id yang diberikan
        $roleMenus = Role::whereIn('rolecat_idrolecat', $this->rolecat_id)->pluck('appmenunew_idappmenunew')->toArray();

        if (empty($roleMenus)) {
            $this->alert('error', 'Menu tidak ditemukan!');

            return;
        }

        // Hapus semua Usermenu yang sesuai dengan user_iduser dan appmenunew_idappmenunew dari roleMenus
        Usermenu::where('user_iduser', $this->id)
            ->whereIn('appmenunew_idappmenunew', $roleMenus)
            ->delete();

        $this->alert('success', 'Paket Menu berhasil dihapus');
        $this->dispatch('close-usermenus-modal');
        $this->js('window.location.reload();');
    }

    public function deleteConfirmationUsermenu($idusermenu)
    {
        $this->idusermenu = $idusermenu;
        $this->dispatch('show-delete-confirmation-usermenus-modal');
    }

    public function deleteUsermenu()
    {
        $usermenu = Usermenu::where('idusermenu', $this->idusermenu)->first();

        try {
            $usermenu->delete();
            $this->alert('success', 'Data usermenu Berhasil Di hapus');
        } catch (\Throwable $th) {
            // throw $th;
            $this->alert('error', 'Data Tidak Bisa di Hapus');
        }

        $this->dispatch('close-usermenus-modal');
        $this->idusermenu = '';

        $this->js('window.location.reload();');

    }

    public function render()
    {

        // aktifkan dan sesuaikan jika menggunakan LOV
        //  $namalov = ModelLOV::search($this->searchlov)->paginate($this->perPage, ['*'], 'pageLOV');

        $appMenuCat = Appmenucat::get();
        $appMenu = Appmenunew::with(['appmenucat'])->get();

        $usermenu = Usermenu::with(['appmenunew', 'user'])
            ->where('user_iduser', $this->id)
            ->search($this->search)
          // ->rangetanggal($this->tglstart, $this->tglend) //aktifkan kalau pake filter tanggal
            ->paginate($this->perPage);

        // Tambaham Rolecat
        $rolecat = Rolecat::get();
        $role = Role::with(['rolecat', 'appmenunew'])->get();

        // Cek User
        $user = User::find($this->id);

        return view('livewire.usermenu-component', [
            'usermenus' => $usermenu,
            'appmenu' => $appMenu,
            'appMenuCat' => $appMenuCat,
            'rolecat' => $rolecat,
            'role' => $role,
            'user' => $user,
        ]);
    }
}
