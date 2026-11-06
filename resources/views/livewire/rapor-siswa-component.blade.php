@section('title', 'Rapor Saya')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="mb-3">
        <h1 class="h4 fw-bold mb-1">Rapor Saya</h1>
        @if ($siswa)
            <p class="text-body-secondary mb-0">
                {{ $siswa->nama }} &middot; NIS {{ $siswa->nis ?? '-' }} &middot; NISN {{ $siswa->nisn ?? '-' }}
            </p>
        @endif
    </div>

    @if ($pesanGalat)
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ $pesanGalat }}
            <button type="button" class="btn-close" wire:click="$set('galat', '')" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Daftar Rapor Final</div>
        <div class="card-body">
            @if ($daftarRapor->isEmpty())
                <div class="text-body-secondary">Belum ada rapor yang sudah final. Rapor hanya ditampilkan setelah difinalisasi.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Tahun Ajaran</th><th>Semester</th><th>Kelas</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarRapor as $entri)
                                <tr>
                                    <td>{{ $entri['semester']->tahunajaran->nama ?? '-' }}</td>
                                    <td>{{ $entri['semester']->nama }}</td>
                                    <td>{{ $entri['kelas']->nama }} ({{ $entri['kelas']->tingkat }})</td>
                                    <td><span class="badge bg-success">Final</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <label class="form-label fw-semibold mt-3" for="raporKunci">Pilih rapor untuk ditampilkan</label>
                <select id="raporKunci" class="form-select" wire:model.live="raporKunci">
                    <option value="">Pilih semester &amp; kelas</option>
                    @foreach ($daftarRapor as $entri)
                        <option value="{{ $entri['semester']->idsemester }}.{{ $entri['kelas']->idkelas }}">
                            {{ $entri['semester']->tahunajaran->nama ?? '' }} &middot; {{ $entri['semester']->nama }} &middot; {{ $entri['kelas']->nama }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @if ($detail)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold">Detail Rapor &mdash; {{ $detail['siswa']->nama }}</span>
                <button type="button" class="btn btn-sm btn-danger" wire:click="unduhPdf" wire:loading.attr="disabled">
                    <i class="fa-solid fa-file-pdf me-1"></i> Unduh PDF
                </button>
            </div>
            <div class="card-body">
                <div class="text-body-secondary small mb-2">
                    Kelas: {{ $detail['kelas']->nama }} ({{ $detail['kelas']->tingkat }}) &middot;
                    Semester: {{ $detail['konteks']->semesterNama() ?? '-' }} &middot;
                    Tahun Ajaran: {{ $detail['konteks']->tahunAjaranNama() ?? '-' }}
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-3">
                        <thead class="table-light">
                            <tr><th>Mata Pelajaran</th><th class="text-center">Nilai Akhir</th><th class="text-center">KKTP</th><th class="text-center">Keterangan</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($detail['nilai_final'] as $baris)
                                <tr>
                                    <td>{{ $baris->mapel->nama ?? ('Mapel #'.$baris->mapel_idmapel) }}</td>
                                    <td class="text-center">{{ number_format((float) $baris->nilai_akhir, 2, ',', '.') }}</td>
                                    <td class="text-center">{{ $baris->nilai_kktp === null ? '-' : number_format((float) $baris->nilai_kktp, 2, ',', '.') }}</td>
                                    <td class="text-center">{{ $baris->keterangan ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-1">Rekap Kehadiran</div>
                            <div class="small">Hadir: {{ $detail['rekap_kehadiran']['hadir'] }} &middot; Sakit: {{ $detail['rekap_kehadiran']['sakit'] }} &middot; Izin: {{ $detail['rekap_kehadiran']['izin'] }} &middot; Alpha: {{ $detail['rekap_kehadiran']['alpha'] }}</div>
                            <div class="text-body-secondary small">Total hari tercatat: {{ $detail['rekap_kehadiran']['total'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-1">Catatan Wali Kelas</div>
                            <div class="small">{{ $detail['catatan']?->catatan ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
