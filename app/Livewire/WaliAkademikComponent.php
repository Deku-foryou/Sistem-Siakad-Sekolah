<?php

namespace App\Livewire;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\CatatanRapor;
use App\Models\FinalisasiRapor;
use App\Models\Siswa;
use App\Services\JadwalSiswaService;
use App\Services\KonteksAkademikService;
use App\Services\WalimuridService;
use App\Support\KonteksAkademik;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Akses akademik read-only untuk wali (2B.8 + 2B.10 + hardening 2B.10).
 * Semua data di-scope ke siswa dari relasi verified milik wali login.
 */
class WaliAkademikComponent extends Component
{
    use WithPagination;

    public $tab = 'nilai'; // nilai | rekap | ketidakhadiran | catatan | perkembangan | absensi_harian | absensi_perjam | jadwal

    public $siswaId = '';

    // Filter tab Ketidakhadiran (opsional)
    public $filterStatus = 'semua'; // semua | sakit | izin | alpha

    public $filterDari = '';

    public $filterSampai = '';

    public function boot(): void
    {
        WalimuridService::pastikanWaliAktif(Auth::user());
    }

    public function mount(): void
    {
        $wali = Auth::user();
        $relasi = \App\Models\Walimurid::query()
            ->where('user_iduser', $wali->id)
            ->where('status_hubungan', 'verified')
            ->whereNull('tanggal_berhenti')
            ->orderBy('idwalimurid') // urutan stabil
            ->first();
        if ($relasi) {
            $this->siswaId = (string) $relasi->siswa_idsiswa;
        }
    }

    public function pilihSiswa(int $siswaId): void
    {
        // validasi akses server-side
        WalimuridService::pastikanAksesAkademikWali(Auth::user(), $siswaId);
        $this->siswaId = (string) $siswaId;
        $this->resetPage('harianPage');
        $this->resetPage('perjamPage');
    }

    public function gantiTab(string $tab): void
    {
        if (! in_array($tab, ['nilai', 'rekap', 'ketidakhadiran', 'catatan', 'perkembangan', 'absensi_harian', 'absensi_perjam', 'jadwal'], true)) {
            $tab = 'nilai';
        }
        $this->tab = $tab;
    }

    public function updatedFilterStatus(): void
    {
        if (! in_array($this->filterStatus, ['semua', 'sakit', 'izin', 'alpha'], true)) {
            $this->filterStatus = 'semua';
        }
        $this->resetPage('harianPage');
        $this->resetPage('perjamPage');
    }

    public function updatedFilterDari(): void
    {
        $this->filterDari = $this->validTanggal($this->filterDari);
        if ($this->filterDari !== '' && $this->filterSampai !== '' && $this->filterDari > $this->filterSampai) {
            [$this->filterDari, $this->filterSampai] = [$this->filterSampai, $this->filterDari];
        }
        $this->resetPage('harianPage');
        $this->resetPage('perjamPage');
    }

    public function updatedFilterSampai(): void
    {
        $this->filterSampai = $this->validTanggal($this->filterSampai);
        if ($this->filterDari !== '' && $this->filterSampai !== '' && $this->filterDari > $this->filterSampai) {
            [$this->filterDari, $this->filterSampai] = [$this->filterSampai, $this->filterDari];
        }
        $this->resetPage('harianPage');
        $this->resetPage('perjamPage');
    }

