@section('title', 'Pengumuman')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Pengumuman</h1>
            <p class="text-body-secondary mb-0">
                Kelola pengumuman sekolah untuk siswa. Pengumuman terbit tidak dapat diedit atau dikembalikan menjadi draf.
            </p>
        </div>
        <button class="btn btn-success btn-sm" wire:click="tambahPengumuman">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i> Tambah Pengumuman
        </button>
    </div>

    <div class="card card-body shadow-sm mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="cari" class="form-label mb-1 small fw-semibold">Cari judul/isi</label>
                <input type="text" id="cari" wire:model.live="search" class="form-control form-control-sm" placeholder="Ketikan kata kunci...">
            </div>
            <div class="col-md-2">
                <label for="filter-status" class="form-label mb-1 small fw-semibold">Status</label>
                <select id="filter-status" wire:model.live="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="draf">Draf</option>
                    <option value="terbit">Terbit</option>
                    <option value="arsip">Arsip</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-kategori" class="form-label mb-1 small fw-semibold">Kategori</label>
                <select id="filter-kategori" wire:model.live="filterKategori" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="akademik">Akademik</option>
                    <option value="acara">Acara</option>
                    <option value="penting">Penting</option>
                    <option value="darurat">Darurat</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-prioritas" class="form-label mb-1 small fw-semibold">Prioritas</label>
                <select id="filter-prioritas" wire:model.live="filterPrioritas" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="rendah">Rendah</option>
                    <option value="sedang">Sedang</option>
                    <option value="tinggi">Tinggi</option>
                    <option value="mendesak">Mendesak</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetFilter">Reset filter</button>
            </div>
        </div>
    </div>

    @if ($pengumumanList->total() > 0)
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 200px;">Judul</th>
                            <th>Kategori</th>
                            <th class="text-center">Prioritas</th>
                            <th class="text-center">Status</th>
                            <th>Target</th>
                            <th class="text-center">Penerima</th>
                            <th style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pengumumanList as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->judul }}</div>
                                    <small class="text-body-secondary">Terbit: {{ $item->waktu_terbit?->format('d/m/Y H:i') ?? '-' }}</small>
                                </td>
                                <td>{{ ucfirst($item->kategori) }}</td>
                                <td class="text-center">
                                    @if ($item->prioritas === 'mendesak')
                                        <span class="badge bg-danger">Mendesak</span>
                                    @elseif ($item->prioritas === 'tinggi')
                                        <span class="badge bg-warning text-dark">Tinggi</span>
                                    @elseif ($item->prioritas === 'sedang')
                                        <span class="badge bg-info text-dark">Sedang</span>
                                    @else
                                        <span class="badge bg-secondary">Rendah</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($item->status === 'terbit')
                                        <span class="badge bg-success">Terbit</span>
                                    @elseif ($item->status === 'arsip')
                                        <span class="badge bg-secondary">Arsip</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Draf</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($item->target) }}</td>
                                <td class="text-center">{{ $item->baca_count ?? 0 }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="detailPengumuman({{ $item->idpengumuman }})">Detail</button>
                                        @if ($item->status === 'draf')
                                            <button class="btn btn-sm btn-outline-warning" wire:click="editPengumuman({{ $item->idpengumuman }})">Edit</button>
                                            <button class="btn btn-sm btn-outline-success"
                                                    wire:click="terbitkan({{ $item->idpengumuman }})"
                                                    onclick="return confirm('Terbitkan pengumuman ini untuk penerima terkait?')">
                                                Terbitkan
                                            </button>
                                        @endif
                                        @if ($item->status === 'terbit')
                                            <button class="btn btn-sm btn-outline-secondary"
                                                    wire:click="arsipkan({{ $item->idpengumuman }})"
                                                    onclick="return confirm('Arsipkan pengumuman ini? Riwayat baca tetap dipertahankan.')">
                                                Arsipkan
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Menampilkan {{ $pengumumanList->firstItem() }}–{{ $pengumumanList->lastItem() }} dari {{ $pengumumanList->total() }} pengumuman</small>
                <div>{{ $pengumumanList->links() }}</div>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa-regular fa-clipboard fs-2 text-secondary mb-2" aria-hidden="true"></i>
                <p class="fw-semibold mb-1">Belum ada pengumuman</p>
                <p class="text-body-secondary mb-0">
                    @if ($search || $filterStatus || $filterKategori || $filterPrioritas)
                        Tidak ada pengumuman dengan filter yang dipilih. Coba ubah atau reset filter.
                    @else
                        Belum ada pengumuman yang dibuat.
                    @endif
                </p>
                @if ($search || $filterStatus || $filterKategori || $filterPrioritas)
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-3" wire:click="resetFilter">Reset filter</button>
                @endif
            </div>
        </div>
    @endif

    {{-- Modal tambah/edit --}}
    <div class="modal fade" id="pengumumanModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="h6 fw-bold mb-0">{{ $editId ? 'Edit Pengumuman (Draf)' : 'Tambah Pengumuman (Draf)' }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="judul">Judul <span class="text-danger">*</span></label>
                        <input type="text" id="judul" wire:model.live="judul" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="isi">Isi <span class="text-danger">*</span></label>
                        <textarea id="isi" wire:model.live="isi" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="kategori">Kategori</label>
                            <select id="kategori" wire:model.live="kategori" class="form-select">
                                <option value="akademik">Akademik</option>
                                <option value="acara">Acara</option>
                                <option value="penting">Penting</option>
                                <option value="darurat">Darurat</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="prioritas">Prioritas</label>
                            <select id="prioritas" wire:model.live="prioritas" class="form-select">
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="mendesak">Mendesak</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="target">Jenis Target <span class="text-danger">*</span></label>
                        <select id="target" wire:model.live="target" class="form-select">
                            <option value="semua">Semua siswa aktif</option>
                            <option value="jurusan">Jurusan tertentu</option>
                            <option value="kelas">Kelas tertentu</option>
                            <option value="siswa">Siswa tertentu</option>
                        </select>
                    </div>

                    @if($target === 'jurusan')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="jurusanIds">Jurusan <span class="text-danger">*</span></label>
                            <select id="jurusanIds" wire:model.live="jurusanIds" class="form-select" multiple size="5">
                                @foreach ($jurusanList as $jurusan)
                                    <option value="{{ $jurusan->idjurusan }}">{{ $jurusan->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($target === 'kelas')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="kelasIds">Kelas <span class="text-danger">*</span></label>
                            <select id="kelasIds" wire:model.live="kelasIds" class="form-select" multiple size="5">
                                @foreach ($kelasList as $kelas)
                                    <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($target === 'siswa')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="siswaIds">Siswa (Ctrl+klik untuk multi) <span class="text-danger">*</span></label>
                            <select id="siswaIds" wire:model.live="siswaIds" class="form-select" multiple size="5">
                                @foreach ($siswaList as $siswa)
                                    <option value="{{ $siswa->idsiswa }}">{{ $siswa->nama }} ({{ $siswa->nisn }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="berlakuDari">Berlaku dari</label>
                            <input type="date" id="berlakuDari" wire:model.live="berlakuDari" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="berlakuSampai">Berlaku sampai</label>
                            <input type="date" id="berlakuSampai" wire:model.live="berlakuSampai" class="form-control">
                        </div>
                    </div>
                    <div class="form-text mt-2">Pengumuman yang sudah terbit tidak dapat diedit atau dikembalikan menjadi draf. Untuk revisi, buat pengumuman baru.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" wire:click="storePengumuman">Simpan sebagai Draf</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal detail tetap berada di DOM agar Livewire tidak menghapus elemen saat transisi Bootstrap. --}}
    <div class="modal fade" id="detailPengumumanModal" tabindex="-1" aria-labelledby="detailPengumumanModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                @if($detail)
                    <div class="modal-header bg-success-subtle border-bottom px-3 px-md-4 py-3">
                        <div class="pe-3">
                            <div class="small text-success-emphasis fw-semibold mb-1">Detail Pengumuman</div>
                            <h2 class="h5 fw-bold mb-0" id="detailPengumumanModalLabel">{{ $detail->judul }}</h2>
                        </div>
                        <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-3 p-md-4">
                        <div class="d-flex flex-wrap gap-2 mb-4" aria-label="Kategori, prioritas, dan target">
                            @php
                                $kategoriBadge = match ($detail->kategori) {
                                    'darurat' => 'bg-danger',
                                    'penting' => 'bg-warning text-dark',
                                    'acara' => 'bg-primary',
                                    default => 'bg-success',
                                };
                                $prioritasBadge = match ($detail->prioritas) {
                                    'mendesak' => 'bg-danger',
                                    'tinggi' => 'bg-warning text-dark',
                                    'sedang' => 'bg-info text-dark',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge rounded-pill {{ $kategoriBadge }}">{{ ucfirst($detail->kategori) }}</span>
                            <span class="badge rounded-pill {{ $prioritasBadge }}">Prioritas {{ ucfirst($detail->prioritas) }}</span>
                            <span class="badge rounded-pill bg-light text-dark border">Target {{ ucfirst($detail->target) }}</span>
                        </div>

                        <section class="mb-4" aria-labelledby="isiPengumumanHeading">
                            <h3 class="small text-body-secondary text-uppercase fw-bold mb-2" id="isiPengumumanHeading">Isi Pengumuman</h3>
                            <div class="text-break" style="white-space: pre-wrap;">{{ $detail->isi }}</div>
                        </section>

                        <section aria-labelledby="informasiPengumumanHeading">
                            <h3 class="small text-body-secondary text-uppercase fw-bold mb-2" id="informasiPengumumanHeading">Informasi</h3>
                            <dl class="row g-2 mb-0">
                                <div class="col-sm-6">
                                    <div class="h-100 rounded bg-light p-3">
                                        <dt class="small text-body-secondary fw-semibold mb-1">Status</dt>
                                        <dd class="mb-0">
                                            @if ($detail->status === 'terbit')
                                                <span class="badge bg-success">Terbit</span>
                                            @elseif ($detail->status === 'arsip')
                                                <span class="badge bg-secondary">Arsip</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Draf</span>
                                            @endif
                                        </dd>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="h-100 rounded bg-light p-3">
                                        <dt class="small text-body-secondary fw-semibold mb-1">Waktu terbit</dt>
                                        <dd class="mb-0">{{ $detail->waktu_terbit?->format('d/m/Y H:i') ?? '-' }}</dd>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="h-100 rounded bg-light p-3">
                                        <dt class="small text-body-secondary fw-semibold mb-1">Masa berlaku</dt>
                                        <dd class="mb-0">{{ $detail->berlaku_dari?->format('d/m/Y') ?? '-' }} – {{ $detail->berlaku_sampai?->format('d/m/Y') ?? '-' }}</dd>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="h-100 rounded bg-light p-3">
                                        <dt class="small text-body-secondary fw-semibold mb-1">Jumlah penerima</dt>
                                        <dd class="mb-0">{{ $detail->baca_count ?? 0 }} siswa</dd>
                                    </div>
                                </div>
                            </dl>
                        </section>

                        @if($detail->targets->isNotEmpty())
                            <section class="mt-4" aria-labelledby="targetTerkaitHeading">
                                <h3 class="small text-body-secondary text-uppercase fw-bold mb-2" id="targetTerkaitHeading">Target Terkait</h3>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($detail->targets as $t)
                                        @if($t->jurusan)
                                            <span class="badge bg-light text-dark border">{{ $t->jurusan->nama }}</span>
                                        @endif
                                        @if($t->kelas)
                                            <span class="badge bg-light text-dark border">{{ $t->kelas->nama }}</span>
                                        @endif
                                        @if($t->siswa)
                                            <span class="badge bg-light text-dark border">{{ $t->siswa->nama }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                    <div class="modal-footer bg-light px-3 px-md-4 py-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @script
        <script>
            const detailModalElement = document.getElementById('detailPengumumanModal');
            let detailBodyStyleBeforeShow = null;

            const detailModalInstance = () => {
                if (!window.bootstrap?.Modal) {
                    console.error('Bootstrap Modal tidak tersedia untuk Detail Pengumuman.');
                    return null;
                }

                return window.bootstrap.Modal.getOrCreateInstance(detailModalElement);
            };

            $wire.on('show-pengumuman-modal', () => {
                const element = document.getElementById('pengumumanModal');
                if (!window.bootstrap?.Modal) {
                    console.error('Bootstrap Modal tidak tersedia untuk Pengumuman.');
                    return;
                }

                if (element) window.bootstrap.Modal.getOrCreateInstance(element).show();
            });

            $wire.on('close-pengumuman-modal', () => {
                const element = document.getElementById('pengumumanModal');
                if (element && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getInstance(element)?.hide();
                }
            });

            $wire.on('show-detail-pengumuman-modal', () => {
                if (!detailModalElement) return;

                detailBodyStyleBeforeShow = {
                    overflow: document.body.style.overflow,
                    paddingRight: document.body.style.paddingRight,
                };
                detailModalInstance()?.show();
            });

            detailModalElement?.addEventListener('hidden.bs.modal', () => {
                const anotherModalIsOpen = document.querySelector('.modal.show:not(#detailPengumumanModal)');

                if (anotherModalIsOpen) {
                    document.body.classList.add('modal-open');
                    if (detailBodyStyleBeforeShow) {
                        document.body.style.overflow = detailBodyStyleBeforeShow.overflow;
                        document.body.style.paddingRight = detailBodyStyleBeforeShow.paddingRight;
                    }
                } else {
                    document.body.classList.remove('modal-open');
                    if (detailBodyStyleBeforeShow) {
                        document.body.style.overflow = detailBodyStyleBeforeShow.overflow;
                        document.body.style.paddingRight = detailBodyStyleBeforeShow.paddingRight;
                    }
                }

                detailBodyStyleBeforeShow = null;
                $wire.tutupDetail();
            });
        </script>
    @endscript
</div>
