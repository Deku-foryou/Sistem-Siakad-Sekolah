<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 text-primary">Absensi Harian</h4>
            <small class="text-muted">Kelola absensi siswa per kelas</small>
        </div>
    </div>

    <!-- Alert Messages -->
    @if ($errorMessage)
        <div class="alert alert-danger alert-sm py-2" role="alert">
            {{ $errorMessage }}
            <button type="button" class="btn-close btn-sm" wire:click="$set('errorMessage', '')"></button>
        </div>
    @endif
    @if ($successMessage)
        <div class="alert alert-success alert-sm py-2" role="alert">
            {{ $successMessage }}
            <button type="button" class="btn-close btn-sm" wire:click="$set('successMessage', '')"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card card-body shadow-sm mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <select wire:model.live="tahunAjaranId" class="form-select form-select-sm">
                    <option value="">-- Tahun Ajaran --</option>
                    @foreach ($tahunAjaranList as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.live="semesterId" class="form-select form-select-sm">
                    <option value="">-- Semester --</option>
                    @foreach ($semesterList as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.live="kelasId" class="form-select form-select-sm">
                    <option value="">-- Kelas --</option>
                    @foreach ($kelasList as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" wire:model.live="tanggal" class="form-control form-control-sm">
            </div>
        </div>
    </div>

    <!-- Absensi Table -->
    @if ($isLoaded)
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0">Daftar Siswa ({{ count($siswaList) }})</h6>
                <button class="btn btn-sm btn-primary" wire:click="simpanAbsensi" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Siswa</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswaList as $index => $siswa)
                            @php
                                $sId = $siswa['idsiswa'];
                                $record = $absensiData[$sId] ?? null;
                                $isEditingThis = $isEditMode && $editAbsensiId == ($record['idabsensi_harian'] ?? 0);
                            @endphp
                            <tr class="{{ $isEditingThis ? 'table-warning' : '' }}">
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $siswa['nama'] }}</td>
                                <td>
                                    <select wire:model="absensiData.{{ $sId }}.status"
                                        class="form-select form-select-sm {{ $isEditingThis ? '' : 'bg-transparent border-0' }}"
                                        @if (!$isEditingThis && $record && $record['idabsensi_harian']) disabled @endif>
                                        <option value="hadir">Hadir</option>
                                        <option value="sakit">Sakit</option>
                                        <option value="izin">Izin</option>
                                        <option value="alpha">Alpha</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" wire:model="absensiData.{{ $sId }}.catatan"
                                        placeholder="-" class="form-control form-control-sm {{ $isEditingThis ? '' : 'bg-transparent border-0' }}"
                                        @if (!$isEditingThis && $record && $record['idabsensi_harian']) disabled @endif>
                                </td>
                                <td class="text-center">
                                    @if ($record && $record['idabsensi_harian'])
                                        <button class="btn btn-sm {{ $isEditingThis ? 'btn-outline-danger' : 'btn-outline-secondary' }}"
                                            wire:click="toggleEditAbsensi({{ $record['idabsensi_harian'] }})">
                                            {{ $isEditingThis ? '✕' : '✎' }}
                                        </button>
                                    @else
                                        <span class="badge bg-light text-dark">Baru</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data siswa aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($kelasId && $tanggal)
        <div class="alert alert-info py-2">Memuat data...</div>
    @else
        <div class="alert alert-secondary py-2">Silakan pilih filter di atas.</div>
    @endif
</div>