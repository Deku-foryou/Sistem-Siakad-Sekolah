@section('title', 'Absensi Per Jam')

<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

    {{-- Page header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-user-clock me-2 text-success" aria-hidden="true"></i>Absensi Per Jam</h1>
            <p class="sa-sub">Kelola kehadiran siswa per pertemuan jadwal &mdash; {{ $guruNama ?? 'Guru' }}</p>
        </div>
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

    {{-- Filter tanggal --}}
    <div class="sa-card sa-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-4">
                <label for="tanggal-pertemuan" class="form-label">Tanggal pertemuan</label>
                <input type="date" id="tanggal-pertemuan" wire:model.live="tanggal" class="form-control">
            </div>
            <div class="col-sm-6 col-md-8">
                <p class="sa-hint mb-0"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Pertemuan pada tanggal terpilih digenerate otomatis dari jadwal aktif bila belum tersedia.</p>
            </div>
        </div>
    </div>

    {{-- Daftar pertemuan --}}
    <div class="sa-card mb-3">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-list-ol" aria-hidden="true"></i>Pertemuan Terjadwal</h2>
            <span class="sa-count">{{ count($pertemuanList) }} pertemuan</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 70px;">Jam Ke</th>
                        <th>Waktu</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pertemuanList as $p)
                        <tr class="{{ (int) $p['idjadwal_pertemuan'] === (int) $pertemuanId ? 'sa-row-active' : '' }}">
                            <td class="text-center fw-semibold">{{ $p['jam_ke'] ?? '-' }}</td>
                            <td class="sa-cell-muted">{{ $p['waktu_mulai'] }}&ndash;{{ $p['waktu_selesai'] }}</td>
                            <td class="fw-semibold">{{ $p['kelas_nama'] }}</td>
                            <td>{{ $p['mapel_nama'] }}</td>
                            <td class="text-center">
                                @if ($p['status'] === 'terjadwal')
                                    <span class="sa-badge sa-aktif">Terjadwal</span>
                                @elseif ($p['status'] === 'batal')
                                    <span class="sa-badge sa-alpha">Batal</span>
                                @else
                                    <span class="sa-badge sa-netral">Selesai</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="pilihPertemuan({{ $p['idjadwal_pertemuan'] }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Isi absensi">
                                    <i class="fa-solid fa-pen-to-square me-1" aria-hidden="true"></i>Isi Absensi
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sa-empty"><i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i><p class="sa-empty-title">Tidak ada pertemuan</p><p class="sa-empty-text">Tidak ada pertemuan untuk tanggal ini pada penugasan Anda.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Panel absensi terpilih --}}
    @if ($pertemuanTerpilih)
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2 class="sa-card-title"><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i>{{ $pertemuanTerpilih['kelas_nama'] }} &mdash; {{ $pertemuanTerpilih['mapel_nama'] }}</h2>
                    <div class="sa-hint mt-1">{{ $pertemuanTerpilih['tanggal'] }} &middot; jam ke-{{ $pertemuanTerpilih['jam_ke'] ?? '-' }} &middot; {{ $pertemuanTerpilih['waktu_mulai'] }}&ndash;{{ $pertemuanTerpilih['waktu_selesai'] }}</div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if ($pertemuanTerpilih['status'] !== 'terjadwal')
                        <span class="sa-badge sa-alpha">Pertemuan {{ $pertemuanTerpilih['status'] }} &mdash; tidak dapat diabsen</span>
                    @elseif ($pertemuanTerpilih['deadline_lewat'])
                        <span class="sa-badge sa-sakit">Batas edit guru (H+1) terlewat</span>
                    @else
                        <span class="sa-badge sa-aktif">Dapat diabsen</span>
                    @endif
                    <button class="btn btn-sm btn-primary" wire:click="simpanAbsensi" wire:loading.attr="disabled" wire:target="simpanAbsensi"
                        @if ($pertemuanTerpilih['status'] !== 'terjadwal' || $pertemuanTerpilih['deadline_lewat']) disabled @endif data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                        <span wire:loading.remove wire:target="simpanAbsensi"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</span>
                        <span wire:loading wire:target="simpanAbsensi">Menyimpan&hellip;</span>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 52px;">No</th>
                            <th>Nama Siswa</th>
                            <th style="width: 190px;">Status</th>
                            <th>Keterangan</th>
                            <th class="text-center" style="width: 110px;">Data</th>
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
                                <td class="text-center sa-cell-muted">{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $siswa['nama'] }}</td>
                                <td>
                                    <select wire:model="absensiData.{{ $sId }}.status" class="form-select form-select-sm sa-focus" aria-label="Status kehadiran {{ $siswa['nama'] }}" @if ($disabled) disabled @endif>
                                        <option value="">&mdash; Belum diabsen</option>
                                        <option value="hadir">Hadir</option>
                                        <option value="sakit">Sakit</option>
                                        <option value="izin">Izin</option>
                                        <option value="alpha">Alpha</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" wire:model="absensiData.{{ $sId }}.keterangan" placeholder="-" class="form-control form-control-sm sa-focus" aria-label="Keterangan {{ $siswa['nama'] }}" @if ($disabled) disabled @endif>
                                </td>
                                <td class="text-center">
                                    @if ($record && $record['idabsensi_perjam'])
                                        @if ($rowStatus === 'hadir')
                                            <span class="sa-badge sa-hadir">Hadir</span>
                                        @elseif ($rowStatus === 'sakit')
                                            <span class="sa-badge sa-sakit">Sakit</span>
                                        @elseif ($rowStatus === 'izin')
                                            <span class="sa-badge sa-izin">Izin</span>
                                        @elseif ($rowStatus === 'alpha')
                                            <span class="sa-badge sa-alpha">Alpha</span>
                                        @else
                                            <span class="sa-badge sa-netral">Tersimpan</span>
                                        @endif
                                    @else
                                        <span class="sa-badge sa-netral">Baru</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="sa-empty"><i class="fa-solid fa-user-slash" aria-hidden="true"></i><p class="sa-empty-title">Tidak ada siswa</p><p class="sa-empty-text">Tidak ada siswa dengan penempatan aktif pada kelas ini.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pertemuanTerpilih['status'] === 'terjadwal' && ! $pertemuanTerpilih['deadline_lewat'])
                <div class="sa-card-foot">
                    <span><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Baris kosong berarti belum diabsen. Hanya siswa dengan penempatan aktif yang ditampilkan.</span>
                    <span><strong>Batas edit guru:</strong> H+1 tanggal pertemuan</span>
                </div>
            @endif
        </div>
    @else
        <div class="sa-card"><div class="sa-card-body sa-empty"><i class="fa-regular fa-hand-pointer" aria-hidden="true"></i><p class="sa-empty-title">Pilih pertemuan</p><p class="sa-empty-text">Klik &ldquo;Isi Absensi&rdquo; pada salah satu pertemuan di atas untuk menampilkan daftar siswa.</p></div></div>
    @endif
</div>
