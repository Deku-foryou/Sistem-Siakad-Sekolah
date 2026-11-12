@section('title', 'Koreksi Nilai')
<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')
    {{-- Header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-pen-to-square me-2 text-success" aria-hidden="true"></i>Koreksi Nilai</h1>
            @if ($konteks->valid())
                <p class="sa-sub">Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot; Semester {{ $konteks->semesterNama() }}</p>
            @endif
        </div>
        <span class="sa-chip"><i class="fa-solid fa-user-shield" aria-hidden="true"></i>Superadmin</span>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Konteks akademik belum valid</div>
            <div class="mt-1">Koreksi nilai tidak dapat dilakukan sampai tahun ajaran/semester aktif tersedia.</div>
        </div>
    @endif

    @if ($pesanGalat)
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ $pesanGalat }}
            <button type="button" class="btn-close" wire:click="$set('galat', '')" aria-label="Tutup"></button>
        </div>
    @endif

    @if ($sukses)
        <div class="alert alert-success alert-dismissible" role="status">
            {{ $sukses }}
            <button type="button" class="btn-close" wire:click="$set('sukses', '')" aria-label="Tutup"></button>
        </div>
    @endif

    @if ($konteks->valid())
        {{-- Filter kelas & mapel --}}
        <div class="sa-card sa-filter mb-3">
            <label class="form-label fw-semibold" for="kelas">Kelas</label>
            <select id="kelas" class="form-select" wire:model.live="kelasId">
                <option value="">Pilih kelas</option>
                @foreach ($daftarKelas as $item)
                    <option value="{{ $item->idkelas }}">{{ $item->nama }} ({{ $item->tingkat }})</option>
                @endforeach
            </select>

            @if ($kelasId !== '' && $nilaiFinal)
                <div class="mt-2 small">
                    @if ($nilaiFinal->status === 'final')
                        <span class="sa-badge sa-hadir">Rapor final</span>
                        <span class="text-body-secondary">difinalisasi oleh {{ $nilaiFinal->difinalisasiOleh->name ?? '-' }} pada {{ $nilaiFinal->difinalisasi_pada?->format('d/m/Y H:i') ?? '-' }}</span>
                    @else
                        <span class="sa-badge sa-netral">Draf</span>
                        <span class="text-body-secondary">koreksi hanya untuk rapor final</span>
                    @endif
                </div>
            @elseif ($kelasId !== '')
                <div class="mt-2 small text-body-secondary">Rapor belum difinalisasi; koreksi tidak dapat dilakukan.</div>
            @endif

            @if ($kelasId !== '')
                <label class="form-label fw-semibold mt-3" for="mapel">Mata Pelajaran</label>
                <select id="mapel" class="form-select" wire:model.live="mapelId">
                    <option value="">Pilih mata pelajaran</option>
                    @foreach ($mapelOptions as $mapel)
                        <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        @if ($mapelId !== '' && $mapelId !== null)
            <div class="sa-card mb-3">
                <div class="sa-card-head">
                    <h2 class="sa-card-title"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Koreksi Nilai Komponen</h2>
                    <span class="sa-count">{{ $barisNilai->count() }} baris</span>
                </div>
                <div class="table-responsive">
                    <table class="table sa-table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 180px;">Siswa</th>
                                <th style="min-width: 160px;">Komponen</th>
                                <th class="text-center" style="width: 110px;">Nilai</th>
                                <th class="text-center" style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($barisNilai as $baris)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $baris->siswa->nama ?? ('Siswa #'.$baris->siswa_idsiswa) }}</div>
                                        <div class="sa-cell-muted">NIS {{ $baris->siswa->nis ?? '-' }}</div>
                                    </td>
                                    <td>{{ $baris->komponenNilai->nama ?? ('Komponen #'.$baris->komponen_nilai_idkomponen_nilai) }}</td>
                                    <td class="text-center fw-semibold">{{ number_format((float) $baris->nilai, 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="bukaKoreksiKomponen({{ $baris->idnilai_siswa }})" @disabled(! $nilaiFinal || $nilaiFinal->status !== 'final') aria-label="Koreksi nilai {{ $baris->siswa->nama ?? $baris->siswa_idsiswa }}">
                                            <i class="fa-solid fa-pen-to-square me-1" aria-hidden="true"></i>Koreksi
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"><div class="sa-empty"><i class="fa-regular fa-clipboard" aria-hidden="true"></i><p class="sa-empty-title">Belum ada nilai</p><p class="sa-empty-text">Belum ada nilai tersimpan untuk mapel ini.</p></div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="sa-card-foot"><span>Koreksi komponen menghitung ulang snapshot nilai akhir siswa pada transaksi yang sama. Nilai lama/baru dan alasan dicatat di log.</span></div>
            </div>

            <div class="sa-card mb-3">
                <div class="sa-card-head">
                    <h2 class="sa-card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>Log Perubahan (30 terakhir)</h2>
                    <span class="sa-count">{{ $logList->count() }} log</span>
                </div>
                <div class="table-responsive">
                    <table class="table sa-table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Waktu</th>
                                <th style="width: 140px;">Target</th>
                                <th style="min-width: 220px;">Perubahan</th>
                                <th style="min-width: 180px;">Alasan</th>
                                <th style="width: 140px;">Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logList as $log)
                                <tr>
                                    <td class="small">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="small">
                                        @if ($log->nilai_siswa_idnilai_siswa)
                                            Nilai komponen<br>
                                            <span class="text-body-secondary">{{ $log->nilaiSiswa?->siswa?->nama ?? ('#'.$log->nilai_siswa_idnilai_siswa) }} &middot; {{ $log->nilaiSiswa?->komponenNilai?->nama }}</span>
                                        @else
                                            Nilai akhir<br>
                                            <span class="text-body-secondary">{{ $log->nilaiAkhir?->siswa?->nama ?? ('#'.$log->nilai_akhir_idnilai_akhir) }}</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        {{ $log->nilai_lama === null ? '-' : number_format((float) $log->nilai_lama, 2, ',', '.') }}
                                        &rarr;
                                        {{ $log->nilai_baru === null ? '-' : number_format((float) $log->nilai_baru, 2, ',', '.') }}
                                    </td>
                                    <td class="small">{{ $log->alasan }}</td>
                                    <td class="small">{{ $log->diubahOleh?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"><div class="sa-empty"><i class="fa-regular fa-clipboard" aria-hidden="true"></i><p class="sa-empty-text">Belum ada log perubahan untuk mapel ini.</p></div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="sa-card mb-3">
            <div class="sa-card-head">
                <h2 class="sa-card-title"><i class="fa-solid fa-bullseye" aria-hidden="true"></i>Koreksi KKTP (semester aktif)</h2>
                <span class="sa-count">{{ $kktpList->count() }} data</span>
            </div>
            <div class="table-responsive">
                <table class="table sa-table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Mata Pelajaran</th>
                            <th class="text-center" style="width: 120px;">KKTP</th>
                            <th class="text-center" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kktpList as $kktp)
                            <tr>
                                <td>{{ $kktp->mapel->nama ?? ('Mapel #'.$kktp->mapel_idmapel) }}</td>
                                <td class="text-center fw-semibold">{{ number_format((float) $kktp->nilai_minimal, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-warning" wire:click="bukaKoreksiKktp({{ $kktp->idkktp_mapel }})" aria-label="Koreksi KKTP {{ $kktp->mapel->nama ?? $kktp->mapel_idmapel }}">
                                        <i class="fa-solid fa-bullseye me-1" aria-hidden="true"></i>Koreksi
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3"><div class="sa-empty"><i class="fa-solid fa-bullseye" aria-hidden="true"></i><p class="sa-empty-text">Belum ada konfigurasi KKTP pada semester aktif.</p></div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="sa-card-foot"><span>Koreksi KKTP hanya untuk rapor final yang sudah memiliki snapshot. Konfigurasi KKTP biasa tetap dilakukan dari menu KKTP.</span></div>
        </div>
    @endif

<!-- Modal Koreksi Nilai Komponen -->
<div wire:ignore.self class="modal fade" id="koreksiNilaiModal" tabindex="-1" aria-labelledby="koreksiNilaiTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="koreksiNilaiTitle"><i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>Koreksi Nilai Komponen</h5></div>
            <form wire:submit.prevent="koreksiKomponen">
                <div class="modal-body">
                    <div class="alert alert-warning small" role="note"><i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Nilai rapor sudah final. Koreksi mencatat nilai lama/baru dan alasan ke log. Tidak ada penghapusan nilai.</div>
                    <label class="form-label" for="nilaiBaru">Nilai Baru</label>
                    <input id="nilaiBaru" type="number" class="form-control mb-3" min="0" max="100" step="0.01" inputmode="decimal" wire:model="nilaiBaru" placeholder="0 - 100">
                    <label class="form-label" for="alasan">Alasan Koreksi <span class="text-danger" aria-hidden="true">*</span></label>
                    <textarea id="alasan" class="form-control" rows="2" wire:model="alasan" placeholder="Contoh: salah tulis saat input"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Koreksi KKTP -->
<div wire:ignore.self class="modal fade" id="koreksiKktpModal" tabindex="-1" aria-labelledby="koreksiKktpTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="koreksiKktpTitle"><i class="fa-solid fa-bullseye me-2" aria-hidden="true"></i>Koreksi KKTP</h5></div>
            <form wire:submit.prevent="koreksiKktp">
                <div class="modal-body">
                    <div class="alert alert-warning small" role="note"><i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Berlaku untuk seluruh snapshot nilai akhir (semester aktif, mapel ini) yang sudah difinalisasi.</div>
                    <label class="form-label" for="nilaiKktpBaru">KKTP Baru</label>
                    <input id="nilaiKktpBaru" type="number" class="form-control mb-3" min="0" max="100" step="0.01" inputmode="decimal" wire:model="nilaiKktpBaru" placeholder="0 - 100">
                    <label class="form-label" for="alasanKktp">Alasan Koreksi <span class="text-danger" aria-hidden="true">*</span></label>
                    <textarea id="alasanKktp" class="form-control" rows="2" wire:model="alasanKktp" placeholder="Contoh: penyesuaian KKTP setelah review sekolah"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

@script
<script>
    window.addEventListener('close-koreksi-nilai-modal', () => { $('#koreksiNilaiModal').modal('hide'); });
    window.addEventListener('show-koreksi-nilai-modal', () => $('#koreksiNilaiModal').modal('show'));
    window.addEventListener('close-koreksi-kktp-modal', () => { $('#koreksiKktpModal').modal('hide'); });
    window.addEventListener('show-koreksi-kktp-modal', () => $('#koreksiKktpModal').modal('show'));
</script>
@endscript
