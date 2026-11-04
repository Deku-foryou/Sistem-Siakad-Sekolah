<?php

namespace App\Livewire;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Services\KonteksAkademikService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Histori absensi siswa yang sedang login (dua tab).
 *
 * - Read-only: tidak ada method yang mengubah data.
 * - IDOR-proof: siswa_idsiswa selalu diambil dari akun login, tidak pernah dari input.
 * - Hanya menampilkan absensi milik siswa login (harian dari absensi_harian,
 *   per jam dari snapshot jadwal_pertemuan, bukan jadwal legacy).
 * - Filter tanggal/status/ringkasan/pagination terpisah per tab.
 */
class HistoriAbsensiSiswaComponent extends Component
{
    use WithPagination;

    /** Tab aktif: 'harian' (default) atau 'per_jam'. */
    public string $tab = 'harian';

    // Filter tab Harian
    public $tanggalDariHarian = null;

    public $tanggalSampaiHarian = null;

    public $statusHarian = '';

    // Filter tab Per Jam
    public $tanggalDari = null;

    public $tanggalSampai = null;

    public $status = '';

    public $perPage = 15;

    public function mount()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);
    }

    public function gantiTab(string $tab): void
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        if (! in_array($tab, ['harian', 'per_jam'], true)) {
            $tab = 'harian';
        }

        $this->tab = $tab;
    }

    public function updatedTanggalDari()
    {
        $this->guardTanggalDari();
        $this->resetPage('perJam');
    }

    public function updatedTanggalSampai()
    {
        $this->guardTanggalSampai();
        $this->resetPage('perJam');
    }

    public function updatedStatus()
    {
        if ($this->status !== '' && ! in_array($this->status, ['hadir', 'sakit', 'izin', 'alpha'], true)) {
            $this->status = '';
        }
        $this->resetPage('perJam');
    }

    public function updatedTanggalDariHarian()
    {
        $this->guardTanggalDariHarian();
        $this->resetPage('harian');
    }

    public function updatedTanggalSampaiHarian()
    {
        $this->guardTanggalSampaiHarian();
        $this->resetPage('harian');
    }

    public function updatedStatusHarian()
    {
        if ($this->statusHarian !== '' && ! in_array($this->statusHarian, ['hadir', 'sakit', 'izin', 'alpha'], true)) {
            $this->statusHarian = '';
        }
        $this->resetPage('harian');
    }

    public function resetFilter()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $this->reset(['tanggalDari', 'tanggalSampai', 'status']);
        $this->resetPage('perJam');
    }

    public function resetFilterHarian()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $this->reset(['tanggalDariHarian', 'tanggalSampaiHarian', 'statusHarian']);
        $this->resetPage('harian');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $user = Auth::user();
        $siswa = $user->siswa()->first();
        $konteks = KonteksAkademikService::ambil();

        $absensiHarian = null;
        $ringkasanHarian = ['total' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
        $absensiPerJam = null;
        $ringkasanPerJam = ['total' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];

        if ($siswa) {
            $this->guardTanggalDariHarian();
            $this->guardTanggalSampaiHarian();
            $this->guardTanggalDari();
            $this->guardTanggalSampai();

            // ---- Tab Harian (absensi_harian) ----
            $baseHarian = $this->queryHarian($siswa->idsiswa, $konteks);
            $ringkasanHarian = $this->ringkasan($baseHarian, 'absensi_harian.status');
            $absensiHarian = $baseHarian
                ->with(['kelas:idkelas,nama'])
                ->orderByDesc('absensi_harian.tanggal')
                ->orderByDesc('absensi_harian.idabsensi_harian')
                ->paginate($this->perPage, ['*'], 'harian');

            // ---- Tab Per Jam (absensi_perjam + snapshot jadwal_pertemuan) ----
            $basePerJam = $this->queryPerJam($siswa->idsiswa);
            $ringkasanPerJam = $this->ringkasan($basePerJam, 'absensi_perjam.status');
            $absensiPerJam = $basePerJam
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
                ->paginate($this->perPage, ['*'], 'perJam');
        }

        return view('livewire.histori-absensi-siswa-component', [
            'user' => $user,
            'siswa' => $siswa,
            'tab' => $this->tab,
            'absensiHarian' => $absensiHarian,
            'ringkasanHarian' => $ringkasanHarian,
            'absensiPerJam' => $absensiPerJam,
            'ringkasanPerJam' => $ringkasanPerJam,
            'namaHari' => \App\Support\Hari::NAMA,
            'tanggalDariHarian' => $this->tanggalDariHarian,
            'tanggalSampaiHarian' => $this->tanggalSampaiHarian,
            'statusHarian' => $this->statusHarian,
            'tanggalDari' => $this->tanggalDari,
            'tanggalSampai' => $this->tanggalSampai,
            'status' => $this->status,
            'perPage' => $this->perPage,
        ]);
    }

    /**
     * Query dasar histori harian: hanya milik siswa login, semester & tahun ajaran aktif.
     */
    private function queryHarian(int $siswaId, $konteks)
    {
        $query = AbsensiHarian::query()
            ->forSiswa($siswaId)
            ->with(['kelas:idkelas,nama']);

        if ($konteks->valid() && $konteks->semester) {
            $query->forSemester($konteks->semester->idsemester);
        }

        if ($konteks->valid() && $konteks->tahunAjaran) {
            $query->forTahunajaran($konteks->tahunAjaran->idtahunajaran);
        }

        if ($this->statusHarian !== '') {
            $query->where('absensi_harian.status', $this->statusHarian);
        }

        if ($this->tanggalDariHarian !== null && $this->tanggalDariHarian !== '') {
            $query->where('absensi_harian.tanggal', '>=', $this->tanggalDariHarian);
        }

        if ($this->tanggalSampaiHarian !== null && $this->tanggalSampaiHarian !== '') {
            $query->where('absensi_harian.tanggal', '<=', $this->tanggalSampaiHarian);
        }

        return $query;
    }

    /**
     * Query dasar histori per jam: hanya milik siswa login.
     * Pertahankan hasil dan keamanan query sebelumnya (join snapshot jadwal_pertemuan).
     */
    private function queryPerJam(int $siswaId)
    {
        $query = AbsensiPerjam::query()
            ->join('jadwal_pertemuan as jp', 'jp.idjadwal_pertemuan', '=', 'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan')
            ->join('kelas as k', 'k.idkelas', '=', 'jp.kelas_idkelas')
            ->join('mapel as m', 'm.idmapel', '=', 'jp.mapel_idmapel')
            ->join('guru as g', 'g.idguru', '=', 'jp.guru_idguru')
            ->where('absensi_perjam.siswa_idsiswa', $siswaId);

        if ($this->status !== '') {
            $query->where('absensi_perjam.status', $this->status);
        }

        if ($this->tanggalDari !== null && $this->tanggalDari !== '') {
            $query->where('jp.tanggal', '>=', $this->tanggalDari);
        }

        if ($this->tanggalSampai !== null && $this->tanggalSampai !== '') {
            $query->where('jp.tanggal', '<=', $this->tanggalSampai);
        }

        return $query;
    }

    private function ringkasan($base, string $kolomStatus): array
    {
        $ringkasan = ['total' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];

        $raw = (clone $base)
            ->selectRaw($kolomStatus.', COUNT(*) as jumlah')
            ->groupBy($kolomStatus)
            ->get();

        foreach ($raw as $row) {
            $ringkasan[$row->status] = (int) $row->jumlah;
            $ringkasan['total'] += (int) $row->jumlah;
        }

        return $ringkasan;
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

    private function guardTanggalDariHarian(): void
    {
        if ($this->tanggalDariHarian !== null && $this->tanggalDariHarian !== '' && ! $this->isValidDate($this->tanggalDariHarian)) {
            $this->tanggalDariHarian = null;
        }
    }

    private function guardTanggalSampaiHarian(): void
    {
        if ($this->tanggalSampaiHarian !== null && $this->tanggalSampaiHarian !== '' && ! $this->isValidDate($this->tanggalSampaiHarian)) {
            $this->tanggalSampaiHarian = null;
        }
    }

    private function isValidDate(string $value): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $value);

        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }
}