    /**
     * Unduh CSV rekap kehadiran semester aktif untuk siswa terpilih.
     * Read-only; guard tunggal WalimuridService::pastikanAksesAkademikWali().
     */
    public function unduhRekap()
    {
        $wali = Auth::user();
        WalimuridService::pastikanWaliAktif($wali);

        if ($this->siswaId === '' || ! is_numeric($this->siswaId)) {
            abort(404);
        }
        $siswaId = (int) $this->siswaId;

        // Guard: relasi verified + aktif milik wali login (fail-closed).
        WalimuridService::pastikanAksesAkademikWali($wali, $siswaId);

        $konteks = KonteksAkademikService::ambil();
        $siswa = Siswa::find($siswaId);
        abort_if(! $siswa, 404);

        $rows = WalimuridService::detailRekapKehadiranWali($wali, $siswaId);

        $nama = Str::slug((string) ($siswa->nama ?? '')) ?: 'siswa';
        $semester = Str::slug((string) ($konteks->semesterNama() ?? '')) ?: 'semester';
        $filename = "rekap-kehadiran-{$nama}-{$semester}.csv";
        // Nama file hanya boleh berisi [a-z0-9-] lewat slug; aman untuk header.
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?? 'rekap-kehadiran.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar dikenali Excel

            $hariMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            $line = function (array $fields) use ($out) {
                // Cegah CSV formula injection (=, +, -, @, tab, CR di awal sel).
                $fields = array_map(function ($value) {
                    $string = (string) $value;

                    return preg_match('/^[=+\-@\t\r]/', $string) === 1 ? "'".$string : $string;
                }, $fields);

                fputcsv($out, $fields, ';');
            };

            $line(['No', 'Tanggal', 'Hari', 'Status', 'Keterangan', 'Sumber']);

            $no = 0;
            foreach ($rows as $row) {
                $no++;
                $timestamp = strtotime($row['tanggal']);
                $hari = $timestamp !== false ? ($hariMap[(int) date('w', $timestamp)] ?? '-') : '-';
                $line([
                    $no,
                    $timestamp !== false ? date('d/m/Y', $timestamp) : '-',
                    $hari,
                    strtoupper($row['status']),
                    $row['keterangan'] !== '' ? $row['keterangan'] : '-',
                    $row['sumber'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $wali = Auth::user();
        $konteks = KonteksAkademikService::ambil();
        $relasiList = \App\Models\Walimurid::query()
            ->with('siswa')
            ->where('user_iduser', $wali->id)
            ->where('status_hubungan', 'verified')
            ->whereNull('tanggal_berhenti')
            ->orderBy('idwalimurid') // urutan stabil
            ->get();

        $siswa = null;
        $dataNilai = collect();
        $dataAbsensiHarian = collect();
        $dataAbsensiPerjam = collect();
        $dataRekap = null;
        $dataKetidakhadiranHarian = null;
        $dataKetidakhadiranPerjam = null;
        $dataCatatan = null;
        $dataPerkembangan = [];
        $dataJadwal = collect();
        $konteksValid = $konteks->valid();
        $gagalMuat = false;

        if ($this->siswaId !== '' && $konteksValid) {
            try {
                $relasi = WalimuridService::pastikanAksesAkademikWali($wali, (int) $this->siswaId);
                $siswa = Siswa::find((int) $this->siswaId);
                $siswaId = (int) $this->siswaId;

                // ---- Nilai/rapor final ----
                $kunci = \App\Models\NilaiAkhir::query()
                    ->where('siswa_idsiswa', $siswaId)
                    ->select('semester_idsemester', 'kelas_idkelas')
                    ->distinct()
                    ->get();
                foreach ($kunci as $baris) {
                    $final = FinalisasiRapor::query()
                        ->with(['semester.tahunajaran', 'kelas.wali'])
                        ->where('semester_idsemester', $baris->semester_idsemester)
                        ->where('kelas_idkelas', $baris->kelas_idkelas)
                        ->where('status', FinalisasiRapor::STATUS_FINAL)
                        ->first();
                    if (! $final) {
                        continue;
                    }
                    $nilai = \App\Models\NilaiAkhir::query()
                        ->with(['mapel'])
                        ->where('semester_idsemester', $baris->semester_idsemester)
                        ->where('kelas_idkelas', $baris->kelas_idkelas)
                        ->where('siswa_idsiswa', $siswaId)
                        ->orderBy('mapel_idmapel')
                        ->get();
                    $dataNilai->push([
                        'semester' => $final->semester,
                        'kelas' => $final->kelas,
                        'finalisasi' => $final,
                        'nilai' => $nilai,
                    ]);
                }

                // ---- Absensi harian (read-only, milik siswa), dibatasi periode relasi ----
                $qHarian = $this->queryHarian($siswaId, $konteks, $relasi);
                $dataAbsensiHarian = $qHarian->orderByDesc('tanggal')->limit(50)->get();

                // ---- Absensi per jam ----
                $dataAbsensiPerjam = AbsensiPerjam::query()
                    ->where('siswa_idsiswa', $siswaId)
                    ->orderByDesc('idabsensi_perjam')
                    ->limit(50)
                    ->get();

                // ---- Kelas aktif siswa (TA aktif, kelassiswa status=aktif) ----
                $jadwalSiswa = app(JadwalSiswaService::class);
                $kelasAktif = $jadwalSiswa->kelasAktif($siswaId, $konteks);
                $kelasIds = $kelasAktif->pluck('idkelas')->all();

                // ---- Jadwal: reuse JadwalSiswaService ----
                $jadwal = $jadwalSiswa->jadwal($konteks, $kelasIds);
                $dataJadwal = $jadwalSiswa->perHari($jadwal);

                // ---- Tab A: Rekap Kehadiran (semester aktif, kelas aktif) ----
                $dataRekap = WalimuridService::rekapKehadiranWali($wali, $siswaId);

                // ---- Tab B: Ketidakhadiran (status selain hadir, filter opsional) ----
                $ketidakhadiranBase = AbsensiHarian::query()
                    ->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)
                    ->where('semester_idsemester', $konteks->semester->idsemester)
                    ->where('siswa_idsiswa', $siswaId)
                    ->where('status', '!=', 'hadir');
                if ($relasi->tanggal_mulai) {
                    $ketidakhadiranBase->whereDate('tanggal', '>=', $relasi->tanggal_mulai);
                }
                if ($relasi->tanggal_berhenti) {
                    $ketidakhadiranBase->whereDate('tanggal', '<=', $relasi->tanggal_berhenti);
                }
                if ($this->filterStatus !== 'semua') {
                    $ketidakhadiranBase->where('status', $this->filterStatus);
                }
                if ($this->filterDari !== '') {
                    $ketidakhadiranBase->whereDate('tanggal', '>=', $this->filterDari);
                }
                if ($this->filterSampai !== '') {
                    $ketidakhadiranBase->whereDate('tanggal', '<=', $this->filterSampai);
                }
                $dataKetidakhadiranHarian = $ketidakhadiranBase
                    ->with('kelas:idkelas,nama')
                    ->orderByDesc('tanggal')
                    ->paginate(10, ['*'], 'harianPage');

                $ketidakhadiranPerjamBase = AbsensiPerjam::query()
                    ->join('jadwal_pertemuan as jp', 'jp.idjadwal_pertemuan', '=', 'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan')
                    ->join('mapel as m', 'm.idmapel', '=', 'jp.mapel_idmapel')
                    ->join('kelas as k', 'k.idkelas', '=', 'jp.kelas_idkelas')
                    ->where('jp.semester_idsemester', $konteks->semester->idsemester)
                    ->where('absensi_perjam.siswa_idsiswa', $siswaId)
                    ->where('absensi_perjam.status', '!=', 'hadir');
                if ($relasi->tanggal_mulai) {
                    $ketidakhadiranPerjamBase->whereDate('jp.tanggal', '>=', $relasi->tanggal_mulai);
                }
                if ($relasi->tanggal_berhenti) {
                    $ketidakhadiranPerjamBase->whereDate('jp.tanggal', '<=', $relasi->tanggal_berhenti);
                }
                if ($this->filterStatus !== 'semua') {
                    $ketidakhadiranPerjamBase->where('absensi_perjam.status', $this->filterStatus);
                }
                if ($this->filterDari !== '') {
                    $ketidakhadiranPerjamBase->whereDate('jp.tanggal', '>=', $this->filterDari);
                }
                if ($this->filterSampai !== '') {
                    $ketidakhadiranPerjamBase->whereDate('jp.tanggal', '<=', $this->filterSampai);
                }
                $dataKetidakhadiranPerjam = $ketidakhadiranPerjamBase
                    ->select(
                        'absensi_perjam.*',
                        'jp.tanggal',
                        'jp.jam_ke',
                        'jp.waktu_mulai',
                        'jp.waktu_selesai',
                        'k.nama as kelas_nama',
                        'm.nama as mapel_nama'
                    )
                    ->orderByDesc('jp.tanggal')
                    ->orderByDesc('jp.jam_ke')
                    ->paginate(10, ['*'], 'perjamPage');

                // ---- Tab C: Catatan Wali Kelas (selalu tampil bila ada) ----
                $catatan = CatatanRapor::query()
                    ->with(['guru'])
                    ->where('semester_idsemester', $konteks->semester->idsemester)
                    ->where('siswa_idsiswa', $siswaId)
                    ->first();
                $dataCatatan = $catatan;

                // ---- Tab D: Perkembangan Akademik (snapshot nilai_akhir, semester aktif; lintas kelas) ----
                $nilaiAktif = \App\Models\NilaiAkhir::query()
                    ->with(['mapel'])
                    ->where('semester_idsemester', $konteks->semester->idsemester)
                    ->where('siswa_idsiswa', $siswaId)
                    ->orderBy('mapel_idmapel')
                    ->get();
                $dataPerkembangan = $this->ringkasanPerkembangan($nilaiAktif);
            } catch (\Throwable $e) {
                $gagalMuat = true;
                Log::warning('wali-akademik: gagal memuat data', [
                    'wali_id' => $wali->id,
                    'siswa_id' => is_numeric($this->siswaId) ? (int) $this->siswaId : null,
                    'tab' => $this->tab,
                    'exception' => get_class($e),
                ]);
            }
        }

        return view('livewire.wali-akademik-component', [
            'konteks' => $konteks,
            'relasiList' => $relasiList,
            'siswa' => $siswa,
            'dataNilai' => $dataNilai,
            'dataAbsensiHarian' => $dataAbsensiHarian,
            'dataAbsensiPerjam' => $dataAbsensiPerjam,
            'dataRekap' => $dataRekap,
            'dataKetidakhadiranHarian' => $dataKetidakhadiranHarian,
            'dataKetidakhadiranPerjam' => $dataKetidakhadiranPerjam,
            'dataCatatan' => $dataCatatan,
            'dataPerkembangan' => $dataPerkembangan,
            'dataJadwal' => $dataJadwal,
            'gagalMuat' => $gagalMuat,
        ]);
    }

    /**
     * Query harian untuk tab histori ("Absensi Harian").
     * Dibatasi semester/TA aktif + periode relasi wali.
     */
    private function queryHarian(int $siswaId, KonteksAkademik $konteks, $relasi)
    {
        $query = AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)
            ->where('semester_idsemester', $konteks->semester->idsemester)
            ->where('siswa_idsiswa', $siswaId);
        if ($relasi->tanggal_mulai) {
            $query->whereDate('tanggal', '>=', $relasi->tanggal_mulai);
        }
        if ($relasi->tanggal_berhenti) {
            $query->whereDate('tanggal', '<=', $relasi->tanggal_berhenti);
        }

        return $query;
    }

    /**
     * Ringkasan akademik dari snapshot nilai_akhir untuk semester aktif.
     * Tidak menghitung ulang dari nilai_siswa; menggunakan snapshot + KKTP.
     *
     * @return array{rows: \Illuminate\Support\Collection, jumlah_mapel: int, rata_rata: float|null, jumlah_di_bawah_kktp: int}
     */
    private function ringkasanPerkembangan($nilaiAktif): array
    {
        $rows = collect();
        $jumlahTotal = 0;
        $totalNilai = 0.0;
        $jumlahDiBawahKktp = 0;

        foreach ($nilaiAktif as $n) {
            $jumlahTotal++;
            $totalNilai += (float) $n->nilai_akhir;
            $kktp = $n->nilai_kktp !== null ? (float) $n->nilai_kktp : null;
            $diBawah = $kktp !== null && (float) $n->nilai_akhir < $kktp;
            if ($diBawah) {
                $jumlahDiBawahKktp++;
            }
            $rows->push([
                'mapel' => $n->mapel->nama ?? '-',
                'nilai' => (float) $n->nilai_akhir,
                'kktp' => $kktp,
                'di_bawah' => $diBawah,
            ]);
        }

        return [
            'rows' => $rows,
            'jumlah_mapel' => $jumlahTotal,
            'rata_rata' => $jumlahTotal > 0 ? round($totalNilai / $jumlahTotal, 2) : null,
            'jumlah_di_bawah_kktp' => $jumlahDiBawahKktp,
        ];
    }

    /**
     * Validasi tanggal ketat (Y-m-d). Kembalikan string bersih atau ''.
     */
    private function validTanggal($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $value = trim((string) $value);
        $parsed = \DateTime::createFromFormat('Y-m-d', $value);

        return ($parsed !== false && $parsed->format('Y-m-d') === $value) ? $value : '';
    }
}
