<?php

namespace App\Livewire;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Services\KonteksAkademikService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        $rekapHarian = ['total' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
        $rekapPerJam = ['total' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
        if ($siswa) {
            $this->guardTanggalDariHarian();
            $this->guardTanggalSampaiHarian();
            $this->guardTanggalDari();
            $this->guardTanggalSampai();

            // ---- Tab Harian (absensi_harian) ----
            $baseHarian = $this->queryHarian($siswa->idsiswa, $konteks);
            $ringkasanHarian = $this->ringkasan($baseHarian, 'absensi_harian.status');
            $absensiHarian = (clone $baseHarian)
                ->with(['kelas:idkelas,nama'])
                ->orderByDesc('absensi_harian.tanggal')
                ->orderByDesc('absensi_harian.idabsensi_harian')
                ->paginate($this->perPage, ['*'], 'harian');

            // ---- Tab Per Jam (absensi_perjam + snapshot jadwal_pertemuan) ----
            $basePerJam = $this->queryPerJam($siswa->idsiswa);
            $ringkasanPerJam = $this->ringkasan($basePerJam, 'absensi_perjam.status');
            $absensiPerJam = (clone $basePerJam)
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

            // ---- Rekap semester aktif: selalu tanpa filter tanggal/status ----
            $rekapHarian = $this->ringkasan($this->queryHarian($siswa->idsiswa, $konteks, true), 'absensi_harian.status');
            $rekapPerJam = $this->ringkasan($this->queryPerJam($siswa->idsiswa, true), 'absensi_perjam.status');
        }

        $persenHarian = $this->persen((int) $rekapHarian['hadir'], (int) $rekapHarian['total']);
        $persenPerJam = $this->persen((int) $rekapPerJam['hadir'], (int) $rekapPerJam['total']);

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
            'konteks' => $konteks,
            'persenHarian' => $persenHarian,
            'persenPerJam' => $persenPerJam,
            'rekapHarian' => $rekapHarian,
            'rekapPerJam' => $rekapPerJam,
        ]);
    }

    /**
     * Unduh rekap absensi (CSV, dipisah tanda titik koma, UTF-8 BOM).
     * Hanya membaca data — tidak mengubah apa pun.
     */
    public function unduhRekap()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $user = Auth::user();
        $siswa = $user->siswa()->first();
        abort_if(! $siswa, 404);

        $konteks = KonteksAkademikService::ambil();

        // Ekspor selalu mencakup rekap semester & tahun ajaran aktif penuh,
        // tidak terpengaruh filter tanggal/status yang sedang tampil di halaman.
        $baseHarian = AbsensiHarian::query()->forSiswa($siswa->idsiswa);
        if ($konteks->valid() && $konteks->semester) {
            $baseHarian->forSemester($konteks->semester->idsemester);
        }
        if ($konteks->valid() && $konteks->tahunAjaran) {
            $baseHarian->forTahunajaran($konteks->tahunAjaran->idtahunajaran);
        }
        $basePerJam = $this->queryPerJam($siswa->idsiswa, true);

        $ringkasanHarian = $this->ringkasan($baseHarian, 'absensi_harian.status');
        $ringkasanPerJam = $this->ringkasan($basePerJam, 'absensi_perjam.status');
        $daftarHarian = (clone $baseHarian)
            ->with(['kelas:idkelas,nama'])
            ->orderBy('absensi_harian.tanggal')
            ->orderBy('absensi_harian.idabsensi_harian')
            ->get();
        $daftarPerJam = (clone $basePerJam)
            ->select(
                'absensi_perjam.status',
                'absensi_perjam.keterangan',
                'jp.tanggal',
                'jp.jam_ke',
                'jp.waktu_mulai',
                'jp.waktu_selesai',
                'k.nama as kelas_nama',
                'm.nama as mapel_nama',
                'g.nama as guru_nama'
            )
            ->orderBy('jp.tanggal')
            ->orderBy('jp.jam_ke')
            ->get();

        $filename = 'rekap-absensi-'.Str::slug((string) $siswa->nama).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($siswa, $konteks, $daftarHarian, $daftarPerJam, $ringkasanHarian, $ringkasanPerJam) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar dikenali Excel
            $line = function (array $fields) use ($out) {
                // Cegah CSV formula injection (=, +, -, @, tab, CR di awal sel).
                $fields = array_map(function ($value) {
                    $string = (string) $value;

                    return preg_match('/^[=+\-@\t\r]/', $string) === 1 ? "'".$string : $string;
                }, $fields);

                fputcsv($out, $fields, ';');
            };

            $line(['Rekap Absensi Siswa']);
            $line(['Nama', $siswa->nama]);
            $line(['NIS', $siswa->nis ?: '-']);
            $line(['NISN', $siswa->nisn ?: '-']);
            $line(['Tahun Ajaran', $konteks->tahunAjaranNama() ?? '-']);
            $line(['Semester', $konteks->semesterNama() ?? '-']);
            $line([]);

            $line(['REKAP ABSENSI HARIAN (SEMESTER AKTIF)']);
            $line(['Hadir', $ringkasanHarian['hadir']]);
            $line(['Sakit', $ringkasanHarian['sakit']]);
            $line(['Izin', $ringkasanHarian['izin']]);
            $line(['Alpha', $ringkasanHarian['alpha']]);
            $line(['Total', $ringkasanHarian['total']]);
            $line(['Persentase Kehadiran', $this->persen((int) $ringkasanHarian['hadir'], (int) $ringkasanHarian['total'])]);
            $line([]);

            $line(['REKAP ABSENSI PER JAM (RIWAYAT)']);
            $line(['Hadir', $ringkasanPerJam['hadir']]);
            $line(['Sakit', $ringkasanPerJam['sakit']]);
            $line(['Izin', $ringkasanPerJam['izin']]);
            $line(['Alpha', $ringkasanPerJam['alpha']]);
            $line(['Total', $ringkasanPerJam['total']]);
            $line(['Persentase Kehadiran', $this->persen((int) $ringkasanPerJam['hadir'], (int) $ringkasanPerJam['total'])]);
            $line([]);

            $line(['DAFTAR ABSENSI HARIAN']);
            $line(['Tanggal', 'Kelas', 'Status', 'Catatan']);
            foreach ($daftarHarian as $row) {
                $line([
                    $row->tanggal?->format('d/m/Y'),
                    $row->kelas?->nama ?? '-',
                    strtoupper((string) $row->status),
                    $row->catatan ?: '',
                ]);
            }
            $line([]);

            $line(['DAFTAR ABSENSI PER JAM']);
            $line(['Tanggal', 'Jam Ke', 'Kelas', 'Mapel', 'Guru', 'Status', 'Keterangan']);
            foreach ($daftarPerJam as $row) {
                $tanggal = isset($row->tanggal) ? substr((string) $row->tanggal, 0, 10) : '-';
                $line([
                    $tanggal,
                    $row->jam_ke ?? '-',
                    $row->kelas_nama ?? '-',
                    $row->mapel_nama ?? '-',
                    $row->guru_nama ?? '-',
                    strtoupper((string) $row->status),
                    $row->keterangan ?: '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function persen(int $hadir, int $total): string
    {
        $persen = $total > 0 ? round($hadir / $total * 100, 1) : 0.0;

        return number_format($persen, 1, ',', '').'%';
    }

    /**
     * Query dasar histori harian: hanya milik siswa login, semester & tahun ajaran aktif.
     *
     * $abaikanFilter = true untuk rekap semester penuh (tanpa filter tanggal/status UI).
     */
    private function queryHarian(int $siswaId, $konteks, bool $abaikanFilter = false)
    {
        $query = AbsensiHarian::query()->forSiswa($siswaId);

        if ($konteks->valid() && $konteks->semester) {
            $query->forSemester($konteks->semester->idsemester);
        }

        if ($konteks->valid() && $konteks->tahunAjaran) {
            $query->forTahunajaran($konteks->tahunAjaran->idtahunajaran);
        }

        if ($abaikanFilter) {
            return $query;
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
     *
     * $abaikanFilter = true untuk ekspor rekap (selalu semester/periode penuh).
     */
    private function queryPerJam(int $siswaId, bool $abaikanFilter = false)
    {
        $query = AbsensiPerjam::query()
            ->join('jadwal_pertemuan as jp', 'jp.idjadwal_pertemuan', '=', 'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan')
            ->join('kelas as k', 'k.idkelas', '=', 'jp.kelas_idkelas')
            ->join('mapel as m', 'm.idmapel', '=', 'jp.mapel_idmapel')
            ->join('guru as g', 'g.idguru', '=', 'jp.guru_idguru')
            ->where('absensi_perjam.siswa_idsiswa', $siswaId);

        if ($abaikanFilter) {
            return $query;
        }

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
