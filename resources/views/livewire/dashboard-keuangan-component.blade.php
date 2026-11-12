@section('title', 'Dashboard Keuangan')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-chart-pie me-2 text-success" aria-hidden="true"></i>Dashboard Keuangan</h1>
            <p class="sa-sub">Ringkasan tagihan, status pembayaran, dan kinerja keuangan sekolah pada tahun ajaran aktif.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Siswa Aktif</div>
                <div class="sa-stat-value text-primary">{{ $totalSiswaAktif }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Tagihan Terbit</div>
                <div class="sa-stat-value">{{ $tagihanTerbit }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Lunas</div>
                <div class="sa-stat-value text-success">{{ $tagihanLunas }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Belum Lunas</div>
                <div class="sa-stat-value text-warning">{{ $tagihanBelumLunas }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Dibebaskan</div>
                <div class="sa-stat-value text-secondary">{{ $tagihanDibebaskan }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Dibayar Sebagian</div>
                <div class="sa-stat-value text-info">{{ $tagihanDibayarSebagian }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Total Nominal</div>
                <div class="sa-stat-value">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sa-card sa-stat">
                <div class="sa-stat-label">Estimasi Terbayar</div>
                <div class="sa-stat-value text-success">Rp {{ number_format($totalTerbayar, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="sa-card mb-3">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-table" aria-hidden="true"></i>Tunggakan per Kelas</h2>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-end">Estimasi Terbayar</th>
                        <th class="text-end">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tagihanPerKelas as $row)
                        <tr>
                            <td>{{ $row['kelas'] }}</td>
                            <td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['estimasi'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['total'] - $row['estimasi'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="sa-empty"><i class="fa-solid fa-inbox" aria-hidden="true"></i><p class="sa-empty-title">Belum ada tagihan</p><p class="sa-empty-text">Terbitkan tagihan terlebih dahulu untuk melihat rekap per kelas.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot">
            <a href="{{ url('datatagihan') }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-list me-1" aria-hidden="true"></i>Lihat Semua Tagihan</a>
            <a href="{{ url('tarif') }}" class="btn btn-sm btn-outline-secondary ms-2"><i class="fa-solid fa-tags me-1" aria-hidden="true"></i>Atur Tarif</a>
            <a href="{{ url('beasiswa') }}" class="btn btn-sm btn-outline-secondary ms-2"><i class="fa-solid fa-hands-holding-child me-1" aria-hidden="true"></i>Kelola Beasiswa</a>
        </div>
    </div>
</div>
