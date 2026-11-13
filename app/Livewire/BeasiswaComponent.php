<?php

namespace App\Livewire;

use App\Livewire\Concerns\LovSiswa;
use App\Models\Beasiswa;
use App\Models\BeasiswaSiswa;
use App\Models\Siswa;
use App\Services\LovService;
use App\Support\Lov;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class BeasiswaComponent extends Component
{
    use LivewireAlert {
        alert as protected;
    }
    use LovSiswa;

    // siswaId & siswaLabel disediakan trait LovSiswa (mode single)
    public string $siswaId = '';

    public $idbeasiswa;

    public $nama = '';

    public $jenis = 'nominal';

    public $nilai = '';

    public $berlaku_dari = '';

    public $berlaku_sampai = '';

    public $alasan = '';

    public $status = 'aktif';

    public $filterStatus = 'aktif';

    // --- Beasiswa yang sedang dikelola penerimanya (modal "Kelola Penerima") ---
    public ?int $penerimaId = null;

    /** Alasan penetapan (opsional) pada modal Kelola Penerima. */
    public string $alasanPenerima = '';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->berlaku_dari = date('Y-m-d');
    }

    public function boot(): void
    {
        // Livewire 3: dipanggil pada SETIAP request (termasuk update) — otorisasi tidak pernah terlewati.
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $beasiswas = Beasiswa::with('penerima')
            ->when($this->filterStatus, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('idbeasiswa')
            ->get();

        $penerimaBeasiswa = null;
        $penerimaList = collect();
        if ($this->penerimaId) {
            $penerimaBeasiswa = Beasiswa::find($this->penerimaId);
            if ($penerimaBeasiswa) {
                $penerimaList = BeasiswaSiswa::with('siswa')
                    ->where('beasiswa_idbeasiswa', $penerimaBeasiswa->idbeasiswa)
                    ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
                    ->orderByDesc('berlaku_dari')
                    ->orderByDesc('idbeasiswa_siswa')
                    ->get();
            }
        }

        return view('livewire.beasiswa-component', [
            'beasiswas' => $beasiswas,
            'penerimaBeasiswa' => $penerimaBeasiswa,
            'penerimaList' => $penerimaList,
        ]);
    }

    public function store(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $validated = $this->validate([
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:nominal,persen,pembebasan',
            'nilai' => 'required|integer|min:0|max:100000000',
            'berlaku_dari' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'alasan' => 'nullable|string|max:500',
        ]);

        if ($validated['jenis'] === 'persen' && ((int) $validated['nilai'] < 1 || (int) $validated['nilai'] > 100)) {
            $this->addError('nilai', 'Nilai persen harus antara 1 dan 100.');

            return;
        }

        DB::transaction(function () use ($validated) {
            Beasiswa::create([
                'nama' => $validated['nama'],
                'jenis' => $validated['jenis'],
                'nilai' => (int) $validated['nilai'],
                'berlaku_dari' => $validated['berlaku_dari'],
                'berlaku_sampai' => $validated['berlaku_sampai'] ?: null,
                'alasan' => $validated['alasan'] ?? null,
                'status' => 'aktif',
                'dibuat_oleh' => auth()->id(),
            ]);
        });

        $this->resetForm();
        $this->alert('success', 'Beasiswa berhasil ditambahkan.');
    }

    public function edit(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $row = Beasiswa::findOrFail($id);
        $this->idbeasiswa = $row->idbeasiswa;
        $this->nama = $row->nama;
        $this->jenis = $row->jenis;
        $this->nilai = (string) $row->nilai;
        $this->berlaku_dari = $row->berlaku_dari->format('Y-m-d');
        $this->berlaku_sampai = $row->berlaku_sampai ? $row->berlaku_sampai->format('Y-m-d') : '';
        $this->alasan = $row->alasan ?? '';
        $this->dispatch('show-edit-beasiswa-modal');
    }

    public function update(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $validated = $this->validate([
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:nominal,persen,pembebasan',
            'nilai' => 'required|integer|min:0|max:100000000',
            'berlaku_dari' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'alasan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $row = Beasiswa::findOrFail($this->idbeasiswa);
            $row->update([
                'nama' => $validated['nama'],
                'jenis' => $validated['jenis'],
                'nilai' => (int) $validated['nilai'],
                'berlaku_dari' => $validated['berlaku_dari'],
                'berlaku_sampai' => $validated['berlaku_sampai'] ?: null,
                'alasan' => $validated['alasan'] ?? null,
            ]);
        });

        $this->resetForm();
        $this->alert('success', 'Beasiswa berhasil diperbarui.');
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $row = Beasiswa::findOrFail($id);
        $row->status = $row->status === 'aktif' ? 'nonaktif' : 'aktif';
        $row->save();

        $this->alert('success', 'Status beasiswa diperbarui.');
    }

    public function kelolaPenerima(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $beasiswa = Beasiswa::findOrFail($id);
        $this->penerimaId = $beasiswa->idbeasiswa;
        $this->alasanPenerima = '';
        $this->lovReset();
        $this->dispatch('show-penerima-beasiswa-modal');
    }

    public function tutupPenerima(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $this->penerimaId = null;
        $this->alasanPenerima = '';
        $this->lovReset();
        $this->dispatch('close-penerima-beasiswa-modal');
    }

    public function tambahPenerima(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        if (! $this->penerimaId) {
            $this->addError('siswaId', 'Beasiswa belum dipilih.');

            return;
        }

        // Validasi ulang di backend — jangan percaya ID dari browser.
        $siswa = LovService::selected(Lov::KEY_SISWA, $this->siswaId !== '' ? (int) $this->siswaId : null);
        if (! $siswa instanceof Siswa) {
            $this->addError('siswaId', 'Siswa tidak ditemukan atau tidak aktif.');

            return;
        }

        $beasiswa = Beasiswa::findOrFail($this->penerimaId);

        $existing = BeasiswaSiswa::where('beasiswa_idbeasiswa', $beasiswa->idbeasiswa)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->first();

        // Cegah penetapan duplikat (penerima AKTIF pada beasiswa yang sama).
        if ($existing && $existing->status === 'aktif') {
            $this->addError('siswaId', 'Siswa sudah terdaftar sebagai penerima aktif beasiswa ini.');

            return;
        }

        DB::transaction(function () use ($beasiswa, $siswa, $existing) {
            if ($existing) {
                // Pernah dicabut lalu ditetapkan kembali: aktifkan kembali baris yang sama
                // (unique beasiswa+siswa). Baris & kreator tidak dihapus; timestamp diperbarui.
                $existing->update([
                    'status' => 'aktif',
                    'berlaku_dari' => now()->format('Y-m-d'),
                    'berlaku_sampai' => null,
                    'alasan' => $this->alasanPenerima !== '' ? $this->alasanPenerima : $existing->alasan,
                    'dibuat_oleh' => auth()->id(),
                ]);
            } else {
                BeasiswaSiswa::create([
                    'beasiswa_idbeasiswa' => $beasiswa->idbeasiswa,
                    'siswa_idsiswa' => $siswa->idsiswa,
                    'berlaku_dari' => now()->format('Y-m-d'),
                    'berlaku_sampai' => null,
                    'alasan' => $this->alasanPenerima !== '' ? $this->alasanPenerima : null,
                    'status' => 'aktif',
                    'dibuat_oleh' => auth()->id(),
                ]);
            }
        });

        $this->alasanPenerima = '';
        $this->lovReset();
        $this->alert('success', 'Beasiswa berhasil ditetapkan kepada siswa.');
    }

    public function cabutPenerima(int $idbeasiswa_siswa): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $row = BeasiswaSiswa::where('idbeasiswa_siswa', $idbeasiswa_siswa)
            ->where('status', 'aktif')
            ->first();

        if (! $row) {
            $this->alert('warning', 'Siswa tidak terdaftar sebagai penerima aktif beasiswa ini.');

            return;
        }

        // Cegah pencabutan lintas beasiswa yang bukan sedang dikelola.
        if ($this->penerimaId && (int) $row->beasiswa_idbeasiswa !== (int) $this->penerimaId) {
            $this->alert('warning', 'Penerima tidak cocok dengan beasiswa yang sedang dikelola.');

            return;
        }

        DB::transaction(function () use ($row) {
            // Pencabutan hanya mengubah status & tanggal berhenti — baris & histori TIDAK dihapus.
            $row->status = 'berhenti';
            $row->berlaku_sampai = now()->format('Y-m-d');
            $row->save();
        });

        $this->alert('success', 'Beasiswa berhasil dicabut. Histori penetapan tetap tersimpan.');
    }

    public function close(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->resetForm();
        $this->dispatch('close-beasiswa-modal');
    }

    public function resetForm(): void
    {
        $this->idbeasiswa = null;
        $this->nama = '';
        $this->jenis = 'nominal';
        $this->nilai = '';
        $this->berlaku_dari = now()->format('Y-m-d');
        $this->berlaku_sampai = '';
        $this->alasan = '';
        $this->status = 'aktif';
        $this->dispatch('close-beasiswa-modal');
    }
}
