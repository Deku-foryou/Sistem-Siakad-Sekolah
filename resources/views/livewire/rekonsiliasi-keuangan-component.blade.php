<div>
@section('title', 'Rekonsiliasi Keuangan')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-scale-balanced me-2 text-success" aria-hidden="true"></i>Rekonsiliasi Keuangan</h1>
            <p class="sa-sub">Pemeriksaan konsistensi ledger &amp; kuitansi — read-only. Tidak ada perbaikan otomatis; koreksi harus melalui proses resmi.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="sa-chip"><i class="fa-solid fa-shield-halved me-1"></i>Operasional</span>
        </div>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="rk-periode" class="form-label">Periode (opsional, YYYY-MM)</label>
                <input id="rk-periode" type="text" class="form-control" wire:model.live.debounce.400ms="filterPeriode" placeholder="2028-03">
                <small class="form-text text-muted">Berlaku juga saat "Jalankan Pemeriksaan" (membatasi cakupan); memfilter riwayat &amp; hasil dipilih.</small>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" wire:click="jalankan" wire:loading.attr="disabled" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Jalankan pemeriksaan">
                    <i class="fa-solid fa-arrows-rotate me-1"></i>Jalankan Pemeriksaan
                </button>
            </div>
            <div class="col-md-2">
                <label for="rk-tab" class="form-label">Tampilan</label>
                <select id="rk-tab" class="form-select" wire:model.live="tab">
                    <option value="temuan">Temuan Saat Terpilih</option>
                    <option value="riwayat">Riwayat Pemeriksaan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="rk-kat" class="form-label">Kategori</label>
                <select id="rk-kat" class="form-select" wire:model.live="filterKategori">
                    <option value="">Semua kategori</option>
                    @foreach($kategoriAda as $kat)
                        <option value="{{ $kat }}">{{ $kategoriLabel[$kat] ?? $kat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="rk-stat" class="form-label">Status</label>
                <select id="rk-stat" class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua status</option>
                    <option value="kritis">Kritis</option>
                    <option value="perlu_ditinjau">Perlu ditinjau</option>
                    <option value="info">Info</option>
                </select>
            </div>
            @if($hasil)
            <div class="col-md-2 text-end">
                <label for="rk-export-pilih" class="form-label">Ekspor CSV</label>
                <a id="rk-export-pilih" class="btn btn-outline-success w-100" href="{{ route('rekonsiliasikeuangan.export.ringkasan', ['id' => $hasil->id]) }}" target="_blank" rel="noopener" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor ringkasan CSV">
                    <i class="fa-solid fa-file-csv me-1"></i>Ringkasan
                </a>
                <a class="btn btn-outline-primary w-100 mt-1" target="_blank" rel="noopener" href="{{ route('rekonsiliasikeuangan.export.temuan', array_merge(['id' => $hasil->id], array_filter(['kategori' => $filterKategori, 'status' => $filterStatus]))) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Filter data">
                    <i class="fa-solid fa-file-csv me-1"></i>Temuan (sesuai filter)
                </a>
            </div>
            @endif
        </div>
    </div>

    @if($hasil)
        @if(!empty($terpotong))
            <div class="alert alert-warning mb-3" role="alert">
                <div class="fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Hasil terbatas (truncated) — batas 500 temuan per kategori</div>
                <div class="mt-1">Kategori berikut memiliki lebih banyak temuan daripada yang ditampilkan:</div>
                <ul class="mb-0 mt-1">
                    @foreach($terpotong as $kat => $total)
                        <li><strong>{{ $kategoriLabel[$kat] ?? $kat }}</strong>: total sebenarnya {{ number_format((int) (is_numeric($total) ? $total : 0), 0, ',', '.') }} temuan (hanya 500 disimpan &amp; ditampilkan).</li>
                    @endforeach
                </ul>
                <div class="mt-1 small text-body-secondary">Jumlah temuan ({{ $hasil->jumlah_temuan }}) adalah jumlah yang tersimpan, bukan total sebelum pembatasan. Gunakan ekspor CSV untuk mengekspor temuan tersimpan; untuk memperbaiki temuan yang tidak ditampilkan, perbaiki penyebab utama lalu jalankan ulang pemeriksaan.</div>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="sa-card p-3"><div class="text-muted small">Jumlah pemeriksaan</div><div class="fs-4 fw-bold">{{ $hasil->jumlah_pemeriksaan }}</div></div></div>
            <div class="col-md-3"><div class="sa-card p-3"><div class="text-muted small">Jumlah temuan (tersimpan)</div><div class="fs-4 fw-bold {{ (int) $hasil->jumlah_temuan > 0 ? 'text-warning-emphasis' : 'text-success' }}">{{ $hasil->jumlah_temuan }}</div>@if(($totalDitemukan ?? 0) !== (int) $hasil->jumlah_temuan)<div class="small text-muted">Total ditemukan: {{ number_format((int) ($totalDitemukan ?? 0), 0, ',', '.') }}</div>@endif</div></div>
            <div class="col-md-3"><div class="sa-card p-3"><div class="text-muted small">Kritis</div><div class="fs-4 fw-bold {{ $hasil->jumlah_kritis > 0 ? 'text-danger' : 'text-success' }}">{{ $hasil->jumlah_kritis }}</div></div></div>
            <div class="col-md-3"><div class="sa-card p-3"><div class="text-muted small">Dijalankan oleh</div><div class="fs-6 fw-bold">{{ $hasil->pembuat?->name ?? '-' }}</div><div class="small text-muted">{{ $hasil->dijalankan_pada?->format('d/m/Y H:i') }}</div></div></div>
        </div>

        @if(!empty($perKategori))
            <div class="sa-card mb-3">
                <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-chart-column" aria-hidden="true"></i>Ringkasan per kategori</h2></div>
                <div class="p-3 d-flex flex-wrap gap-2">
                    @foreach($perKategori as $kat => $jumlah)
                        <span class="sa-chip"><i class="fa-solid fa-tag me-1" aria-hidden="true"></i>{{ $kategoriLabel[$kat] ?? $kat }}: {{ number_format((int) (is_numeric($jumlah) ? $jumlah : 0), 0, ',', '.') }}@if(isset($terpotong[$kat])) <span class="sa-badge sa-netral ms-1" title="Dibatasi 500 temuan">terpotong</span>@endif</span>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="sa-card mb-3"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Belum ada pemeriksaan</p><p class="sa-empty-text">Klik "Jalankan Pemeriksaan" untuk memulai rekonsiliasi keuangan.</p></div></div>
    @endif

    @if($tab === 'temuan')
    <div class="sa-card mb-3">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Temuan</h2>
            <span class="sa-count">{{ $temuanRows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $temuanRows->total() : $temuanRows->count() }} temuan</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Kategori</th><th>Ref</th><th>Siswa</th><th>Tagihan</th><th class="text-end">Selisih (Rp)</th><th>Keterangan</th><th>Status</th><th>Waktu Pemeriksaan</th></tr>
                </thead>
                <tbody>
                    @forelse ($temuanRows ?? collect() as $row)
                        <tr>
                            <td><span class="small">{{ $kategoriLabel[$row->kategori] ?? $row->kategori }}</span></td>
                            <td><code class="small">{{ $row->referensi_tipe }}#{{ $row->referensi_id }}</code></td>
                            <td>{{ $row->siswa ? $row->siswa->nama . ' (NIS '.($row->siswa->nis ?? '-').')' : '-' }}</td>
                            <td>{{ $row->tagihan ? strtoupper($row->tagihan->jenis).' '.$row->tagihan->periode : '-' }}</td>
                            <td class="text-end">{{ $row->nominal_selisih !== null ? number_format((int) $row->nominal_selisih, 0, ',', '.') : '-' }}</td>
                            <td><small>{{ $row->keterangan }}</small></td>
                            <td>
                                @if($row->status === 'kritis') <span class="sa-badge sa-alpha">Kritis</span>
                                @elseif($row->status === 'perlu_ditinjau') <span class="sa-badge sa-netral">Perlu ditinjau</span>
                                @else <span class="sa-badge sa-hadir">Info</span>
                                @endif
                            </td>
                            <td class="text-nowrap"><small>{{ $hasil?->dijalankan_pada?->format('d/m/Y H:i') ?? '-' }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="sa-empty"><i class="fa-solid fa-circle-check"></i><p class="sa-empty-title">Tidak ada temuan</p><p class="sa-empty-text">Data konsisten atau belum ada pemeriksaan tersimpan.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($hasil && $temuanRows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $temuanRows->hasPages())<div class="sa-card-foot">{{ $temuanRows->links() }}</div>@endif
    </div>
    @else
    <div class="sa-card mb-3">
        <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>Riwayat Pemeriksaan</h2><span class="sa-count">{{ $hasilList->count() }} hasil</span></div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Tanggal</th><th>Dijalankan</th><th>Periode</th><th class="text-end">Temuan</th><th class="text-end">Kritis</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($hasilList as $h)
                        <tr>
                            <td>{{ $h->dijalankan_pada?->format('d/m/Y H:i') }}</td>
                            <td>{{ $h->pembuat?->name ?? '-' }}</td>
                            <td>{{ $h->periode ?: 'Semua' }}</td>
                            <td class="text-end">{{ $h->jumlah_temuan }}</td>
                            <td class="text-end">{{ $h->jumlah_kritis }}</td>
                            <td class="text-center"><button class="btn btn-sm btn-outline-primary" wire:click="pilihHasil({{ $h->id }})" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat detail">Lihat Temuan</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Belum ada riwayat</p><p class="sa-empty-text">Riwayat pemeriksaan akan tampil di sini.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
</div>
