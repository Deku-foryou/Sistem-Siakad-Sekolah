<?php

namespace App\Livewire;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ActivitylogComponent extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $selectedUser;
    public $selectedAction;

    public $selectedMenu;

    public $tglstart;
    public $tglend;

    public $openRow = null;

    public $selectedBulan;


    public $dataBulan = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];



    public function mount()
    {
        $this->tglstart = date('Y-m-d');
        $this->tglend = date('Y-m-d');
    }


    public function toggle($id)
    {
        if ($this->openRow == $id) {
            $this->openRow = null;
        } else {
            $this->openRow = $id;
        }
    }


    // membuat fitur hapus bedasarkan pilihan bulan (1 bulan sebelumnya, 2 bulan sebelumnya, 3 bulan sebelumnya)

    public function deleteLog($bulan)
    {

        $log = DB::table('activity_log')->whereMonth('created_at', $bulan)->get();
        if ($log) {
            DB::table('activity_log')->whereMonth('created_at', $bulan)->delete();
            $this->alert('success', 'Log berhasil dihapus');
            $this->resetPage();
        }
    }



    public function render()
    {
        // Cache filter dropdowns (hanya load sekali, bukan setiap render)
        $getUser = cache()->remember('activity_log_users', 3600, function () {
            return DB::table('activity_log')
                ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
                ->select('users.name as user_name', 'users.id as user_id')
                ->distinct()
                ->orderBy('users.name')
                ->get();
        });

        $getAction = cache()->remember('activity_log_actions', 3600, function () {
            return DB::table('activity_log')
                ->select('description')
                ->distinct()
                ->orderBy('description')
                ->get();
        });

        $getMenu = cache()->remember('activity_log_menus', 3600, function () {
            return DB::table('activity_log')
                ->select('subject_type')
                ->distinct()
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'name' => class_basename($item->subject_type),
                        'value' => $item->subject_type
                    ];
                });
        });

        // Main query dengan pagination yang lebih reasonable (25 per page)
        $logs = DB::table('activity_log')
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->select('activity_log.*', 'users.name as user_name')
            ->orderBy('activity_log.created_at', 'desc')
            ->when($this->selectedUser, function ($query) {
                $query->where('activity_log.causer_id', $this->selectedUser);
            })
            ->when($this->selectedAction, function ($query) {
                $query->where('activity_log.description', $this->selectedAction);
            })
            ->when($this->tglstart && $this->tglend, function ($query) {
                $query->whereBetween('activity_log.created_at', [$this->tglstart . ' 00:00:00', $this->tglend . ' 23:59:59']);
            })
            ->when($this->selectedMenu, function ($query) {
                $query->where('activity_log.subject_type', $this->selectedMenu);
            })
            ->paginate(25);

        return view(
            'livewire.activitylog-component',
            compact('logs', 'getUser', 'getAction', 'getMenu')
        );
    }
}