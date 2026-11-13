@section('title', 'Absensi Harian')

<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

    {{-- Page header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-calendar-check me-2 text-success" aria-hidden="true"></i>Absensi Harian</h1>
            <p class="sa-sub">Kelola kehadiran siswa per kelas, satu hari per sesi pengisian.</p>
        </div>
        @if ($isLoaded)
            <button class="btn btn-primary" wire:click="simpanAbsensi" wire:loading.attr="disabled" wire:target="simpanAbsensi" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                <span wire:loading.remove wire:target="simpanAbsensi"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan Absensi</span>
                <span wire:loading wire:target="simpanAbsensi"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyimpan&hellip;</span>
            </button>
        @endif
    </div>

    {{-- Feedback --}}
    @if ($errorMessage)
        <div class="alert alert-danger d-flex justify-content-between align-items-center gap-2 py-2" role="alert">
            <span><i class="fa-solid fa-circle-exclamation me-2" aria-hidden="true"></i>{{ $errorMessage }}</span>
            <button type="button" class="btn-close" wire:click="$set('errorMessage', '')" aria-label="Tutup pesan error" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
        </div>
    @endif
    @if ($successMessage)
        <div class="alert alert-success d-flex justify-content-between align-items-center gap-2 py-2" role="status">
            <span><i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>{{ $successMessage }}</span>
            <button type="button" class="btn-close" wire:click="$set('successMessage', '')" aria-label="Tutup pesan sukses" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
        </div>
    @endif

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
        <p class="sa-hint mb-2"><i class="fa-solid fa-filter me-1" aria-hidden="true"></i>Pilih periode dan kelas untuk memuat daftar siswa.</p>
        <div class="row g-2">
            <div class="col-sm-6 col-md-3">
                <label for="absensi-ta" class="form-label">Tahun Ajaran</label>
                <select id="absensi-ta" wire:model.live="tahunAjaranId" class="form-select">
                    <option value="">-- Pilih Tahun Ajaran --</option>
                    @foreach ($tahunAjaranList as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="absensi-smt" class="form-label">Semester</label>
                <select id="absensi-smt" wire:model.live="semesterId" class="form-select">
                    <option value="">-- Pilih Semester --</option>
                    @foreach ($semesterList as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="absensi-kelas" class="form-label">Kelas</label>
                <select id="absensi-kelas" wire:model.live="kelasId" class="form-select">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach ($kelasList as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="absensi-tanggal" class="form-label">Tanggal</label>
                <input type="date" id="absensi-tanggal" wire:model.live="tanggal" class="form-control">
            </div>
        </div>
    </div>

    @if ($isLoaded)
        @php
            $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'kosong' => 0];
            foreach ($siswaList as $siswa) {
                $sId = $siswa['idsiswa'];
                $st = $absensiData[$sId]['status'] ?? null;
                if ($st && array_key_exists($st, $rekap)) {
                    $rekap[$st]++;
                } else {
                    $rekap['kosong']++;
                }
            }
        @endphp

        {{-- Ringkasan --}}
        <div class="sa-stats" aria-label="Ringkasan kehadiran">
            <span class="sa-stat"><i class="fa-solid fa-users text-secondary" aria-hidden="true"></i>Siswa <b>{{ count($siswaList) }}</b></span>
            <span class="sa-stat">Hadir <b class="text-success">{{ $rekap['hadir'] }}</b></span>
            <span class="sa-stat">Izin <b class="text-primary">{{ $rekap['izin'] }}</b></span>
            <span class="sa-stat">Sakit <b class="text-warning-emphasis">{{ $rekap['sakit'] }}</b></span>
            <span class="sa-stat">Alpha <b class="text-danger">{{ $rekap['alpha'] }}</b></span>
            @if ($rekap['kosong'] > 0)
                <span class="sa-stat">Belum diisi <b>{{ $rekap['kosong'] }}</b></span>
            @endif
        </div>

        {{-- Tabel siswa --}}
        <div class="sa-card">
            <div class="sa-card-head">
                <h2 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Daftar Siswa</h2>
                <span class="sa-count">{{ count($siswaList) }} siswa</span>
            </div>
            <div class="table-responsive">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 52px;">No</th>
                            <th>Nama Siswa</th>
                            <th style="width: 170px;">Status</th>
                            <th>Catatan</th>
                            <th class="text-center" style="width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswaList as $index => $siswa)
                            @php
                                $sId = $siswa['idsiswa'];
                                $record = $absensiData[$sId] ?? null;
                                $isEditingThis = $isEditMode && $editAbsensiId == ($record['idabsensi_harian'] ?? 0);
                            @endphp
                            <tr class="{{ $isEditingThis ? 'sa-row-active' : '' }}">
                                <td class="text-center sa-cell-muted">{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $siswa['nama'] }}</td>
                                <td>
                                    <select wire:model="absensiData.{{ $sId }}.status"
                                        class="form-select form-select-sm sa-focus {{ $isEditingThis ? '' : 'bg-transparent border-0' }}"
                                        aria-label="Status kehadiran {{ $siswa['nama'] }}"
                                        @if (! $isEditingThis && $record && $record['idabsensi_harian']) disabled @endif>
                                        <option value="hadir">Hadir</option>
                                        <option value="sakit">Sakit</option>
                                        <option value="izin">Izin</option>
                                        <option value="alpha">Alpha</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" wire:model="absensiData.{{ $sId }}.catatan"
                                        placeholder="-" class="form-control form-control-sm sa-focus {{ $isEditingThis ? '' : 'bg-transparent border-0' }}"
                                        aria-label="Catatan kehadiran {{ $siswa['nama'] }}"
                                        @if (! $isEditingThis && $record && $record['idabsensi_harian']) disabled @endif>
                                </td>
                                <td class="text-center">
                                    @if ($record && $record['idabsensi_harian'])
                                        <button type="button"
                                            class="btn btn-sm {{ $isEditingThis ? 'btn-outline-danger' : 'btn-outline-secondary' }}"
                                            wire:click="toggleEditAbsensi({{ $record['idabsensi_harian'] }})"
                                            aria-label="{{ $isEditingThis ? 'Batalkan ubah' : 'Ubah absensi' }} {{ $siswa['nama'] }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data">
                                            @if ($isEditingThis)
                                                <i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Batal</span>
                                            @else
                                                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Ubah</span>
                                            @endif
                                        </button>
                                    @else
                                        <span class="sa-badge sa-netral">Baru</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="sa-empty"><i class="fa-solid fa-user-slash" aria-hidden="true"></i><p class="sa-empty-title">Tidak ada siswa aktif</p><p class="sa-empty-text">Tidak ditemukan siswa aktif dengan penempatan pada kelas ini.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="sa-card-foot">
                <span><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Baris kuning = mode ubah baris tersebut saja. Simpan hanya untuk baris yang diedit.</span>
                <button type="button" class="btn btn-sm btn-primary" wire:click="simpanAbsensi" wire:loading.attr="disabled" wire:target="simpanAbsensi" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                    <span wire:loading.remove wire:target="simpanAbsensi"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</span>
                    <span wire:loading wire:target="simpanAbsensi">Menyimpan&hellip;</span>
                </button>
            </div>
        </div>
    @elseif ($kelasId && $tanggal)
        <div class="sa-card"><div class="sa-card-body text-center py-4 sa-hint"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memuat data absensi&hellip;</div></div>
    @else
        <div class="sa-card">
            <div class="sa-card-body sa-empty">
                <i class="fa-regular fa-clipboard" aria-hidden="true"></i>
                <p class="sa-empty-title">Pilih filter dulu</p>
                <p class="sa-empty-text">Tahun ajaran, semester, kelas, dan tanggal diperlukan untuk menampilkan daftar siswa.</p>
            </div>
        </div>
    @endif
</div>
