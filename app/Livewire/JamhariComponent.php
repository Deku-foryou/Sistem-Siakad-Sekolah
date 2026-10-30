<?php

namespace App\Livewire;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Jamhari;
use App\Services\JamHariService;
use App\Support\Hari;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class JamhariComponent extends Component
{
    use LivewireAlert;

    public $hari = 1;
    public $filterJurusan, $filterKelas;
    public $idjamhari, $jenis = 'pelajaran', $jam_ke, $mulai, $selesai, $keterangan;
    public $hariTujuan = [];

    protected $rules = [
        'jenis' => 'required|in:pelajaran,istirahat',
        'jam_ke' => 'nullable|integer',
        'mulai' => 'required|date_format:H:i',
        'selesai' => 'required|date_format:H:i',
    ];

    public function updatedFilterJurusan()
    {
        $this->filterKelas = '';
    }

    public function resetForm()
    {
        $this->reset(['idjamhari', 'jenis', 'jam_ke', 'mulai', 'selesai']);
        $this->jenis = 'pelajaran';
        $awal = JamHariService::nilaiAwal($this->hari);
        $this->jam_ke = $awal['jam_ke'];
        $this->mulai = $awal['mulai'];
    }

    public function store()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        if (!$this->filterKelas) {
            $this->alert('error', 'Pilih kelas terlebih dahulu.');
            return;
        }
        $this->validate();
        try {
            JamHariService::buat(array_merge($this->validate(), [
                'hari' => $this->hari,
                'kelas_id' => $this->filterKelas
            ]));
            $this->alert('success', 'Jam berhasil ditambah');
            $this->dispatch('close-jamhari-modal');
            $this->resetForm();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $jam = Jamhari::findOrFail($id);
        $this->idjamhari = $jam->idjamhari;
        $this->jenis = $jam->jenis;
        $this->jam_ke = $jam->jam_ke;
        $this->mulai = substr($jam->mulai, 0, 5);
        $this->selesai = substr($jam->selesai, 0, 5);
        $this->dispatch('show-edit-jamhari-modal');
    }

    public function update()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->validate();
        try {
            JamHariService::ubah($this->idjamhari, $this->validate());
            $this->alert('success', 'Jam berhasil diubah');
            $this->dispatch('close-jamhari-modal');
            $this->resetForm();
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function deleteConfirm($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->idjamhari = $id;
        $this->dispatch('show-delete-jamhari-modal');
    }

    public function delete()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JamHariService::hapus($this->idjamhari);
            $this->alert('success', 'Jam berhasil dihapus');
        } catch (\DomainException $e) {
            $this->alert('error', $e->getMessage());
        }
        $this->dispatch('close-jamhari-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        
        $jurusans = Jurusan::where('aktif', true)->orderBy('nama')->get();
        $kelases = $this->filterJurusan 
            ? Kelas::where('jurusan_id', $this->filterJurusan)->orderBy('nama')->get()
            : collect();
        
        // Asumsi Jamhari terikat kelas
        $jam = $this->filterKelas
            ? Jamhari::hari($this->hari)->where('kelas_id', $this->filterKelas)->urut()->get()
            : collect();
        
        $jumlah = $this->filterKelas
            ? Jamhari::select('hari', DB::raw('count(*) as total'))
                ->where('kelas_id', $this->filterKelas)
                ->groupBy('hari')->pluck('total', 'hari')
            : collect();
            
        return view('livewire.jamhari-component', [
            'jam' => $jam,
            'jumlah' => $jumlah,
            'hariNama' => Hari::NAMA,
            'jurusans' => $jurusans,
            'kelases' => $kelases,
        ]);
    }
}
