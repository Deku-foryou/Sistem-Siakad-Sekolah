<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumen Refund {{ $dokumen['nomor'] }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h2 { margin: 0; }
        .header p { margin: 2px 0; }
        .title { text-align: center; font-weight: bold; font-size: 14px; margin: 18px 0 4px 0; text-transform: uppercase; }
        .subtitle { text-align: center; font-size: 10px; color: #444; margin-bottom: 14px; }
        .info { margin-bottom: 10px; }
        .info td { padding: 3px 6px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { border: 1px solid #333; padding: 6px 8px; }
        .table th { background: #f0f0f0; }
        .right { text-align: right; }
        .footer { margin-top: 36px; }
        .ttd { float: right; text-align: center; width: 220px; }
        .no { font-weight: bold; }
        .note { font-size: 9px; color: #555; border-top: 1px solid #999; padding-top: 8px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $sekolah['nama'] ?? config('app.name') }}</h2>
        @if(!empty($sekolah['alamat']))<p>{{ $sekolah['alamat'] }}</p>@endif
        @if(!empty($sekolah['telepon']))<p>Telp: {{ $sekolah['telepon'] }}</p>@endif
        @if(!empty($sekolah['email']))<p>{{ $sekolah['email'] }}</p>@endif
    </div>
    <div class="title">Dokumen Refund</div>
    <div class="subtitle">Pengembalian dana pembayaran &mdash; dokumen resmi sekolah</div>
    <table class="info">
        <tr>
            <td width="170">No. Dokumen</td>
            <td>: <span class="no">{{ $dokumen['nomor'] }}</span></td>
        </tr>
        <tr>
            <td>Tanggal Terbit</td>
            <td>: {{ $dokumen['tanggal'] }}</td>
        </tr>
        <tr>
            <td>Nama Siswa</td>
            <td>: {{ $dokumen['siswa_nama'] }} @if($dokumen['siswa_nis']) (NIS: {{ $dokumen['siswa_nis'] }}) @endif</td>
        </tr>
        <tr>
            <td>Pembayaran Asli</td>
            <td>: Pembayaran #{{ $dokumen['pembayaran_id'] }} &mdash; {{ strtoupper($dokumen['pembayaran_provider']) }} / {{ strtoupper($dokumen['pembayaran_metode']) }}</td>
        </tr>
        <tr>
            <td>Referensi Provider</td>
            <td>: {{ $dokumen['referensi_provider'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tagihan Asal</td>
            <td>: {{ $dokumen['tagihan_label'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nominal Pembayaran Asli</td>
            <td>: Rp {{ number_format($dokumen['pembayaran_nominal'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Metode Refund</td>
            <td>: {{ strtoupper(str_replace('_', ' ', $dokumen['metode_refund'])) }}</td>
        </tr>
        <tr>
            <td>Referensi Refund</td>
            <td>: {{ $dokumen['referensi_refund'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Alasan</td>
            <td>: {{ $dokumen['alasan'] }}</td>
        </tr>
    </table>
    <table class="table">
        <thead>
            <tr>
                <th>Uraian</th>
                <th class="right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pengembalian dana untuk Pembayaran #{{ $dokumen['pembayaran_id'] }}</td>
                <td class="right">{{ number_format($dokumen['nominal_refund'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th class="right">Total Refund</th>
                <th class="right">Rp {{ number_format($dokumen['nominal_refund'], 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
    <div class="footer">
        <div class="ttd">
            <p>Menyetujui,</p>
            <p style="margin-top:2px; font-size:9px; color:#555;">(Pemberi Persetujuan)</p>
            <br><br><br>
            <p><strong>{{ $dokumen['persetujuan_nama'] ?? '________________' }}</strong></p>
            @if(!empty($dokumen['persetujuan_pada']))<p style="font-size:9px;">Disetujui: {{ $dokumen['persetujuan_pada'] }}</p>@endif
        </div>
        <div style="clear: both;"></div>
        <p style="margin-top:24px;">Diproses oleh: <strong>{{ $dokumen['pemroses_nama'] ?? '________________' }}</strong> @if(!empty($dokumen['diproses_pada'])) &mdash; {{ $dokumen['diproses_pada'] }} @endif</p>
        <p style="font-size:9px; color:#555;">Diajukan oleh: {{ $dokumen['pengaju_nama'] ?? '-' }}</p>
    </div>
    <div class="note">
        Dokumen ini diterbitkan berdasarkan data pada saat proses refund selesai. Histori pembayaran asli tidak diubah oleh dokumen ini.
        Dokumen diterbitkan oleh sistem SIAKAD; verifikasi keaslian melalui nomor dokumen {{ $dokumen['nomor'] }} pada bagian keuangan sekolah.
    </div>
</body>
</html>
