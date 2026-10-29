<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesImport;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Tahunajaran;
use App\Services\KelasService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class KelasComponent extends Component
{
    use HandlesImport;
    use LivewireAlert;
    use WithPagination;

    public $idkelas;

    public $tahunajaran_idtahunajaran;

    public $nama;

    public $tingkat = '10';

    public $jenis = 'induk';

    public $guru_idguru;

    public $jurusan_id;

    public $rombel;

    public $filterTahunajaran;

    public $search = '';

    public $perPage = 10;

    public $previewNama = '';

    public function mount()
    {
        $aktif = Tahunajaran::aktif();
        $this->filterTahunajaran = $aktif?->idtahunajaran;
        $this->tahunajaran_idtahunajaran = $aktif?->idtahunajaran;
        $this->updatePreview();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        $this->resetPage();
    }

    public function updatedFilterTahunajaran()
    {
        $this->resetPage();
        $this->updatePreview();
    }

    public function updatedTingkat()
    {
        $this->updatePreview();
    }

    public function updatedJurusanId()
    {
        $this->updatePreview();
    }

    public function updatedRombel()
    {
        $this->updatePreview();
    }

    public function updatePreview()
    {
        $this->previewNama = '';
        if ($this->tingkat && $this->jurusan_id) {
            $tingkatLabels = [
                '10' => 'X',
                '11' => 'XI',
                '12' => 'XII',
            ];
            $tingkatLabel = $tingkatLabels[$this->tingkat] ?? 'X';

            $jurusan = \App\Models\Jurusan::where('idjurusan', $this->jurusan_id)->first();
            $jurusanName = $jurusan ? ' '.$jurusan->nama : '';

            $this->previewNama = $tingkatLabel.$jurusanName.' '.$this->rombel;
        }
    }

    public function storeKelas()
    {
        $this->validate([
            'tahunajaran_idtahunajaran' => 'required',
            'tingkat' => 'required|in:10,11,12',
            'jenis' => 'required|in:induk,mapel_pilihan',
            'jurusan_id' => 'nullable|exists:jurusan,idjurusan',
            'rombel' => 'required|integer|min:1',
        ]);

        $data = [
            'tahunajaran_idtahunajaran' => $this->tahunajaran_idtahunajaran,
            'tingkat' => $this->tingkat,
            'jenis' => $this->jenis,
            'jurusan_id' => $this->jurusan_id,
            'rombel' => $this->rombel,
        ];

        $result = KelasService::buat($data);

        if (! $result['success']) {
            $this->alert('error', 'Gagal menambah kelas: '.implode(', ', array_values($result['errors'] ?? [])));

            return;
        }

        $this->nama = $result['kelas']->nama ?? '';
        $this->rombel = $result['kelas']->rombel ?? $this->rombel;

        $this->reset(['idkelas', 'tahunajaran_idtahunajaran', 'nama', 'tingkat', 'jenis', 'guru_idguru', 'jurusan_id', 'rombel', 'previewNama']);
        $this->alert('success', 'Kelas berhasil ditambahkan');
        $this->dispatch('close-kelas-modal');
    }

    public function close()
    {
        $this->reset(['idkelas', 'nama', 'guru_idguru', 'jurusan_id', 'rombel', 'previewNama']);
        $this->resetValidation();
        $this->dispatch('close-kelas-modal');
    }

    public function editKelas($id)
    {
        $kelas = Kelas::where('idkelas', $id)->first();
        $this->idkelas = $kelas->idkelas;
        $this->tahunajaran_idtahunajaran = $kelas->tahunajaran_idtahunajaran;
        $this->nama = $kelas->nama;
        $this->tingkat = $kelas->tingkat;
        $this->jenis = $kelas->jenis;
        $this->guru_idguru = $kelas->guru_idguru;
        $this->jurusan_id = $kelas->jurusan_id;
        $this->rombel = $kelas->rombel ?? 1;
        $this->dispatch('show-edit-kelas-modal');
    }

    public function updateKelas()
    {
        $this->validate([
            'tahunajaran_idtahunajaran' => 'required',
            'tingkat' => 'required|in:10,11,12',
            'jenis' => 'required|in:induk,mapel_pilihan',
            'jurusan_id' => 'nullable|exists:jurusan,idjurusan',
            'rombel' => 'required|integer|min:1',
        ]);

        if ($this->jenis !== 'induk') {
            $this->guru_idguru = null;
        }

        if ($this->guru_idguru && $this->jenis !== 'induk') {
            $this->alert('error', 'Wali hanya untuk kelas Induk');

            return;
        }

        if ($this->guru_idguru) {
            $exists = Kelas::where('tahunajaran_idtahunajaran', $this->tahunajaran_idtahunajaran)
                ->where('jenis', 'induk')
                ->where('guru_idguru', $this->guru_idguru)
                ->where('idkelas', '!=', $this->idkelas)
                ->exists();
            if ($exists) {
                $this->alert('error', 'Guru sudah menjadi Wali kelas lain di Tahun Ajaran ini');

                return;
            }
        }

        $kelas = Kelas::where('idkelas', $this->idkelas)->first();
        $kelas->tahunajaran_idtahunajaran = $this->tahunajaran_idtahunajaran;
        $kelas->nama = $this->nama;
        $kelas->tingkat = $this->tingkat;
        $kelas->jenis = $this->jenis;
        $kelas->guru_idguru = $this->guru_idguru;
        $kelas->jurusan_id = $this->jurusan_id;
        $kelas->rombel = $this->rombel;
        $kelas->save();

        $this->reset(['idkelas', 'nama', 'guru_idguru', 'jurusan_id', 'rombel', 'previewNama']);
        $this->alert('success', 'Kelas berhasil diperbarui');
        $this->dispatch('close-kelas-modal');
    }

    public function deleteConfirmationKelas($id)
    {
        $this->idkelas = $id;
        $this->dispatch('show-delete-confirmation-kelas-modal');
    }

    public function deleteKelas()
    {
        $kelas = Kelas::where('idkelas', $this->idkelas)->first();
        if ($kelas->kelassiswa()->count() > 0) {
            $this->alert('error', 'Kelas memiliki anggota, tidak dapat dihapus');
            $this->dispatch('close-kelas-modal');
            $this->idkelas = '';

            return;
        }
        try {
            $kelas->delete();
            $this->alert('success', 'Kelas berhasil dihapus');
        } catch (\Throwable $th) {
            $this->alert('error', 'Data tidak dapat dihapus');
        }
        $this->dispatch('close-kelas-modal');
        $this->idkelas = '';
    }

    public function render()
    {
        $kelas = Kelas::with(['tahunajaran', 'wali'])
            ->when($this->filterTahunajaran, function ($q) {
                $q->where('tahunajaran_idtahunajaran', $this->filterTahunajaran);
            })
            ->latest()
            ->search($this->search)
            ->paginate($this->perPage);

        $tahunajarans = Tahunajaran::orderBy('nama')->get();
        $gurus = Guru::where('status', 'aktif')->orderBy('nama')->get();
        $jurusan = Jurusan::where('aktif', true)->orderBy('nama')->get();

        return view('livewire.kelas-component', [
            'kelases' => $kelas,
            'tahunajarans' => $tahunajarans,
            'gurus' => $gurus,
            'jurusan' => $jurusan,
        ]);
    }
}
