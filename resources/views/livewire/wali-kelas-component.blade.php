@section('title', 'Wali Kelas & Rapor')
<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')
    {{-- Header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-chalkboard-user me-2 text-success" aria-hidden="true"></i>Wali Kelas &amp; Rapor</h1>
            @if ($konteks->valid())
                <p class="sa-sub">Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot; Semester {{ $konteks->semesterNama() }}</p>
            @endif
        </div>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Konteks akademik belum valid. Halaman rapor tidak dapat dibuka sampai tahun ajaran/semester aktif tersedia.</div>
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
        {{-- Pilih kelas --}}
        <div class="sa-card sa-filter mb-3">
            <label class="form-label fw-semibold" for="kelas">Kelas Tanggung Jawab</label>
            <select id="kelas" class="form-select" wire:model.live="kelasId">
                <option value="">Pilih kelas</option>
                @foreach ($daftarKelas as $item)
                    <option value="{{ $item->idkelas }}">
                        {{ $item->nama }} ({{ $item->tingkat }}) &middot; Wali: {{ $item->wali->nama ?? 'Belum ada wali' }}
                    </option>
                @endforeach
            </select>
            @if ($daftarKelas->isEmpty())
                <div class="form-text text-danger">Tidak ada kelas yang menjadi tanggung jawab Anda pada tahun ajaran aktif.</div>
            @endif
        </div>

        @if ($kelasId !== '')
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                @if ($status && $status->status === 'final')
                    <span class="sa-badge sa-hadir"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Rapor Final</span>
                @else
                    <span class="sa-badge sa-netral">Draf / Belum Final</span>
                @endif
                <span class="sa-hint">{{ $daftarSiswa->count() }} siswa aktif &middot; Nilai dari snapshot final; PDF wajib final.</span>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('finalisasirapor') }}"><i class="fa-solid fa-clipboard-check me-1" aria-hidden="true"></i>Ke Finalisasi Rapor</a>
            </div>

            <div class="sa-card mb-3">
                <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-users" aria-hidden="true"></i>Daftar Siswa &amp; Detail Rapor</h2></div>
                <div class="sa-card-body">
                    <label class="form-label fw-semibold" for="siswa">Siswa (sesuai histori kelas &amp; semester)</label>
                    <select id="siswa" class="form-select" wire:model.live="siswaId">
                        <option value="">Pilih siswa</option>
                        @foreach ($daftarSiswa as $item)
                            <option value="{{ $item->idsiswa }}">{{ $item->nama }} &middot; NIS {{ $item->nis ?? '-' }}</option>
                        @endforeach
                    </select>
                    @if ($daftarSiswa->isEmpty())
                        <div class="form-text text-danger">Tidak ada siswa aktif pada kelas ini.</div>
                    @endif
                </div>
            </div>
        @endif

        @if ($detail)
            <div class="sa-card mb-3">
                <div class="sa-card-head">
                    <h2 class="sa-card-title"><i class="fa-solid fa-file-lines" aria-hidden="true"></i>Rapor: {{ $detail['siswa']->nama }}</h2>
                    <button type="button" class="btn btn-sm btn-danger" wire:click="unduhPdf" wire:loading.attr="disabled" @disabled(!$detail['is_final'])>
                        <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i> Cetak PDF
                    </button>
                </div>
                <div class="sa-card-body">
                    <div class="sa-hint mb-2">
                        NIS: {{ $detail['siswa']->nis ?? '-' }} &middot; NISN: {{ $detail['siswa']->nisn ?? '-' }}
                        &middot; Kelas: {{ $detail['kelas']->nama }} ({{ $detail['kelas']->tingkat }})
                    </div>
                    @if (!$detail['is_final'])
                        <div class="alert alert-warning" role="alert"><strong>Rapor kelas ini belum final.</strong> Nilai disembunyikan sampai finalisasi; PDF tidak dapat dicetak sebagai rapor resmi.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table sa-table table-bordered mb-3">
                                <thead>
                                    <tr><th>Mata Pelajaran</th><th class="text-center">Nilai Akhir</th><th class="text-center">KKTP</th><th class="text-center">Keterangan</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($detail['nilai_final'] as $baris)
                                        <tr>
                                            <td>{{ $baris->mapel->nama ?? ('Mapel #'.$baris->mapel_idmapel) }}</td>
                                            <td class="text-center fw-semibold">{{ number_format((float) $baris->nilai_akhir, 2, ',', '.') }}</td>
                                            <td class="text-center">{{ $baris->nilai_kktp === null ? '-' : number_format((float) $baris->nilai_kktp, 2, ',', '.') }}</td>
                                            <td class="text-center">{{ $baris->keterangan ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4"><div class="sa-empty"><i class="fa-regular fa-clipboard" aria-hidden="true"></i><p class="sa-empty-text">Tidak ada snapshot nilai final untuk siswa ini.</p></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100" style="border-color: var(--sa-border) !important; background: var(--sa-green-faint);">
                                <div class="fw-semibold mb-1"><i class="fa-solid fa-clipboard-check me-1" aria-hidden="true"></i>Rekap Kehadiran (informasi)</div>
                                <div class="small">Hadir: {{ $detail['rekap_kehadiran']['hadir'] }} &middot; Sakit: {{ $detail['rekap_kehadiran']['sakit'] }} &middot; Izin: {{ $detail['rekap_kehadiran']['izin'] }} &middot; Alpha: {{ $detail['rekap_kehadiran']['alpha'] }}</div>
                                <div class="sa-hint">Total hari tercatat: {{ $detail['rekap_kehadiran']['total'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="catatan">Catatan Wali Kelas</label>
                            <textarea id="catatan" class="form-control" rows="3" wire:model="catatan" maxlength="1000" placeholder="Tulis catatan wali kelas..."></textarea>
                            <button type="button" class="btn btn-sm btn-primary mt-2" wire:click="simpanCatatan" wire:loading.attr="disabled" wire:target="simpanCatatan">
                                <span wire:loading.remove wire:target="simpanCatatan"><i class="fa-solid fa-save me-1" aria-hidden="true"></i>Simpan Catatan</span>
                                <span wire:loading wire:target="simpanCatatan"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyimpan&hellip;</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
