@section('title', 'Pengumuman')

<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-bullhorn me-2 text-success" aria-hidden="true"></i>Pengumuman</h1>
            <p class="sa-sub">Kelola pengumuman sekolah untuk siswa. Pengumuman terbit tidak dapat diedit atau dikembalikan menjadi draf.</p>
        </div>
        <button class="btn btn-primary" wire:click="tambahPengumuman" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Pengumuman
        </button>
    </div>

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="cari" class="form-label">Cari judul/isi</label>
                <input type="text" id="cari" wire:model.live="search" class="form-control sa-focus" placeholder="Ketik kata kunci&hellip;">
            </div>
            <div class="col-6 col-md-2">
                <label for="filter-status" class="form-label">Status</label>
                <select id="filter-status" wire:model.live="filterStatus" class="form-select sa-focus">
                    <option value="">Semua</option>
                    <option value="draf">Draf</option>
                    <option value="terbit">Terbit</option>
                    <option value="arsip">Arsip</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="filter-kategori" class="form-label">Kategori</label>
                <select id="filter-kategori" wire:model.live="filterKategori" class="form-select sa-focus">
                    <option value="">Semua</option>
                    <option value="akademik">Akademik</option>
                    <option value="acara">Acara</option>
                    <option value="penting">Penting</option>
                    <option value="darurat">Darurat</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="filter-prioritas" class="form-label">Prioritas</label>
                <select id="filter-prioritas" wire:model.live="filterPrioritas" class="form-select sa-focus">
                    <option value="">Semua</option>
                    <option value="rendah">Rendah</option>
                    <option value="sedang">Sedang</option>
                    <option value="tinggi">Tinggi</option>
                    <option value="mendesak">Mendesak</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilter" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset">Reset</button>
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    @if ($pengumumanList->total() > 0)
        <div class="sa-card">
            <div class="sa-card-head">
                <h2 class="sa-card-title"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i>Daftar Pengumuman</h2>
                <span class="sa-count">{{ $pengumumanList->total() }} pengumuman</span>
            </div>
            <div class="table-responsive">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 220px;">Judul</th>
                            <th>Kategori</th>
                            <th class="text-center">Prioritas</th>
                            <th class="text-center">Status</th>
                            <th>Target</th>
                            <th class="text-center">Penerima</th>
                            <th style="min-width: 190px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pengumumanList as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->judul }}</div>
                                    <small class="text-body-secondary"><i class="fa-regular fa-calendar me-1" aria-hidden="true"></i>{{ $item->waktu_terbit?->format('d/m/Y H:i') ?? 'Belum terbit' }}</small>
                                </td>
                                <td><span class="sa-badge sa-netral">{{ ucfirst($item->kategori) }}</span></td>
                                <td class="text-center">
                                    @if ($item->prioritas === 'mendesak')
                                        <span class="sa-badge sa-alpha">Mendesak</span>
                                    @elseif ($item->prioritas === 'tinggi')
                                        <span class="sa-badge sa-sakit">Tinggi</span>
                                    @elseif ($item->prioritas === 'sedang')
                                        <span class="sa-badge sa-izin">Sedang</span>
                                    @else
                                        <span class="sa-badge sa-netral">Rendah</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($item->status === 'terbit')
                                        <span class="sa-badge sa-hadir">Terbit</span>
                                    @elseif ($item->status === 'arsip')
                                        <span class="sa-badge sa-netral">Arsip</span>
                                    @else
                                        <span class="sa-badge sa-sakit">Draf</span>
                                    @endif
                                </td>
                                <td><span class="sa-chip">{{ ucfirst($item->target) }}</span></td>
                                <td class="text-center">{{ $item->baca_count ?? 0 }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="detailPengumuman({{ $item->idpengumuman }})" aria-label="Lihat detail {{ $item->judul }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat detail">Detail</button>
                                        @if ($item->status === 'draf')
                                            <button class="btn btn-sm btn-outline-warning" wire:click="editPengumuman({{ $item->idpengumuman }})" aria-label="Edit draf {{ $item->judul }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data">Edit</button>
                                            <button class="btn btn-sm btn-outline-success" wire:click="terbitkan({{ $item->idpengumuman }})" wire:confirm="Terbitkan pengumuman ini untuk penerima terkait?" wire:loading.attr="disabled" aria-label="Terbitkan {{ $item->judul }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Terbitkan">Terbitkan</button>
                                        @endif
                                        @if ($item->status === 'terbit')
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="arsipkan({{ $item->idpengumuman }})" wire:confirm="Arsipkan pengumuman ini? Riwayat baca dipertahankan." wire:loading.attr="disabled" aria-label="Arsipkan {{ $item->judul }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Arsipkan">Arsipkan</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="sa-card-foot">
                <small class="text-body-secondary">Menampilkan {{ $pengumumanList->firstItem() }}&ndash;{{ $pengumumanList->lastItem() }} dari {{ $pengumumanList->total() }} pengumuman</small>
                <div>{{ $pengumumanList->links() }}</div>
            </div>
        </div>
    @else
        <div class="sa-card">
            <div class="sa-card-body sa-empty">
                <i class="fa-regular fa-clipboard" aria-hidden="true"></i>
                <p class="sa-empty-title">Belum ada pengumuman</p>
                <p class="sa-empty-text">
                    @if ($search || $filterStatus || $filterKategori || $filterPrioritas)
                        Tidak ada pengumuman dengan filter yang dipilih. Coba ubah atau reset filter.
                    @else
                        Belum ada pengumuman yang dibuat.
                    @endif
                </p>
                @if ($search || $filterStatus || $filterKategori || $filterPrioritas)
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-3" wire:click="resetFilter" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset filter">Reset filter</button>
                @endif
            </div>
        </div>
    @endif

    {{-- Modal tambah/edit --}}
    <div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanModalTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pengumumanModalTitle"><i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>{{ $editId ? 'Edit Pengumuman (Draf)' : 'Tambah Pengumuman (Draf)' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="judul">Judul <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" id="judul" wire:model.live="judul" class="form-control @error('judul') is-invalid @enderror" maxlength="255" required>
                        @error('judul') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="isi">Isi Pengumuman <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea id="isi" wire:model.live="isi" class="form-control @error('isi') is-invalid @enderror" rows="4" required></textarea>
                        @error('isi') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="kategori">Kategori <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="kategori" wire:model.live="kategori" class="form-select @error('kategori') is-invalid @enderror">
                                <option value="akademik">Akademik</option>
                                <option value="acara">Acara</option>
                                <option value="penting">Penting</option>
                                <option value="darurat">Darurat</option>
                            </select>
                            @error('kategori') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="prioritas">Prioritas <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="prioritas" wire:model.live="prioritas" class="form-select @error('prioritas') is-invalid @enderror">
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="mendesak">Mendesak</option>
                            </select>
                            @error('prioritas') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="target">Jenis Target <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="target" wire:model.live="target" class="form-select @error('target') is-invalid @enderror">
                            <option value="semua">Semua siswa aktif</option>
                            <option value="jurusan">Jurusan tertentu</option>
                            <option value="kelas">Kelas tertentu</option>
                            <option value="siswa">Siswa tertentu</option>
                        </select>
                        @error('target') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                    </div>

                    @if($target === 'jurusan')
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="jurusanIds">Jurusan <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="jurusanIds" wire:model.live="jurusanIds" class="form-select @error('jurusanIds') is-invalid @enderror" multiple size="5">
                                @foreach ($jurusanList as $jurusan)
                                    <option value="{{ $jurusan->idjurusan }}">{{ $jurusan->nama }}</option>
                                @endforeach
                            </select>
                            @error('jurusanIds') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                            <div class="form-text">Ctrl+klik untuk memilih lebih dari satu.</div>
                        </div>
                    @endif

                    @if($target === 'kelas')
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="kelasIds">Kelas <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="kelasIds" wire:model.live="kelasIds" class="form-select @error('kelasIds') is-invalid @enderror" multiple size="5">
                                @foreach ($kelasList as $kelas)
                                    <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }}</option>
                                @endforeach
                            </select>
                            @error('kelasIds') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                            <div class="form-text">Ctrl+klik untuk memilih lebih dari satu.</div>
                        </div>
                    @endif

                    @if($target === 'siswa')
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="lov-siswa-search">Siswa <span class="text-danger" aria-hidden="true">*</span></label>
                            @include('livewire.partials.lov-siswa', ['lovMode' => 'multi', 'siswaTerpilih' => $siswaTerpilih, 'siswaLabel' => ''])
                            @error('siswaIds') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="berlakuDari">Berlaku dari</label>
                            <input type="date" id="berlakuDari" wire:model.live="berlakuDari" class="form-control @error('berlakuDari') is-invalid @enderror">
                            @error('berlakuDari') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="berlakuSampai">Berlaku sampai</label>
                            <input type="date" id="berlakuSampai" wire:model.live="berlakuSampai" class="form-control @error('berlakuSampai') is-invalid @enderror">
                            @error('berlakuSampai') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <p class="sa-hint mt-2 mb-0">Pengumuman terbit tidak dapat diedit atau dikembalikan menjadi draf. Untuk revisi, buat pengumuman baru.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                    <button type="button" class="btn btn-success" wire:click="storePengumuman" wire:loading.attr="disabled" wire:target="storePengumuman" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                        <span wire:loading.remove wire:target="storePengumuman">Simpan Draf</span>
                        <span wire:loading wire:target="storePengumuman"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyimpan&hellip;</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal detail --}}
    <div class="modal fade" id="detailPengumumanModal" tabindex="-1" aria-labelledby="detailPengumumanModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailPengumumanModalLabel"><i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>Detail Pengumuman</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup"></button>
                </div>
                <div class="modal-body">
                    @if($detail)
                        <h5 class="mb-2">{{ $detail->judul }}</h5>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="sa-badge sa-netral">{{ ucfirst($detail->kategori) }}</span>
                            @if ($detail->prioritas === 'mendesak')
                                <span class="sa-badge sa-alpha">Mendesak</span>
                            @elseif ($detail->prioritas === 'tinggi')
                                <span class="sa-badge sa-sakit">Tinggi</span>
                            @elseif ($detail->prioritas === 'sedang')
                                <span class="sa-badge sa-izin">Sedang</span>
                            @else
                                <span class="sa-badge sa-netral">Rendah</span>
                            @endif
                            @if ($detail->status === 'terbit')
                                <span class="sa-badge sa-hadir">Terbit</span>
                            @elseif ($detail->status === 'arsip')
                                <span class="sa-badge sa-netral">Arsip</span>
                            @else
                                <span class="sa-badge sa-sakit">Draf</span>
                            @endif
                            <span class="sa-chip">Target {{ ucfirst($detail->target) }}</span>
                        </div>
                        <div class="mb-3" style="white-space: pre-wrap;">{{ $detail->isi }}</div>
                        <table class="table sa-table table-bordered mb-3">
                            <tbody>
                                <tr><th class="table-light" style="width: 38%;">Waktu terbit</th><td>{{ $detail->waktu_terbit?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                                <tr><th class="table-light">Masa berlaku</th><td>{{ $detail->berlaku_dari?->format('d/m/Y') ?? '-' }} &ndash; {{ $detail->berlaku_sampai?->format('d/m/Y') ?? '-' }}</td></tr>
                                <tr><th class="table-light">Jumlah penerima</th><td>{{ $detail->baca_count ?? 0 }} siswa</td></tr>
                                <tr><th class="table-light">Dibuat oleh</th><td>{{ $detail->pembuat?->username ?? '-' }}</td></tr>
                                <tr><th class="table-light">Diperbarui oleh</th><td>{{ $detail->pengubah?->username ?? '-' }}</td></tr>
                            </tbody>
                        </table>
                        @if($detail->targets->isNotEmpty())
                            <div>
                                <strong class="sa-hint d-block mb-1">Target terkait</strong>
                                @foreach ($detail->targets as $t)
                                    @if($t->jurusan) <span class="sa-chip">{{ $t->jurusan->nama }}</span> @endif
                                    @if($t->kelas) <span class="sa-chip">{{ $t->kelas->nama }}</span> @endif
                                    @if($t->siswa) <span class="sa-chip">{{ $t->siswa->nama }}</span> @endif
                                @endforeach
                            </div>
                        @endif
                    @else
                        <p class="text-body-secondary mb-0"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memuat detail&hellip;</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="tutupDetail" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

{{-- Listener modal: pola SIAKAD (event Livewire -> jQuery/Bootstrap). --}}
@script
    <script>
        $wire.on('show-pengumuman-modal', () => {
            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('pengumumanModal')).show();
            }
        });
        $wire.on('close-pengumuman-modal', () => {
            if (window.bootstrap?.Modal) {
                const el = document.getElementById('pengumumanModal');
                const instance = window.bootstrap.Modal.getInstance(el);
                if (instance) instance.hide();
            }
        });
        $wire.on('show-detail-pengumuman-modal', () => {
            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('detailPengumumanModal')).show();
            }
        });
    </script>
@endscript
