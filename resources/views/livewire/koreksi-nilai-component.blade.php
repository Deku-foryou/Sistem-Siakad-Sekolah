@section('title', 'Koreksi Nilai')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Koreksi Nilai</h1>
            @if ($konteks->valid())
                <p class="text-body-secondary mb-0">
                    Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot;
                    Semester {{ $konteks->semesterNama() }}
                </p>
            @endif
        </div>
        <span class="badge bg-success px-3 py-2">Superadmin</span>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold">
                <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                Konteks akademik belum valid
            </div>
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
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ $sukses }}
            <button type="button" class="btn-close" wire:click="$set('sukses', '')" aria-label="Tutup"></button>
        </div>
    @endif

    @if ($konteks->valid())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
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
                            <span class="badge bg-success">Rapor final</span>
                            <span class="text-body-secondary">
                                difinalisasi oleh {{ $nilaiFinal->difinalisasiOleh->name ?? '-' }}
                                pada {{ $nilaiFinal->difinalisasi_pada?->format('d/m/Y H:i') ?? '-' }}
                            </span>
                        @else
                            <span class="badge bg-secondary">Draf</span>
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
        </div>

        @if ($mapelId !== '' && $mapelId !== null)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2 fw-semibold">
                    <i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>Koreksi Nilai Komponen
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 160px;">Siswa</th>
                                <th style="min-width: 140px;">Komponen</th>
                                <th class="text-center" style="width: 110px;">Nilai</th>
                                <th class="text-center" style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($barisNilai as $baris)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $baris->siswa->nama ?? ('Siswa #'.$baris->siswa_idsiswa) }}</div>
                                        <div class="text-body-secondary small">NIS {{ $baris->siswa->nis ?? '-' }}</div>
                                    </td>
                                    <td>{{ $baris->komponenNilai->nama ?? ('Komponen #'.$baris->komponen_nilai_idkomponen_nilai) }}</td>
                                    <td class="text-center">{{ number_format((float) $baris->nilai, 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            wire:click="bukaKoreksiKomponen({{ $baris->idnilai_siswa }})"
                                            @disabled(! $nilaiFinal || $nilaiFinal->status !== 'final')
                                        >
                                            <i class="fa-solid fa-pen-to-square me-1" aria-hidden="true"></i> Koreksi
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-body-secondary py-3">Belum ada nilai tersimpan untuk mapel ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white small text-body-secondary">
                    Koreksi komponen menghitung ulang snapshot nilai akhir siswa terkait pada transaksi yang sama. Nilai lama/baru dan alasan dicatat di log.
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2 fw-semibold">
                    <i class="fa-solid fa-clock-rotate-left me-2" aria-hidden="true"></i>Log Perubahan (30 terakhir)
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 130px;">Waktu</th>
                                <th style="width: 130px;">Target</th>
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
                                            <span class="text-body-secondary">
                                                {{ $log->nilaiSiswa?->siswa?->nama ?? ('#'.$log->nilai_siswa_idnilai_siswa) }}
                                                &middot; {{ $log->nilaiSiswa?->komponenNilai?->nama }}
                                            </span>
                                        @else
                                            Nilai akhir<br>
                                            <span class="text-body-secondary">{{ $log->nilaiAkhir?->siswa?->nama ?? ('#'.$log->nilai_akhir_idnilai_akhir) }}</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        {{ $log->nilai_lama === null ? '—' : number_format((float) $log->nilai_lama, 2, ',', '.') }}
                                        &rarr;
                                        {{ $log->nilai_baru === null ? '—' : number_format((float) $log->nilai_baru, 2, ',', '.') }}
                                    </td>
                                    <td class="small">{{ $log->alasan }}</td>
                                    <td class="small">{{ $log->diubahOleh?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-3">Belum ada log perubahan untuk mapel ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 fw-semibold">
                <i class="fa-solid fa-bullseye me-2" aria-hidden="true"></i>Koreksi KKTP (semester aktif)
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
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
                                <td class="text-center">{{ number_format((float) $kktp->nilai_minimal, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        wire:click="bukaKoreksiKktp({{ $kktp->idkktp_mapel }})"
                                    >
                                        <i class="fa-solid fa-bullseye me-1" aria-hidden="true"></i> Koreksi
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-body-secondary py-3">Belum ada konfigurasi KKTP pada semester aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-body-secondary">
                Koreksi KKTP hanya untuk rapor final yang sudah memiliki snapshot. Konfigurasi KKTP biasa tetap dilakukan dari menu KKTP.
            </div>
        </div>
    @endif
</div>

<!-- Modal Koreksi Nilai Komponen -->
<div wire:ignore.self class="modal fade" id="koreksiNilaiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Koreksi Nilai Komponen</h5></div>
            <form wire:submit.prevent="koreksiKomponen">
                <div class="modal-body">
                    <div class="alert alert-warning small" role="note">
                        Nilai rapor sudah final. Koreksi mencatat nilai lama/baru dan alasan ke log. Tidak ada penghapusan nilai.
                    </div>
                    <label class="form-label" for="nilaiBaru">Nilai Baru</label>
                    <input id="nilaiBaru" type="number" class="form-control mb-2" min="0" max="100" step="0.01" inputmode="decimal" wire:model="nilaiBaru" placeholder="0 - 100">
                    <label class="form-label" for="alasan">Alasan Koreksi <span class="text-danger">*</span></label>
                    <textarea id="alasan" class="form-control" rows="2" wire:model="alasan" placeholder="Contoh: salah tulis saat input"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger">Simpan Koreksi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Koreksi KKTP -->
<div wire:ignore.self class="modal fade" id="koreksiKktpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Koreksi KKTP</h5></div>
            <form wire:submit.prevent="koreksiKktp">
                <div class="modal-body">
                    <div class="alert alert-warning small" role="note">
                        Berlaku untuk seluruh snapshot nilai akhir (semester aktif, mapel ini) yang sudah difinalisasi.
                    </div>
                    <label class="form-label" for="nilaiKktpBaru">KKTP Baru</label>
                    <input id="nilaiKktpBaru" type="number" class="form-control mb-2" min="0" max="100" step="0.01" inputmode="decimal" wire:model="nilaiKktpBaru" placeholder="0 - 100">
                    <label class="form-label" for="alasanKktp">Alasan Koreksi <span class="text-danger">*</span></label>
                    <textarea id="alasanKktp" class="form-control" rows="2" wire:model="alasanKktp" placeholder="Contoh: penyesuaian KKTP setelah review sekolah"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-warning">Simpan Koreksi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.addEventListener('close-koreksi-nilai-modal', () => { $('#koreksiNilaiModal').modal('hide'); });
    window.addEventListener('show-koreksi-nilai-modal', () => $('#koreksiNilaiModal').modal('show'));
    window.addEventListener('close-koreksi-kktp-modal', () => { $('#koreksiKktpModal').modal('hide'); });
    window.addEventListener('show-koreksi-kktp-modal', () => $('#koreksiKktpModal').modal('show'));
</script>
