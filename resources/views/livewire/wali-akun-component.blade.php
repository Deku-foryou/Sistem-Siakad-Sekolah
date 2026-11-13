@section('title')
    Kelola Akun Wali
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">

        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="mb-3 mb-lg-0">
                    <p class='p-0 m-0 h3 fw-bold'>Kelola Akun Wali</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>
                        Buat, reset sandi, dan nonaktif/aktifkan akun wali. Sandi sementara tampil sekali.
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAkunModal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data">
                        <i class="fa-solid fa-user-plus me-2"></i>Tambah Akun Wali
                    </button>
                </div>
            </div>
        </div>

        @if ($sandiTampil)
            <div class="alert alert-warning border border-warning mt-3" role="alert">
                <div class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i>Sandi sementara (tampil sekali) — salin sekarang, jangan dibagi lewat pesan/email.</div>
                <div class="d-flex align-items-center gap-2">
                    <code id="sandiSementara" class="fs-5">{{ $sandiTampil }}</code>
                    <button type="button" class="btn btn-sm btn-outline-dark" id="copySandiBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Salin">Salin</button>
                    <button type="button" class="btn btn-sm btn-primary" wire:click="dismissSandi" data-bs-toggle="tooltip" data-bs-placement="top" title="Ya, lanjutkan">Saya sudah menyalin</button>
                </div>
            </div>
        @endif

        <!-- Loading Spinner -->
        <div wire:loading wire:target="simpan,simpanAksi" class="loading-spinner">
            <div class="loader"></div>
        </div>

        <!-- Action Toolbar -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-5">
                        <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #e9ecef;">
                            <span class="input-group-text bg-white border-0 ps-3">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-0 ps-2 py-2" placeholder="Cari nama / username / email..."
                                style="font-size: 14px; box-shadow: none;" wire:model.live.debounce.300ms="search">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <select class="form-select border-0" wire:model.live="filterStatus">
                            <option value="">Semua status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <select class="form-select border-0" wire:model.live="perpage">
                            <option value="10">10 / halaman</option>
                            <option value="25">25 / halaman</option>
                            <option value="50">50 / halaman</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Nama</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status akun</th>
                                <th class="pe-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="ps-3">{{ $user->name }}</td>
                                    <td><code>{{ $user->username }}</code></td>
                                    <td>{{ $user->email ?? '—' }}</td>
                                    <td>
                                        @if ($user->status_akun === 'aktif')
                                            <span class="badge bg-success">aktif</span>
                                        @else
                                            <span class="badge bg-secondary">nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end">
                                        <button class="btn btn-sm btn-outline-primary" title="Reset sandi"
                                            wire:click="bukaAksi({{ $user->id }}, 'reset')" data-bs-toggle="modal" data-bs-target="#aksiModal" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="fa-solid fa-key"></i>
                                        </button>
                                        @if ($user->status_akun === 'aktif')
                                            <button class="btn btn-sm btn-outline-danger" title="Nonaktifkan"
                                                wire:click="bukaAksi({{ $user->id }}, 'nonaktif')" data-bs-toggle="modal" data-bs-target="#aksiModal" data-bs-toggle="tooltip" data-bs-placement="top">
                                                <i class="fa-solid fa-user-slash"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success" title="Aktifkan"
                                                wire:click="bukaAksi({{ $user->id }}, 'aktif')" data-bs-toggle="modal" data-bs-target="#aksiModal" data-bs-toggle="tooltip" data-bs-placement="top">
                                                <i class="fa-solid fa-user-check"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada akun wali.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $users->links() }}</div>
            </div>
        </div>

        <!-- Modal Tambah Akun Wali -->
        <div class="modal fade" id="addAkunModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Akun Wali</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup"></button>
                    </div>
                    <form wire:submit.prevent="simpan">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="name">Nama lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" wire:model="name" autocomplete="off">
                                @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" wire:model="username" autocomplete="off" placeholder="maks 30 karakter, huruf/angka/. _ -">
                                @error('username') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="email">Email (opsional)</label>
                                <input type="email" class="form-control" id="email" wire:model="email" autocomplete="off">
                                @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-1">
                                <label class="form-label" for="alasanAkun">Keterangan (opsional)</label>
                                <input type="text" class="form-control" id="alasanAkun" wire:model="alasanAkun" autocomplete="off" placeholder="Misal: diverifikasi dokumen">
                            </div>
                            <p class="small text-muted mb-0 mt-3">Sistem akan menerbitkan sandi sementara acak (16 karakter) dan menampilkan hanya satu kali ke admin setelah disimpan.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                                <span wire:loading><span class="spinner-border spinner-border-sm"></span></span>
                                Simpan &amp; Terbitkan Sandi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Aksi (reset / nonaktif / aktif) -->
        <div class="modal fade" id="aksiModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            @if ($aksi === 'reset') Reset Sandi
                            @elseif ($aksi === 'nonaktif') Nonaktifkan Akun
                            @else Aktifkan Akun
                            @endif
                        </h5>
                        <button type="button" class="btn-close" wire:click="batalAksi" data-bs-dismiss="modal" aria-label="Close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="alasanAksi">Alasan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="alasanAksi" rows="3" wire:model="alasanAksi" placeholder="Misal: permohonan resmi orang tua / rotasi administrasi"></textarea>
                            @error('alasanAksi') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <p class="small text-muted mb-0">Data ini dicatat ke histori akun (append-only), aman dan tidak berisi sandi.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="batalAksi" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                        <button type="button" class="btn btn-primary" wire:click="simpanAksi" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                            <span wire:loading><span class="spinner-border spinner-border-sm"></span></span>
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@script
    <script>
        Livewire.on('close-add-akun-modal', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addAkunModal')).hide();
        });
        Livewire.on('close-sandi-modal', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('aksiModal')).hide();
        });
        Livewire.on('close-status-modal', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('aksiModal')).hide();
        });

        const btn = document.getElementById('copySandiBtn');
        if (btn) {
            btn.addEventListener('click', () => {
                const el = document.getElementById('sandiSementara');
                if (el && navigator.clipboard) {
                    navigator.clipboard.writeText(el.textContent.trim());
                    btn.textContent = 'Tersalin';
                    setTimeout(() => btn.textContent = 'Salin', 1500);
                }
            });
        }
    </script>
@endscript
