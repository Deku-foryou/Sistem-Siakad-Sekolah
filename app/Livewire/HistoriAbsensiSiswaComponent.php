<?php

namespace App\Livewire;

use App\Models\AbsensiPerjam;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Histori absensi per jam untuk siswa yang sedang login.
 *
 * - Read-only: tidak ada method yang mengubah data.
 * - IDOR-proof: siswa_idsiswa selalu diambil dari akun login, tidak pernah dari input.
 * - Hanya menampilkan pertemuan yang memiliki catatan absensi siswa.
 * - Snapshot JadwalPertemuan (kelas/mapel/guru/hari/jam/waktu) dipakai, bukan jadwal legacy.
 */
class HistoriAbsensiSiswaComponent extends Component
{
    use WithPagination;

    public $tanggalDari = null;

    public $tanggalSampai = null;

    public $status = '';

    public $perPage = 15;

    public function mount()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);
    }

    public function updatedTanggalDari()
    {
        $this->guardTanggalDari();
        $this->resetPage();
    }

    public function updatedTanggalSampai()
    {
        $this->guardTanggalSampai();
        $this->resetPage();
    }

    public function updatedStatus()
    {
        if ($this->status !== '' && ! in_array($this->status, ['hadir', 'sakit', 'izin', 'alpha'], true)) {
            $this->status = '';
        }
        $this->resetPage();
    }

    public function resetFilter()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $this->reset(['tanggalDari', 'tanggalSampai', 'status']);
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $user = Auth::user();
        $siswa = $user->siswa()->first();

        $absensi = null;
        $ringkasan = ['total' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];

        if ($siswa) {
            $this->guardTanggalDari();
            $this->guardTanggalSampai();

            $base = $this->query($siswa->idsiswa);

            $ringkasanRaw = (clone $base)
                ->selectRaw('absensi_perjam.status, COUNT(*) as jumlah')
                ->groupBy('absensi_perjam.status')
                ->get();
            foreach ($ringkasanRaw as $row) {
                $ringkasan[$row->status] = (int) $row->jumlah;
                $ringkasan['total'] += (int) $row->jumlah;
            }

            $absensi = $base
                ->select(
                    'absensi_perjam.*',
                    'jp.tanggal',
                    'jp.hari',
                    'jp.jam_ke',
                    'jp.waktu_mulai',
                    'jp.waktu_selesai',
                    'jp.status as pertemuan_status',
                    'k.nama as kelas_nama',
                    'm.nama as mapel_nama',
                    'g.nama as guru_nama'
                )
                ->orderByDesc('jp.tanggal')
                ->orderByDesc('jp.jam_ke')
                ->orderByDesc('jp.waktu_mulai')
                ->paginate($this->perPage);
        }

        return view('livewire.histori-absensi-siswa-component', [
            'user' => $user,
            'siswa' => $siswa,
            'absensi' => $absensi,
            'ringkasan' => $ringkasan,
            'namaHari' => \App\Support\Hari::NAMA,
            'tanggalDari' => $this->tanggalDari,
            'tanggalSampai' => $this->tanggalSampai,
            'status' => $this->status,
            'perPage' => $this->perPage,
        ]);
    }

    /**
     * Query dasar: hanya catatan absensi milik siswa login.
     * Snapshot pertemuan (kelas/mapel/guru/hari/jam/waktu) disimpan di jadwal_pertemuan.
     */
    private function query(int $siswaId)
    {
        $q = AbsensiPerjam::query()
            ->join('jadwal_pertemuan as jp', 'jp.idjadwal_pertemuan', '=', 'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan')
            ->join('kelas as k', 'k.idkelas', '=', 'jp.kelas_idkelas')
            ->join('mapel as m', 'm.idmapel', '=', 'jp.mapel_idmapel')
            ->join('guru as g', 'g.idguru', '=', 'jp.guru_idguru')
            ->where('absensi_perjam.siswa_idsiswa', $siswaId);

        if ($this->status !== '') {
            $q->where('absensi_perjam.status', $this->status);
        }
        if ($this->tanggalDari !== null && $this->tanggalDari !== '') {
            $q->where('jp.tanggal', '>=', $this->tanggalDari);
        }
        if ($this->tanggalSampai !== null && $this->tanggalSampai !== '') {
            $q->where('jp.tanggal', '<=', $this->tanggalSampai);
        }

        return $q;
    }

    private function guardTanggalDari(): void
    {
        if ($this->tanggalDari !== null && $this->tanggalDari !== '' && ! $this->isValidDate($this->tanggalDari)) {
            $this->tanggalDari = null;
        }
    }

    private function guardTanggalSampai(): void
    {
        if ($this->tanggalSampai !== null && $this->tanggalSampai !== '' && ! $this->isValidDate($this->tanggalSampai)) {
            $this->tanggalSampai = null;
        }
    }

    private function isValidDate(string $value): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $value);

        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }
}
