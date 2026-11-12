<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuitansi {{ $kuitansi['nomor'] }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 2px 0; }
        .title { text-align: center; font-weight: bold; font-size: 14px; margin: 20px 0; text-transform: uppercase; }
        .info { margin-bottom: 10px; }
        .info td { padding: 4px 6px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #333; padding: 6px 8px; }
        .table th { background: #f0f0f0; }
        .right { text-align: right; }
        .footer { margin-top: 40px; }
        .ttd { float: right; text-align: center; width: 200px; }
        .no { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $sekolah['nama'] ?? config('app.name') }}</h2>
        @if(!empty($sekolah['alamat']))<p>{{ $sekolah['alamat'] }}</p>@endif
        @if(!empty($sekolah['telepon']))<p>Telp: {{ $sekolah['telepon'] }}</p>@endif
    </div>
    <div class="title">KUITANSI PEMBAYARAN</div>
    <table class="info">
        <tr>
            <td width="120">No. Kuitansi</td>
            <td>: <span class="no">{{ $kuitansi['nomor'] }}</span></td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: {{ $kuitansi['tanggal'] }}</td>
        </tr>
        <tr>
            <td>Nama Siswa</td>
            <td>: {{ $kuitansi['siswa_nama'] }} @if($kuitansi['siswa_nis']) (NIS: {{ $kuitansi['siswa_nis'] }}) @endif</td>
        </tr>
        <tr>
            <td>Nama Pembayar</td>
            <td>: {{ $kuitansi['nama_pembayar'] ?? '-' }}</td>
        </tr>
    </table>
    <table class="table">
        <thead>
            <tr>
                <th>Uraian</th>
                <th>Periode</th>
                <th>Metode</th>
                <th class="right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $kuitansi['tagihan_jenis'] === 'spp' ? 'SPP' : ucfirst($kuitansi['tagihan_jenis'] ?? 'Tagihan') }} @if($kuitansi['tagihan_nama']) - {{ $kuitansi['tagihan_nama'] }} @endif</td>
                <td>{{ $kuitansi['tagihan_periode'] ?? '-' }}</td>
                <td>{{ strtoupper($kuitansi['metode'] ?? '-') }}</td>
                <td class="right">{{ number_format($kuitansi['nominal'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="right">Total</th>
                <th class="right">Rp {{ number_format($kuitansi['nominal'], 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
    @if($kuitansi['referensi'])
    <p style="margin-top: 10px;">Referensi: {{ $kuitansi['referensi'] }}</p>
    @endif
    <div class="footer">
        <div class="ttd">
            <p>Petugas</p>
            <br><br><br>
            <p>(________________________)</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
