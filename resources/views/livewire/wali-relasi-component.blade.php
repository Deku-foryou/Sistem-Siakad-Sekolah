@section('title', 'Relasi Saya')

<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-link me-2 text-success" aria-hidden="true"></i>Relasi Saya</h1>
            <p class="sa-sub">Kelola hubungan antara akun wali Anda dengan siswa yang menjadi tanggung jawab Anda.</p>
        </div>
    </div>

    {{-- Feedback --}}
    @if ($sukses !== '')
        <div class="alert alert-success d-flex justify-content-between align-items-center gap-2 py-2" role="status">
            <span><i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>{{ $sukses }}</span>
            <button type="button" class="btn-close" wire:click="$set('sukses','')" aria-label="Tutup pesan sukses" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
        </div>
    @endif
    @if ($galat !== '')
        <div class="alert alert-danger d-flex justify-content-between align-items-center gap-2 py-2" role="alert">
            <span><i class="fa-solid fa-circle-exclamation me-2" aria-hidden="true"></i>{{ $galat }}</span>
            <button type="button" class="btn-close" wire:click="$set('galat','')" aria-label="Tutup pesan error" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
        </div>
    @endif

    {{-- Daftar relasi milik wali --}}
    <section class="sa-card mb-3" aria-labelledby="daftar-relasi-heading">
        <div class="sa-card-head">
            <h2 id="daftar-relasi-heading" class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Daftar Relasi</h2>
            <span class="sa-count">{{ $relasi->total() }} relasi</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>NIS</th>
                        <th>Hubungan</th>
                        <th>Status</th>
                        <th>Periode Akses</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($relasi as $r)
                        @php $permintaanPutusMenunggu = $r->permintaanPutus->firstWhere('status_permintaan', 'menunggu'); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $r->siswa->nama ?? '-' }}</td>
                            <td class="sa-cell-muted">{{ $r->siswa->nis ?? '-' }}</td>
                            <td>{{ ucfirst($r->hubungan) }}</td>
                            <td>
                                @if ($r->status_hubungan === 'verified')
                                    <span class="sa-badge sa-hadir">Terverifikasi</span>
                                @elseif ($r->status_hubungan === 'pending')
                                    <span class="sa-badge sa-sakit">Menunggu verifikasi</span>
                                @elseif ($r->status_hubungan === 'rejected')
                                    <span class="sa-badge sa-alpha">Ditolak</span>
                                @else
                                    <span class="sa-badge sa-netral">Berakhir</span>
                                @endif
                                @if ($permintaanPutusMenunggu)
                                    <div class="sa-hint mt-1"><i class="fa-solid fa-clock me-1" aria-hidden="true"></i>Pemutusan menunggu keputusan pengelola</div>
                                @endif
                            </td>
                            <td class="sa-cell-muted">
                                {{ $r->tanggal_mulai?->format('d/m/Y') ?? '-' }}
                                @if ($r->tanggal_berhenti)
                                    &ndash; {{ $r->tanggal_berhenti->format('d/m/Y') }}
                                @else
                                    &ndash; sekarang
                                @endif
                            </td>
                            <td>
                                @if (in_array($r->status_hubungan, ['rejected', 'terminated'], true))
                                    <button class="btn btn-sm btn-outline-primary" wire:click="bukaUlang({{ $r->idwalimurid }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Ajukan">Ajukan Ulang</button>
                                @elseif ($r->status_hubungan === 'verified')
                                    @if ($permintaanPutusMenunggu)
                                        <span class="sa-badge sa-sakit" title="Pemutusan sudah diajukan"><i class="fa-solid fa-clock me-1" aria-hidden="true"></i>Menunggu</span>
                                    @else
                                        <button class="btn btn-sm btn-outline-warning" wire:click="bukaPutus({{ $r->idwalimurid }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Tab permintaan putus">Minta Putus</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="sa-empty">
                                    <i class="fa-regular fa-handshake" aria-hidden="true"></i>
                                    <p class="sa-empty-title">Belum ada relasi</p>
                                    <p class="sa-empty-text">Ajukan relasi melalui formulir di bawah untuk mulai mengakses data akademik siswa.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot">
            <small class="text-body-secondary">Menampilkan {{ $relasi->firstItem() }}&ndash;{{ $relasi->lastItem() }} dari {{ $relasi->total() }} relasi</small>
            <div>{{ $relasi->links() }}</div>
        </div>
    </section>

    {{-- Ajukan relasi baru --}}
    <section class="sa-card mb-3" aria-labelledby="ajukan-relasi-heading">
        <div class="sa-card-head">
            <h2 id="ajukan-relasi-heading" class="sa-card-title"><i class="fa-solid fa-user-plus" aria-hidden="true"></i>Ajukan Relasi Baru</h2>
        </div>
        <div class="sa-card-body">
            <p class="sa-hint mb-3">Masukkan NIS atau nama siswa untuk mencari siswa yang ingin dihubungkan dengan akun wali Anda.</p>

            <div class="row g-2">
                <div class="col-md-8">
                    <label for="cari-siswa" class="form-label">Cari siswa</label>
                    <input type="text" id="cari-siswa" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Contoh: 12345 atau Budi" aria-describedby="cari-siswa-help" autocomplete="off">
                    <div id="cari-siswa-help" class="form-text">Hanya siswa aktif yang dapat diajukan.</div>
                </div>
            </div>

            @if ($hasilCari->isNotEmpty())
                <div class="mt-3 border rounded" style="border-color: var(--sa-border) !important;">
                    <ul class="list-group mb-0">
                        @foreach ($hasilCari as $s)
                            <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2">
                                <span><strong>{{ $s->nama }}</strong><br><small class="text-body-secondary">NIS {{ $s->nis }}&middot; Nisn {{ $s->nisn }}</small></span>
                                <button class="btn btn-sm btn-outline-primary" wire:click="pilihSiswa({{ $s->idsiswa }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Pilih">Pilih</button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @elseif (trim($search) !== '' && mb_strlen(trim($search)) >= 2)
                <p class="sa-hint mt-2 mb-0"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Tidak ada siswa aktif yang cocok.</p>
            @endif

            @if ($siswaTerpilih)
                <div class="border rounded p-3 mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: var(--sa-green-faint); border-color: var(--sa-border) !important;">
                    <div>
                        <div class="sa-hint mb-1">Siswa terpilih</div>
                        <div class="fw-semibold">{{ $siswaTerpilih->nama }}</div>
                        <div class="sa-cell-muted">NIS {{ $siswaTerpilih->nis }}&middot; {{ $siswaTerpilih->nisn }}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="gantiSiswa" data-bs-toggle="tooltip" data-bs-placement="top" title="Ganti siswa">Ganti siswa</button>
                </div>

                <div class="row g-2 mt-3">
                    <div class="col-md-6">
                        <label for="hubungan" class="form-label">Hubungan Anda dengan siswa <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="hubungan" class="form-select" wire:model="hubungan">
                            <option value="ayah">Ayah</option>
                            <option value="ibu">Ibu</option>
                            <option value="wali">Wali</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="alasan-pengajuan" class="form-label">Alasan (opsional)</label>
                        <textarea id="alasan-pengajuan" class="form-control" wire:model="alasanPengajuan" rows="2" maxlength="500" aria-describedby="alasan-help"></textarea>
                        <div id="alasan-help" class="form-text">Maksimal 500 karakter.</div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary" wire:click="ajukan" wire:loading.attr="disabled" wire:target="ajukan" data-bs-toggle="tooltip" data-bs-placement="top" title="Ajukan">
                        <span wire:loading.remove wire:target="ajukan"><i class="fa-solid fa-paper-plane me-1" aria-hidden="true"></i>Ajukan Relasi</span>
                        <span wire:loading wire:target="ajukan"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Mengirim&hellip;</span>
                    </button>
                </div>
            @endif
        </div>
    </section>

    {{-- Keterangan status --}}
    <aside class="sa-card" aria-labelledby="status-relasi-heading">
        <div class="sa-card-body">
            <h2 id="status-relasi-heading" class="sa-card-title mb-2"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>Arti Status Relasi</h2>
            <ul class="sa-hint mb-0" style="padding-left:1.2rem;">
                <li><strong>Menunggu verifikasi</strong> &mdash; Pengajuan sedang menunggu pemeriksaan admin.</li>
                <li><strong>Terverifikasi</strong> &mdash; Relasi telah disetujui; akses akademik tersedia sesuai ketentuan periode.</li>
                <li><strong>Ditolak</strong> &mdash; Pengajuan tidak disetujui. Anda dapat mengajukan ulang.</li>
                <li><strong>Berakhir</strong> &mdash; Relasi sudah berakhir. Anda dapat mengajukan ulang.</li>
            </ul>
        </div>
    </aside>

    {{-- Modal pengajuan ulang --}}
    @if ($relasiAsalId !== null)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);" role="dialog" aria-modal="true" aria-labelledby="ulang-modal-title">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ulang-modal-title"><i class="fa-solid fa-rotate-left me-2" aria-hidden="true"></i>Ajukan Ulang Relasi</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="batalUlang" aria-label="Tutup" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="ulang-hubungan" class="form-label">Hubungan Anda dengan siswa</label>
                            <select id="ulang-hubungan" class="form-select" wire:model="hubunganUlang">
                                <option value="ayah">Ayah</option>
                                <option value="ibu">Ibu</option>
                                <option value="wali">Wali</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label for="ulang-alasan" class="form-label">Catatan (opsional)</label>
                            <textarea id="ulang-alasan" class="form-control" wire:model="alasanUlang" rows="2" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="batalUlang" data-bs-toggle="tooltip" data-bs-placement="top" title="Batalkan aksi">Batal</button>
                        <button type="button" class="btn btn-primary" wire:click="ajukanUlang" wire:loading.attr="disabled" wire:target="ajukanUlang" data-bs-toggle="tooltip" data-bs-placement="top" title="Ajukan"><span wire:loading.remove wire:target="ajukanUlang">Ajukan Ulang</span><span wire:loading wire:target="ajukanUlang">Mengirim&hellip;</span></button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal permintaan putus (P5b) --}}
    @if ($targetRelasiPutusId !== null)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);" role="dialog" aria-modal="true" aria-labelledby="putus-modal-title">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="putus-modal-title"><i class="fa-solid fa-link-slash me-2" aria-hidden="true"></i>Minta Putus Relasi</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="batalPutus" aria-label="Tutup" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
                    </div>
                    <div class="modal-body">
                        <p class="sa-hint">Relasi Anda tetap aktif selama pengelola belum memutuskan. Anda dapat dihubungi untuk konfirmasi.</p>
                        <div class="mb-0">
                            <label for="putus-alasan" class="form-label">Alasan (wajib)</label>
                            <textarea id="putus-alasan" class="form-control" wire:model="alasanPutus" rows="3" maxlength="500" aria-describedby="putus-alasan-help"></textarea>
                            <div id="putus-alasan-help" class="form-text">Maksimal 500 karakter.</div>
                            @error('alasanPutus') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="batalPutus" data-bs-toggle="tooltip" data-bs-placement="top" title="Batalkan aksi">Batal</button>
                        <button type="button" class="btn btn-warning" wire:click="ajukanPutus" wire:loading.attr="disabled" wire:target="ajukanPutus" data-bs-toggle="tooltip" data-bs-placement="top" title="Kirim"><span wire:loading.remove wire:target="ajukanPutus">Kirim Permintaan</span><span wire:loading wire:target="ajukanPutus">Mengirim&hellip;</span></button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
