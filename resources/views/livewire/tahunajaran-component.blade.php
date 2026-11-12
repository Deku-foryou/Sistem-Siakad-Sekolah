@section('title')
    Manajemen Tahun Ajaran
@endsection

<div class="academic-management">
    <style>
    .academic-management {
        --academic-primary: #0e7d6a;
        --academic-primary-soft: #e8f5f1;
        --academic-border: #e4e9e7;
        --academic-muted: #697773;
        color: #23312d;
        max-width: 1440px;
        margin: 0 auto;
        padding: 2rem clamp(1rem, 2.5vw, 2rem) 3rem;
    }

    .academic-management .academic-breadcrumb {
        font-size: .82rem;
    }

    .academic-management .academic-breadcrumb a {
        color: var(--academic-muted);
        text-decoration: none;
    }

    .academic-management .academic-breadcrumb a:hover {
        color: var(--academic-primary);
    }

    .academic-management .academic-title {
        color: #1d2c28;
        font-size: clamp(1.55rem, 2.6vw, 2rem);
        font-weight: 700;
        letter-spacing: -.035em;
    }

    .academic-management .academic-description,
    .academic-management .academic-muted {
        color: var(--academic-muted);
    }

    .academic-management .btn-academic {
        background: var(--academic-primary);
        border-color: var(--academic-primary);
        color: #fff;
        font-weight: 600;
    }

    .academic-management .btn-academic:hover,
    .academic-management .btn-academic:focus {
        background: #0b6758;
        border-color: #0b6758;
        color: #fff;
    }

    .academic-management .academic-context,
    .academic-management .academic-list,
    .academic-management .academic-year-card {
        border: 1px solid var(--academic-border);
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 .25rem 1rem rgba(27, 54, 45, .045);
    }

    .academic-management .academic-context {
        border-left: 4px solid var(--academic-primary);
    }

    .academic-management .academic-context.is-invalid {
        border-left-color: #d68a20;
    }

    .academic-management .context-label {
        color: var(--academic-muted);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .055em;
        text-transform: uppercase;
    }

    .academic-management .context-value {
        color: #1d2c28;
        font-size: 1.05rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .academic-management .context-divider {
        border-left: 1px solid var(--academic-border);
    }

    .academic-management .badge-academic-active {
        background: #e5f5ec;
        color: #176b3a;
    }

    .academic-management .badge-academic-inactive {
        background: #eef0f1;
        color: #58636a;
    }

    .academic-management .badge-academic-warning {
        background: #fff2d6;
        color: #82530a;
    }

    .academic-management .academic-list-heading {
        color: #24332f;
        font-size: 1.08rem;
        font-weight: 700;
    }

    .academic-management .academic-search {
        max-width: 340px;
    }

    .academic-management .academic-search .input-group {
        border: 1px solid var(--academic-border);
        border-radius: .7rem;
        background: #fff;
    }

    .academic-management .academic-search .input-group-text,
    .academic-management .academic-search .form-control {
        border: 0;
        background: transparent;
        box-shadow: none;
    }

    .academic-management .academic-year-card {
        overflow: hidden;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .academic-management .academic-year-card.is-active {
        border-color: #b7dacf;
    }

    .academic-management .academic-year-card:hover {
        box-shadow: 0 .45rem 1.25rem rgba(27, 54, 45, .075);
    }

    .academic-management .academic-year-header {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.15rem 1.25rem;
    }

    .academic-management .academic-year-heading {
        display: flex;
        min-width: 0;
        flex: 1 1 auto;
        align-items: center;
        gap: .75rem;
    }

    .academic-management .academic-year-name {
        margin: 0;
        color: #24332f;
        font-size: 1.04rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .academic-management .academic-year-meta {
        color: var(--academic-muted);
        font-size: .82rem;
        white-space: nowrap;
    }

    .academic-management .academic-year-actions {
        display: flex;
        flex: 0 0 auto;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: .45rem;
    }

    .academic-management .academic-icon-button {
        display: inline-flex;
        width: 2.25rem;
        height: 2.25rem;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .academic-management .academic-expand-button[aria-expanded="true"] .expand-icon {
        transform: rotate(180deg);
    }

    .academic-management .expand-icon {
        transition: transform .2s ease;
    }

    .academic-management .academic-year-body {
        border-top: 1px solid var(--academic-border);
        background: #fbfcfb;
        padding: .5rem 1.25rem 1.25rem;
    }

    .academic-management .academic-semester-list {
        display: grid;
        gap: .65rem;
    }

    .academic-management .academic-semester-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid var(--academic-border);
        border-radius: .75rem;
        background: #fff;
        padding: .85rem 1rem;
    }

    .academic-management .academic-semester-info,
    .academic-management .academic-semester-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .65rem;
    }

    .academic-management .academic-semester-name {
        min-width: 6rem;
        margin: 0;
        color: #2b3935;
        font-size: .95rem;
        font-weight: 650;
    }

    .academic-management .academic-empty {
        border: 1px dashed #cbd6d1;
        border-radius: .75rem;
        background: #fff;
        color: var(--academic-muted);
        padding: 1.25rem;
        text-align: center;
    }

    .academic-management .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 1.25rem 3rem rgba(22, 38, 33, .18);
    }

    .academic-management .modal-header {
        align-items: flex-start;
        border-bottom-color: var(--academic-border);
        padding: 1.2rem 1.35rem;
    }

    .academic-management .modal-title {
        color: #24332f;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .academic-management .modal-description {
        margin: .25rem 0 0;
        color: var(--academic-muted);
        font-size: .88rem;
    }

    .academic-management .modal-body {
        padding: 1.25rem 1.35rem;
    }

    .academic-management .modal-footer {
        gap: .5rem;
        border-top: 0;
        padding-top: .5rem;
    }

    .academic-management .form-label {
        color: #34443f;
        font-size: .88rem;
        font-weight: 650;
    }

    .academic-management .form-control,
    .academic-management .form-select {
        min-height: 2.75rem;
        border-color: #d8e0dc;
        border-radius: .65rem;
    }

    .academic-management .form-control:focus,
    .academic-management .form-select:focus {
        border-color: #79b9a9;
        box-shadow: 0 0 0 .2rem rgba(14, 125, 106, .12);
    }

    @media (max-width: 767.98px) {
        .academic-management {
            padding: 1.25rem .85rem 2rem;
        }

        .academic-management .academic-context {
            padding: 1rem !important;
        }

        .academic-management .context-divider {
            border-left: 0;
            border-top: 1px solid var(--academic-border);
            padding-top: .8rem;
        }

        .academic-management .academic-search {
            max-width: none;
            width: 100%;
        }

        .academic-management .academic-year-header {
            align-items: stretch;
            flex-direction: column;
            gap: .8rem;
            padding: 1rem;
        }

        .academic-management .academic-year-heading {
            flex-wrap: wrap;
            gap: .5rem;
        }

        .academic-management .academic-year-meta {
            width: 100%;
        }

        .academic-management .academic-year-actions {
            justify-content: flex-start;
        }

        .academic-management .academic-year-body {
            padding: .85rem 1rem 1rem;
        }

        .academic-management .academic-semester-row {
            align-items: flex-start;
            flex-direction: column;
            gap: .8rem;
            padding: .85rem;
        }

        .academic-management .academic-semester-actions {
            width: 100%;
        }

        .academic-management .academic-semester-actions .btn {
            flex: 1 1 auto;
        }
    }

    @media (max-width: 399.98px) {
        .academic-management .academic-header-actions .btn {
            width: 100%;
        }

        .academic-management .academic-year-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) repeat(3, 2.25rem);
        }

        .academic-management .academic-year-actions .btn-add-semester {
            justify-content: center;
        }

        .academic-management .academic-semester-actions .btn {
            flex-basis: 100%;
        }

        .academic-management .modal-footer {
            flex-direction: column-reverse;
            align-items: stretch;
        }
    }
