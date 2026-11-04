<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 text-primary">Absensi Per Jam</h4>
            <small class="text-muted">Kelola absensi siswa per pertemuan jadwal &mdash; {{ $guruNama ?? 'Guru' }}</small>
        </div>
    </div>

    <!-- Alert Messages -->
    @if ($errorMessage)
        <div class="alert alert-danger py-2 d-flex justify-content-between align-items-center" role="alert">
            <span>{{ $errorMessage }}</span>
            <button type="button" class="btn-close" wire:click="$set('errorMessage', '')" aria-label="Tutup"></button>
        </div>
    @endif
    @if ($successMessage)
        <div class="alert alert-success py-2 d-flex justify-content-between align-items-center" role="alert">
            <span>{{ $successMessage }}</span>
            <button type="button" class="btn-close" wire:click="$set('successMessage', '')" aria-label="Tutup"></button>
        </div>
    @endif

    <!-- Filter tanggal -->
    <div class="card card-body shadow-sm mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="tanggal-pertemuan" class="form-label mb-1 small fw-semibold">Tanggal pertemuan</label>
                <input type="date" id="tanggal-pertemuan" wire:model.live="tanggal" class="form-control form-control-sm">
            </div>
            <div class="col-md-8">
                <div class="form-text">Pertemuan pada tanggal terpilih akan digenerate otomatis dari jadwal aktif bila belum tersedia.</div>
            </div>
        </div>
    </div>

    <!-- Daftar pertemuan hari ini -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0">Pertemuan Hari Ini ({{ count($pertemuanList) }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">Jam Ke</th>
                        <th>Waktu</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pertemuanList as $p)
                        <tr class="{{ (int) $p['idjadwal_pertemuan'] === (int) $pertemuanId ? 'table-success' : '' }}">
                            <td class="text-center">{{ $p['jam_ke'] ?? '-' }}</td>
                            <td>{{ $p['waktu_mulai'] }}&ndash;{{ $p['waktu_selesai'] }}</td>
                            <td>{{ $p['kelas_nama'] }}</td>
                            <td>{{ $p['mapel_nama'] }}</td>
                            <td class="text-center">
                                @if ($p['status'] === 'terjadwal')
                                    <span class="badge bg-success">Terjadwal</span>
                                @elseif ($p['status'] === 'batal')
                                    <span class="badge bg-danger">Batal</span>
                                @else
                                    <span class="badge bg-secondary">Selesai</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="pilihPertemuan({{ $p['idjadwal_pertemuan'] }})">
                                    Isi Absensi
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Tidak ada pertemuan untuk tanggal ini pada penugasan Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel absensi pertemuan terpilih -->
    @if ($pertemuanTerpilih)
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <div>
                    <h6 class="mb-0">
                        {{ $pertemuanTerpilih['kelas_nama'] }} &mdash; {{ $pertemuanTerpilih['mapel_nama'] }}
                        <span class="text-muted fw-normal">({{ $pertemuanTerpilih['tanggal'] }}, jam ke-{{ $pertemuanTerpilih['jam_ke'] ?? '-' }})</span>
                    </h6>
                    <small class="text-muted">{{ $pertemuanTerpilih['waktu_mulai'] }}&ndash;{{ $pertemuanTerpilih['waktu_selesai'] }}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if ($pertemuanTerpilih['status'] !== 'terjadwal')
                        <span class="badge bg-danger">Pertemuan {{ $pertemuanTerpilih['status'] }} &mdash; tidak dapat diabsen</span>
                    @elseif ($pertemuanTerpilih['deadline_lewat'])
                        <span class="badge bg-warning text-dark">Batas edit guru (H+1) terlewat</span>
                    @else
                        <span class="badge bg-success">Dapat diabsen</span>
                    @endif
                    <button class="btn btn-sm btn-primary" wire:click="simpanAbsensi" wire:loading.attr="disabled"
                        @if ($pertemuanTerpilih['status'] !== 'terjadwal' || $pertemuanTerpilih['deadline_lewat']) disabled @endif>
                        <span wire:loading.remove>Simpan</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Siswa</th>
                            <th style="width: 170px;">Status</th>
                            <th>Keterangan</th>
                            <th class="text-center" style="width: 90px;">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswaList as $index => $siswa)
                            @php
                                $sId = $siswa['idsiswa'];
                                $record = $absensiData[$sId] ?? null;
                                $disabled = $pertemuanTerpilih['status'] !== 'terjadwal'
                                    || $pertemuanTerpilih['deadline_lewat'];
                                $rowStatus = $record['status'] ?? '';
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $siswa['nama'] }}</td>
                                <td>
                                    <select wire:model="absensiData.{{ $sId }}.status" class="form-select form-select-sm" @if ($disabled) disabled @endif>
                                        <option value="">&mdash; Belum diabsen</option>
                                        <option value="hadir">Hadir</option>
                                        <option value="sakit">Sakit</option>
                                        <option value="izin">Izin</option>
                                        <option value="alpha">Alpha</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" wire:model="absensiData.{{ $sId }}.keterangan" placeholder="-" class="form-control form-control-sm" @if ($disabled) disabled @endif>
                                </td>
                                <td class="text-center">
                                    @if ($record && $record['idabsensi_perjam'])
                                        @if ($rowStatus === 'hadir')
                                            <span class="badge bg-success">Hadir</span>
                                        @elseif ($rowStatus === 'sakit')
                                            <span class="badge bg-warning text-dark">Sakit</span>
                                        @elseif ($rowStatus === 'izin')
                                            <span class="badge bg-info text-dark">Izin</span>
                                        @elseif ($rowStatus === 'alpha')
                                            <span class="badge bg-danger">Alpha</span>
                                        @else
                                            <span class="badge bg-secondary">Tersimpan</span>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-dark border">Baru</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada siswa dengan penempatan aktif pada kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pertemuanTerpilih['status'] === 'terjadwal' && ! $pertemuanTerpilih['deadline_lewat'])
                <div class="card-footer bg-white text-muted small py-2">
                    Baris dengan status kosong berarti belum diabsen. Hanya siswa dengan penempatan aktif yang ditampilkan. Batas edit guru: H+1 tanggal pertemuan.
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-secondary py-2">Pilih pertemuan di atas untuk menampilkan daftar siswa dan mengisi absensi.</div>
    @endif
</div>
