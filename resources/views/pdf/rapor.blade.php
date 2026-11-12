<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Rapor Siswa - {{ $siswa->nama }}</title>
<style>
@page { margin: 1.6cm; }
body { font-family: sans-serif; font-size: 11px; color: #111; }
.kop { text-align: center; border-bottom: 3px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
.kop .nama { font-size: 16px; font-weight: bold; text-transform: uppercase; }
.kop .sub { font-size: 11px; }
.judul { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 10px 0 8px; }
.identitas { width: 100%; font-size: 11px; margin-bottom: 10px; }
.identitas td { padding: 2px 4px; vertical-align: top; }
table.nilai { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
table.nilai th, table.nilai td { border: 1px solid #444; padding: 5px 6px; }
table.nilai th { background: #e8e8e8; text-align: center; }
.center { text-align: center; }
.box { border: 1px solid #444; padding: 8px; margin-bottom: 10px; }
.ttd { width: 100%; margin-top: 18px; }
.ttd td { width: 50%; text-align: center; vertical-align: top; }
.ttd .garis { margin-top: 55px; }
.kecil { font-size: 9px; color: #555; }
</style>
</head>
<body>
<div class="kop">
    <div class="nama">{{ $nama_sekolah }}</div>
    <div class="sub">LAPORAN HASIL BELAJAR (RAPOR) SISWA</div>
</div>

<div class="judul">Laporan Hasil Belajar Siswa</div>

<table class="identitas">
    <tr><td style="width:130px">Nama</td><td style="width:10px">:</td><td>{{ $siswa->nama }}</td></tr>
    <tr><td>NIS / NISN</td><td>:</td><td>{{ $siswa->nis ?? '-' }} / {{ $siswa->nisn ?? '-' }}</td></tr>
    <tr><td>Kelas</td><td>:</td><td>{{ $kelas->nama }} ({{ $kelas->tingkat }})</td></tr>
    <tr><td>Tahun Ajaran</td><td>:</td><td>{{ $konteks->tahunAjaranNama() ?? '-' }}</td></tr>
    <tr><td>Semester</td><td>:</td><td>{{ $konteks->semesterNama() ?? '-' }}</td></tr>
</table>

<table class="nilai">
    <thead>
        <tr><th>No</th><th>Mata Pelajaran</th><th>Nilai Akhir</th><th>KKTP</th><th>Keterangan</th></tr>
    </thead>
    <tbody>
    @foreach($nilai_final as $i => $baris)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $baris->mapel->nama ?? ('Mapel #'.$baris->mapel_idmapel) }}</td>
            <td class="center">{{ number_format((float) $baris->nilai_akhir, 2, ',', '.') }}</td>
            <td class="center">{{ $baris->nilai_kktp === null ? '-' : number_format((float) $baris->nilai_kktp, 2, ',', '.') }}</td>
            <td class="center">{{ $baris->keterangan ?? '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="box">
    <strong>Rekap Kehadiran</strong><br>
    Hadir: {{ $rekap_kehadiran['hadir'] }} &nbsp; Sakit: {{ $rekap_kehadiran['sakit'] }} &nbsp; Izin: {{ $rekap_kehadiran['izin'] }} &nbsp; Alpha: {{ $rekap_kehadiran['alpha'] }} &nbsp; (Total {{ $rekap_kehadiran['total'] }} hari)
    <br><span class="kecil">Kehadiran bersifat informasi rapor dan bukan komponen nilai akademik.</span>
</div>

<div class="box">
    <strong>Catatan Wali Kelas</strong><br>
    {{ $catatan?->catatan ?: '-' }}
</div>

<table class="ttd">
    <tr>
        <td>
            Kepala Sekolah
            <div class="garis">( ........................ )</div>
        </td>
        <td>
            Wali Kelas
            <div class="garis">{{ $kelas->wali->nama ?? '( ........................ )' }}</div>
        </td>
    </tr>
</table>

<p class="kecil" style="margin-top:14px">Dokumen resmi: rapor kelas ini berstatus final pada {{ $finalisasi->difinalisasi_pada?->format('d/m/Y H:i') ?? '-' }}. Nilai akhir diambil dari snapshot final dan tidak dihitung ulang saat dokumen ini dicetak.</p>
</body>
</html>
