<?php

namespace App\Livewire;

use App\Models\FinalisasiRapor;
use App\Models\Kelas;
use App\Models\KktpMapel;
use App\Models\NilaiPerubahanLog;
use App\Models\NilaiSiswa;
use App\Models\PenugasanMengajar;
use App\Services\KonteksAkademikService;
use App\Services\KoreksiNilaiService;
use DomainException;
use Livewire\Component;

/**
 * Koreksi nilai rapor yang sudah difinalisasi.
 *
 * Hanya superadmin. Nilai final tidak diubah lewat input biasa; jalur ini
 * mencatat nilai lama/baru, alasan, dan pelaku ke nilai_perubahan_log serta
 * memperbarui snapshot nilai akhir/KKTP pada transaksi yang sama.
 */
class KoreksiNilaiComponent extends Component
{
    public $kelasId = '';

    public $mapelId = '';

    public $targetNilaiId = '';

    public $nilaiBaru = '';

    public $alasan = '';

    public $targetKktpId = '';

    public $nilaiKktpBaru = '';

    public $alasanKktp = '';

    public $galat = '';

    public $sukses = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }

    public function updatedKelasId(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->mapelId = '';
        $this->targetNilaiId = '';
        $this->nilaiBaru = '';
        $this->alasan = '';
        $this->galat = '';
        $this->sukses = '';
        $this->dispatch('close-koreksi-nilai-modal');
    }

    public function updatedMapelId(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->targetNilaiId = '';
        $this->nilaiBaru = '';
        $this->alasan = '';
        $this->galat = '';
        $this->sukses = '';
        $this->dispatch('close-koreksi-nilai-modal');
    }

    public function bukaKoreksiKomponen(int $idNilaiSiswa): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->targetNilaiId = (string) $idNilaiSiswa;
        $this->nilaiBaru = '';
        $this->alasan = '';
        $this->dispatch('show-koreksi-nilai-modal');
    }

    public function koreksiKomponen(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->galat = '';
        $this->sukses = '';

        try {
            $nilai = KoreksiNilaiService::koreksiKomponen(
                auth()->user(),
                (int) $this->targetNilaiId,
                $this->nilaiBaru,
                $this->alasan
            );

            $this->sukses = 'Koreksi nilai komponen berhasil. Nilai akhir terkait dihitung ulang.';
            $this->targetNilaiId = '';
            $this->nilaiBaru = '';
            $this->alasan = '';
            $this->dispatch('close-koreksi-nilai-modal');
        } catch (DomainException $exception) {
            $this->galat = $exception->getMessage();
        }
    }

    public function bukaKoreksiKktp(int $idKktpMapel): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->targetKktpId = (string) $idKktpMapel;
        $this->nilaiKktpBaru = '';
        $this->alasanKktp = '';
        $this->dispatch('show-koreksi-kktp-modal');
    }

    public function koreksiKktp(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->galat = '';
        $this->sukses = '';

        try {
            $jumlah = KoreksiNilaiService::koreksiKKTP(
                auth()->user(),
                (int) $this->targetKktpId,
                $this->nilaiKktpBaru,
                $this->alasanKktp
            );

            $this->sukses = 'KKTP berhasil dikoreksi. Snapshot KKTP pada '.$jumlah.' nilai akhir diperbarui.';
            $this->targetKktpId = '';
            $this->nilaiKktpBaru = '';
            $this->alasanKktp = '';
            $this->dispatch('close-koreksi-kktp-modal');
        } catch (DomainException $exception) {
            $this->galat = $exception->getMessage();
        }
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $konteks = KonteksAkademikService::ambil();
        $pesanGalat = $this->galat;
        $daftarKelas = collect();
        $mapelOptions = collect();
        $barisNilai = collect();
        $nilaiFinal = null;
        $kktpList = collect();
        $logList = collect();

        if ($konteks->valid() && $konteks->tahunAjaran) {
            $daftarKelas = Kelas::query()
                ->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)
                ->orderBy('tingkat')
                ->orderBy('nama')
                ->get();

            $kktpList = KktpMapel::query()
                ->with('mapel')
                ->where('semester_idsemester', $konteks->semester->idsemester)
                ->orderBy('mapel_idmapel')
                ->get();
        }

        if ($konteks->valid() && $konteks->semester && $this->kelasId !== '' && $this->kelasId !== null) {
            $kelasId = (int) $this->kelasId;

            $mapelOptions = PenugasanMengajar::query()
                ->with('mapel')
                ->where('semester_idsemester', $konteks->semester->idsemester)
                ->where('kelas_idkelas', $kelasId)
                ->orderBy('mapel_idmapel')
                ->get()
                ->pluck('mapel')
                ->filter()
                ->unique('idmapel')
                ->values();

            $nilaiFinal = FinalisasiRapor::query()
                ->with(['waliKelas', 'difinalisasiOleh'])
                ->where('semester_idsemester', $konteks->semester->idsemester)
                ->where('kelas_idkelas', $kelasId)
                ->first();

            if ($this->mapelId !== '' && $this->mapelId !== null) {
                $mapelId = (int) $this->mapelId;

                $barisNilai = NilaiSiswa::query()
                    ->with(['siswa', 'komponenNilai'])
                    ->where('semester_idsemester', $konteks->semester->idsemester)
                    ->where('kelas_idkelas', $kelasId)
                    ->whereHas('komponenNilai', fn ($q) => $q->where('mapel_idmapel', $mapelId))
                    ->orderBy('siswa_idsiswa')
                    ->orderBy('komponen_nilai_idkomponen_nilai')
                    ->get();

                $logList = NilaiPerubahanLog::query()
                    ->with([
                        'nilaiSiswa.siswa',
                        'nilaiSiswa.komponenNilai',
                        'nilaiAkhir.siswa',
                        'diubahOleh',
                    ])
                    ->where(function ($query) use ($kelasId, $mapelId) {
                        $query->whereHas('nilaiSiswa', function ($sub) use ($kelasId, $mapelId) {
                            $sub->where('kelas_idkelas', $kelasId)
                                ->whereHas('komponenNilai', fn ($q) => $q->where('mapel_idmapel', $mapelId));
                        })->orWhereHas('nilaiAkhir', function ($sub) use ($kelasId, $mapelId) {
                            $sub->where('kelas_idkelas', $kelasId)
                                ->where('mapel_idmapel', $mapelId);
                        });
                    })
                    ->latest()
                    ->limit(30)
                    ->get();
            }
        }

        return view('livewire.koreksi-nilai-component', [
            'konteks' => $konteks,
            'pesanGalat' => $pesanGalat,
            'daftarKelas' => $daftarKelas,
            'mapelOptions' => $mapelOptions,
            'barisNilai' => $barisNilai,
            'nilaiFinal' => $nilaiFinal,
            'kktpList' => $kktpList,
            'logList' => $logList,
        ]);
    }
}
