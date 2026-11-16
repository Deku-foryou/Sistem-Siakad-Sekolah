@section('title', 'Rekonsiliasi Keuangan')
<div>
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-scale-balanced me-2 text-success" aria-hidden="true"></i>Rekonsiliasi Keuangan</h1>
            <p class="sa-sub">Pemeriksaan konsistensi catatan pembayaran, alokasi, tagihan, saldo, refund, dan koreksi. Halaman ini read-only dan tidak memperbaiki data secara otomatis; koreksi melalui proses resmi.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="sa-chip"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Read Only</span>
        </div>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="rk-periode" class="form-label">Periode <span class="text-body-secondary fw-normal">(opsional)</span></label>
                <input id="rk-periode" type="text" class="form-control" wire:model.live.debounce.400ms="filterPeriode" placeholder="YYYY-MM, mis. 2026-11" inputmode="numeric" autocomplete="off">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <button class="btn btn-primary w-100" wire:click="jalankan" wire:loading.attr="disabled" wire:target="jalankan" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Jalankan pemeriksaan baru">
                    <span wire:loading wire:target="jalankan" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    <i class="fa-solid fa-arrows-rotate me-1" wire:loading.remove wire:target="jalankan" aria-hidden="true"></i>Jalankan Pemeriksaan
                </button>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label for="rk-tab" class="form-label">Tampilan</label>
                <select id="rk-tab" class="form-select" wire:model.live="tab">
                    <option value="temuan">Temuan</option>
                    <option value="riwayat">Riwayat</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label for="rk-kat" class="form-label">Kategori</label>
                <select id="rk-kat" class="form-select" wire:model.live="filterKategori">
                    <option value="">Semua kategori</option>
                    @foreach($kategoriAda as $kat)
                        <option value="{{ $kat }}">{{ $kategoriLabel[$kat] ?? $kat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label for="rk-stat" class="form-label">Status</label>
                <select id="rk-stat" class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua status</option>
                    <option value="kritis">Kritis</option>
                    <option value="perlu_ditinjau">Perlu ditinjau</option>
                    <option value="info">Info</option>
                </select>
            </div>
        </div>
        @if($hasil)
        <div class="sa-divider"></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="text-body-secondary small"><i class="fa-solid fa-file-csv me-1" aria-hidden="true"></i>Ekspor CSV (hasil terpilih):</span>
            <span class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('rekonsiliasikeuangan.export.ringkasan', ['id' => $hasil->id]) }}" target="_blank" rel="noopener" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor ringkasan hasil pemeriksaan terpilih">Ringkasan</a>
                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('rekonsiliasikeuangan.export.temuan', array_merge(['id' => $hasil->id], array_filter(['kategori' => $filterKategori, 'status' => $filterStatus]))) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor temuan sesuai filter kategori & status">Temuan</a>
            </span>
        </div>
        @endif
    </div>

    @if($hasil)
        @if(!empty($terpotong))
            <div class="alert alert-warning mb-3" role="alert">
                <div class="fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Hasil terbatas — batas 500 temuan per kategori</div>
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
            <div class="col-6 col-lg-3">
                <div class="sa-card p-3 h-100">
                    <div class="text-muted small">Jumlah Pemeriksaan</div>
                    <div class="fs-4 fw-bold">{{ $hasil->jumlah_pemeriksaan }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="sa-card p-3 h-100">
                    <div class="text-muted small">Jumlah Temuan</div>
                    <div class="fs-4 fw-bold {{ (int) $hasil->jumlah_temuan > 0 ? 'text-warning-emphasis' : '' }}">{{ $hasil->jumlah_temuan }}</div>
                    @if(($totalDitemukan ?? 0) !== (int) $hasil->jumlah_temuan)<div class="small text-muted">Total ditemukan: {{ number_format((int) ($totalDitemukan ?? 0), 0, ',', '.') }}</div>@endif
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="sa-card p-3 h-100">
                    <div class="text-muted small">Temuan Kritis</div>
                    <div class="fs-4 fw-bold {{ $hasil->jumlah_kritis > 0 ? 'text-danger' : '' }}">{{ $hasil->jumlah_kritis }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="sa-card p-3 h-100">
                    <div class="text-muted small">Pemeriksaan Terakhir</div>
                    <div class="fw-bold">{{ $hasil->pembuat?->name ?? '-' }}</div>
                    <div class="small text-muted">{{ $hasil->dijalankan_pada?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>

        @if(!empty($perKategori))
            <div class="sa-card mb-3">
                <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-chart-column" aria-hidden="true"></i>Ringkasan per Kategori</h2></div>
                <div class="p-3 d-flex flex-wrap gap-2">
                    @foreach($perKategori as $kat => $jumlah)
                        <span class="sa-chip"><i class="fa-solid fa-tag me-1" aria-hidden="true"></i>{{ $kategoriLabel[$kat] ?? $kat }}: {{ number_format((int) (is_numeric($jumlah) ? $jumlah : 0), 0, ',', '.') }}@if(isset($terpotong[$kat])) <span class="sa-badge sa-netral ms-1" title="Dibatasi 500 temuan">terpotong</span>@endif</span>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="sa-card mb-3"><div class="sa-empty"><i class="fa-solid fa-inbox" aria-hidden="true"></i><p class="sa-empty-title">Belum ada pemeriksaan</p><p class="sa-empty-text">Klik "Jalankan Pemeriksaan" untuk memulai rekonsiliasi keuangan.</p></div></div>
    @endif

    @if($tab === 'temuan')
    <div class="sa-card mb-3">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-table-list" aria-hidden="true"></i>Daftar Temuan</h2>
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
                            <td class="text-end" style="font-variant-numeric: tabular-nums;">{{ $row->nominal_selisih !== null ? number_format((int) $row->nominal_selisih, 0, ',', '.') : '-' }}</td>
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
                        @if(!$hasil)
                            <tr><td colspan="8"><div class="sa-empty"><i class="fa-solid fa-inbox" aria-hidden="true"></i><p class="sa-empty-title">Belum ada pemeriksaan</p><p class="sa-empty-text">Jalankan pemeriksaan untuk melihat temuan.</p></div></td></tr>
                        @elseif($filterKategori !== '' || $filterStatus !== '')
                            <tr><td colspan="8"><div class="sa-empty"><i class="fa-solid fa-filter" aria-hidden="true"></i><p class="sa-empty-title">Tidak ada temuan sesuai filter</p><p class="sa-empty-text">Ubah kategori atau status filter untuk melihat temuan lainnya.</p></div></td></tr>
                        @else
                            <tr><td colspan="8"><div class="sa-empty"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p class="sa-empty-title">Tidak ada temuan</p><p class="sa-empty-text">Pemeriksaan selesai dan data konsisten; tidak ada ketidaksesuaian yang terdeteksi.</p></div></td></tr>
                        @endif
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
                        <tr class="{{ (int) $h->id === (int) $selectedHasilId ? 'sa-row-active' : '' }}">
                            <td class="text-nowrap">{{ $h->dijalankan_pada?->format('d/m/Y H:i') }}</td>
                            <td>{{ $h->pembuat?->name ?? '-' }}</td>
                            <td>{{ $h->periode ?: 'Semua' }}</td>
                            <td class="text-end" style="font-variant-numeric: tabular-nums;">{{ $h->jumlah_temuan }}</td>
                            <td class="text-end" style="font-variant-numeric: tabular-nums;">{{ $h->jumlah_kritis }}</td>
                            <td class="text-center"><button class="btn btn-sm btn-outline-primary" wire:click="pilihHasil({{ $h->id }})" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat temuan pemeriksaan ini">Lihat Temuan</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sa-empty"><i class="fa-solid fa-inbox" aria-hidden="true"></i><p class="sa-empty-title">Belum ada riwayat</p><p class="sa-empty-text">Riwayat pemeriksaan akan tampil di sini.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot"><span>25 hasil pemeriksaan terbaru{{ $filterPeriode !== '' ? ' untuk periode '.$filterPeriode : '' }}.</span></div>
    </div>
    @endif
</div>
</div>