</style>

    <header class="mb-4">
        <nav aria-label="breadcrumb" class="academic-breadcrumb mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ \App\Support\BerandaUrl::untuk(auth()->user()) }}">Beranda</a></li>
                <li class="breadcrumb-item">Akademik</li>
                <li class="breadcrumb-item active" aria-current="page">Manajemen Tahun Ajaran</li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="academic-title mb-1">Manajemen Tahun Ajaran</h1>
                <p class="academic-description mb-0">Kelola tahun ajaran dan semester sekolah dalam satu tempat.</p>
            </div>
            <div class="academic-header-actions">
                <button type="button" class="btn btn-academic px-3 py-2" wire:click="$dispatch('show-add-tahunajaran-modal')">
                    <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Tambah Tahun Ajaran
                </button>
            </div>
        </div>
    </header>

    <section class="academic-context {{ $konteks->valid() ? '' : 'is-invalid' }} p-3 p-md-4 mb-4" aria-labelledby="context-heading">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
            <div>
                <h2 id="context-heading" class="h6 fw-bold mb-1">Konteks Akademik</h2>
                <p class="academic-muted small mb-0">Periode yang digunakan oleh modul akademik saat ini.</p>
            </div>
            @if ($konteks->valid())
                <span class="badge rounded-pill badge-academic-active px-3 py-2">
                    <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Konteks valid
                </span>
            @else
                <span class="badge rounded-pill badge-academic-warning px-3 py-2">
                    <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Konteks belum valid
                </span>
            @endif
        </div>

        @if ($konteks->valid())
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="context-label mb-1">Tahun ajaran aktif</div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="context-value">{{ $konteks->tahunAjaranNama() }}</span>
                        <span class="badge rounded-pill badge-academic-active">Aktif</span>
                    </div>
                </div>
                <div class="col-12 col-md-6 context-divider">
                    <div class="context-label mb-1">Semester aktif</div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="context-value">Semester {{ $konteks->semesterNama() }}</span>
                        <span class="badge rounded-pill badge-academic-active">Aktif</span>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning mb-0 py-2" role="alert">
                @foreach ($konteks->pesanPeringatan() as $pesan)
                    <div>{{ $pesan }}</div>
                @endforeach
                @if ($konteks->semester && (! $konteks->tahunAjaran || (int) $konteks->semester->tahunajaran_idtahunajaran !== (int) $konteks->tahunAjaran->idtahunajaran))
                    <div class="small mt-1">Gunakan tombol Sinkronkan Konteks pada semester yang sesuai untuk memperbaiki periode aktif.</div>
                @endif
            </div>
        @endif
    </section>

    <section class="academic-list p-3 p-md-4" aria-labelledby="years-heading">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h2 id="years-heading" class="academic-list-heading mb-1">Daftar Tahun Ajaran</h2>
                <div class="academic-muted small">{{ $tahunajarans->total() }} tahun ajaran terdaftar</div>
            </div>
            <div class="academic-search">
                <label for="search-tahunajaran" class="visually-hidden">Cari tahun ajaran</label>
                <div class="input-group">
                    <span class="input-group-text ps-3">
                        <i class="fa-solid fa-magnifying-glass academic-muted" aria-hidden="true"></i>
                    </span>
                    <input
                        id="search-tahunajaran"
                        type="search"
                        class="form-control py-2"
                        placeholder="Cari tahun ajaran..."
                        aria-label="Cari tahun ajaran"
                        wire:model.live.debounce.300ms="search"
                    >
                </div>
            </div>
        </div>

        @if ($tahunajarans->count() > 0)
            <div class="accordion d-grid gap-3" id="accordionTahunAjaran">
                @foreach ($tahunajarans as $ta)
                    <article
                        class="academic-year-card {{ $ta->aktif ? 'is-active' : '' }}"
                        wire:key="ta-card-{{ $ta->idtahunajaran }}"
                    >
                        <div class="academic-year-header">
                            <button
                                type="button"
                                class="btn btn-link academic-year-heading text-start text-decoration-none p-0"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse-{{ $ta->idtahunajaran }}"
                                aria-expanded="{{ $ta->aktif ? 'true' : 'false' }}"
                                aria-controls="collapse-{{ $ta->idtahunajaran }}"
                            >
                                <span class="academic-year-name">Tahun Ajaran {{ $ta->nama }}</span>
                                @if ($ta->aktif)
                                    <span class="badge rounded-pill badge-academic-active">Aktif</span>
                                @else
                                    <span class="badge rounded-pill badge-academic-inactive">Nonaktif</span>
                                @endif
                                <span class="academic-year-meta">
                                    <i class="fa-solid fa-layer-group me-1" aria-hidden="true"></i>{{ $ta->semesters->count() }} semester
                                </span>
                            </button>

                            <div class="academic-year-actions">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-success btn-add-semester"
                                    wire:click="addSemester('{{ $ta->idtahunajaran }}')"
                                    title="Tambah semester pada tahun ajaran {{ $ta->nama }}"
                                    aria-label="Tambah semester pada tahun ajaran {{ $ta->nama }}"
                                >
                                    <i class="fa-solid fa-plus me-sm-1" aria-hidden="true"></i><span>Tambah Semester</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary academic-icon-button"
                                    wire:click="editTahunajaran('{{ $ta->idtahunajaran }}')"
                                    title="Edit tahun ajaran {{ $ta->nama }}"
                                    aria-label="Edit tahun ajaran {{ $ta->nama }}"
                                >
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger academic-icon-button"
                                    wire:click="deleteConfirmationTahunajaran('{{ $ta->idtahunajaran }}')"
                                    title="Hapus tahun ajaran {{ $ta->nama }}"
                                    aria-label="Hapus tahun ajaran {{ $ta->nama }}"
                                >
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light border academic-icon-button academic-expand-button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse-{{ $ta->idtahunajaran }}"
                                    aria-expanded="{{ $ta->aktif ? 'true' : 'false' }}"
                                    aria-controls="collapse-{{ $ta->idtahunajaran }}"
                                    title="Tampilkan atau sembunyikan semester"
                                    aria-label="Tampilkan atau sembunyikan semester tahun ajaran {{ $ta->nama }}"
                                >
                                    <i class="fa-solid fa-chevron-down expand-icon" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div
                            id="collapse-{{ $ta->idtahunajaran }}"
                            class="collapse {{ $ta->aktif ? 'show' : '' }}"
                            data-bs-parent="#accordionTahunAjaran"
                        >
                            <div class="academic-year-body">
                                @if ($ta->semesters->isNotEmpty())
                                    <div class="academic-semester-list">
                                        @foreach ($ta->semesters as $sem)
                                            @php
                                                $semesterKonteksAktif = $konteks->valid()
                                                    && $konteks->semester
                                                    && (int) $konteks->semester->idsemester === (int) $sem->idsemester;
                                                $semesterPerluSinkron = $sem->aktif && ! $semesterKonteksAktif;
                                            @endphp
                                            <div class="academic-semester-row" wire:key="sem-row-{{ $sem->idsemester }}">
                                                <div class="academic-semester-info">
                                                    <h3 class="academic-semester-name">Semester {{ $sem->nama }}</h3>
                                                    @if ($semesterKonteksAktif)
                                                        <span class="badge rounded-pill badge-academic-active">
                                                            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Aktif
                                                        </span>
                                                    @elseif ($semesterPerluSinkron)
                                                        <span class="badge rounded-pill badge-academic-warning">Perlu sinkronisasi</span>
                                                    @else
                                                        <span class="badge rounded-pill badge-academic-inactive">Nonaktif</span>
                                                    @endif
                                                </div>
                                                <div class="academic-semester-actions">
                                                    @if ($semesterPerluSinkron)
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-warning"
                                                            wire:click="aktifkanSemester('{{ $sem->idsemester }}')"
                                                            wire:confirm="Sinkronkan konteks dengan mengaktifkan semester ini dan tahun ajaran induknya? Periode lain akan dinonaktifkan."
                                                        >
                                                            <i class="fa-solid fa-arrows-rotate me-1" aria-hidden="true"></i>Sinkronkan Konteks
                                                        </button>
                                                    @elseif ($sem->aktif)
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            wire:click="nonaktifkanSemester('{{ $sem->idsemester }}')"
                                                            wire:confirm="Nonaktifkan semester ini? Konteks akademik mungkin menjadi tidak valid sampai semester lain diaktifkan."
                                                        >
                                                            <i class="fa-solid fa-pause me-1" aria-hidden="true"></i>Nonaktifkan
                                                        </button>
                                                    @else
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-success"
                                                            wire:click="aktifkanSemester('{{ $sem->idsemester }}')"
                                                            wire:confirm="Aktifkan semester ini dan tahun ajaran induknya? Semua semester dan tahun ajaran lain akan dinonaktifkan."
                                                        >
                                                            <i class="fa-solid fa-check me-1" aria-hidden="true"></i>Aktifkan
                                                        </button>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary academic-icon-button"
                                                        wire:click="editSemester('{{ $sem->idsemester }}')"
                                                        title="Edit semester {{ $sem->nama }}"
                                                        aria-label="Edit semester {{ $sem->nama }}"
                                                    >
                                                        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger academic-icon-button"
                                                        wire:click="deleteConfirmationSemester('{{ $sem->idsemester }}')"
                                                        title="Hapus semester {{ $sem->nama }}"
                                                        aria-label="Hapus semester {{ $sem->nama }}"
                                                    >
                                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="academic-empty">
                                        <i class="fa-regular fa-calendar-plus me-1" aria-hidden="true"></i>
                                        Belum ada semester. Tambahkan semester untuk tahun ajaran ini.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $tahunajarans->links() }}
            </div>
        @else
            <div class="academic-empty py-5">
                <i class="fa-regular fa-folder-open fs-3 d-block mb-2" aria-hidden="true"></i>
                <div class="fw-semibold">Tahun ajaran tidak ditemukan</div>
                <div class="small mt-1">Periksa kata pencarian atau tambahkan tahun ajaran baru.</div>
            </div>
        @endif
    </section>

    <div wire:ignore.self class="modal fade" id="addTahunajaranModal" tabindex="-1" aria-labelledby="addTahunajaranTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="addTahunajaranTitle">Tambah Tahun Ajaran</h2>
                        <p class="modal-description">Masukkan periode baru dengan format tahun berurutan.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="close()" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeTahunajaran">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Tahun Ajaran</label>
                            <input type="text" id="nama" class="form-control" wire:model="nama" placeholder="Contoh: 2026/2027" autocomplete="off">
                            <div class="form-text">Format: YYYY/YYYY, dengan tahun kedua satu angka lebih besar.</div>
                            @error('nama') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal" wire:click="close()">Batal</button>
                            <button type="submit" class="btn btn-academic" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Simpan Tahun Ajaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editTahunajaranModal" tabindex="-1" aria-labelledby="editTahunajaranTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="editTahunajaranTitle">Edit Tahun Ajaran</h2>
                        <p class="modal-description">Perbarui nama periode tahun ajaran.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="close()" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateTahunajaran">
                        <div class="mb-3">
                            <label for="edit_nama" class="form-label">Nama Tahun Ajaran</label>
                            <input type="text" id="edit_nama" class="form-control" wire:model="nama" placeholder="Contoh: 2026/2027" autocomplete="off">
                            <div class="form-text">Format: YYYY/YYYY, dengan tahun kedua satu angka lebih besar.</div>
                            @error('nama') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal" wire:click="close()">Batal</button>
                            <button type="submit" class="btn btn-academic" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="deleteTahunajaranModal" tabindex="-1" aria-labelledby="deleteTahunajaranTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title text-danger" id="deleteTahunajaranTitle">Hapus Tahun Ajaran</h2>
                        <p class="modal-description">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="close()" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Tahun ajaran aktif atau yang masih memiliki semester maupun data terkait tidak dapat dihapus.</p>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal" wire:click="close()">Batal</button>
                        <button type="button" class="btn btn-danger" wire:click="deleteTahunajaran()">Hapus Tahun Ajaran</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="addSemesterModal" tabindex="-1" aria-labelledby="addSemesterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="addSemesterTitle">Tambah Semester</h2>
                        <p class="modal-description">Semester akan ditambahkan ke tahun ajaran yang dipilih.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="close()" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeSemester">
                        <div class="mb-3">
                            <label for="add_semester_tahunajaran" class="form-label">Tahun Ajaran</label>
                            <select class="form-select" wire:model="semester_tahunajaran_id" id="add_semester_tahunajaran" disabled>
                                @foreach ($semuaTahunajaran as $tahun)
                                    <option value="{{ $tahun->idtahunajaran }}">{{ $tahun->nama }}</option>
                                @endforeach
                            </select>
                            @error('semester_tahunajaran_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="add_semester_nama" class="form-label">Nama Semester</label>
                            <select class="form-select" wire:model="semester_nama" id="add_semester_nama">
                                <option value="">Pilih semester</option>
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                            @error('semester_nama') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal" wire:click="close()">Batal</button>
                            <button type="submit" class="btn btn-academic" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Simpan Semester
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="editSemesterTitle">Edit Semester</h2>
                        <p class="modal-description">Perbarui nama dan tahun ajaran induk semester.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="close()" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateSemester">
                        <div class="mb-3">
                            <label for="edit_semester_tahunajaran" class="form-label">Tahun Ajaran</label>
                            <select class="form-select" wire:model="semester_tahunajaran_id" id="edit_semester_tahunajaran" @disabled($semester_aktif)>
                                @foreach ($semuaTahunajaran as $tahun)
                                    <option value="{{ $tahun->idtahunajaran }}">{{ $tahun->nama }}</option>
                                @endforeach
                            </select>
                            @if ($semester_aktif)
                                <div class="form-text">Semester aktif tidak dapat dipindahkan ke tahun ajaran lain.</div>
                            @endif
                            @error('semester_tahunajaran_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="edit_semester_nama" class="form-label">Nama Semester</label>
                            <select class="form-select" wire:model="semester_nama" id="edit_semester_nama">
                                <option value="">Pilih semester</option>
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                            @error('semester_nama') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal" wire:click="close()">Batal</button>
                            <button type="submit" class="btn btn-academic" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="deleteSemesterModal" tabindex="-1" aria-labelledby="deleteSemesterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title text-danger" id="deleteSemesterTitle">Hapus Semester</h2>
                        <p class="modal-description">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="close()" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus semester ini? Semester aktif atau semester yang masih digunakan tidak dapat dihapus.</p>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal" wire:click="close()">Batal</button>
                        <button type="button" class="btn btn-danger" wire:click="deleteSemester()">Hapus Semester</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    window.addEventListener('close-tahunajaran-modal', () => {
        ['addTahunajaranModal', 'editTahunajaranModal', 'deleteTahunajaranModal'].forEach(id => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
        });
    });

    window.addEventListener('show-add-tahunajaran-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addTahunajaranModal')).show();
    });

    window.addEventListener('show-edit-tahunajaran-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editTahunajaranModal')).show();
    });

    window.addEventListener('show-delete-confirmation-tahunajaran-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTahunajaranModal')).show();
    });

    window.addEventListener('close-semester-modal', () => {
        ['addSemesterModal', 'editSemesterModal', 'deleteSemesterModal'].forEach(id => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
        });
    });

    window.addEventListener('show-add-semester-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addSemesterModal')).show();
    });

    window.addEventListener('show-edit-semester-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editSemesterModal')).show();
    });

    window.addEventListener('show-delete-confirmation-semester-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteSemesterModal')).show();
    });
</script>
</div>
