@section('title', 'Input Nilai')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Input Nilai</h1>
            @if ($konteks->valid())
                <p class="text-body-secondary mb-0">
                    Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot;
                    Semester {{ $konteks->semesterNama() }}
                </p>
            @endif
        </div>
        <span class="badge bg-success px-3 py-2">Guru</span>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold">
                <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                Konteks akademik belum valid
            </div>
            <div class="mt-1">
                Belum ada tahun ajaran/semester aktif atau semester tidak cocok dengan tahun ajaran.
                Input nilai tidak dapat dilakukan sampai konteks diperbaiki.
            </div>
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
                <label class="form-label fw-semibold" for="penugasan">Penugasan Mengajar</label>
                <select id="penugasan" class="form-select" wire:model.live="penugasanId">
                    <option value="">Pilih kelas dan mata pelajaran</option>
                    @foreach ($daftarPenugasan as $item)
                        <option value="{{ $item->idpenugasan_mengajar }}">
                            {{ $item->kelas->nama ?? '-' }} &middot; {{ $item->mapel->nama ?? '-' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">
                    Hanya penugasan mengajar milik Anda pada semester aktif yang ditampilkan.
                </div>
            </div>
        </div>

        @if ($daftarPenugasan->isEmpty())
            <div class="alert alert-info" role="status">
                Belum ada penugasan mengajar untuk Anda pada semester aktif.
                Hubungi administrator agar penugasan dibuat terlebih dahulu.
            </div>
        @elseif (! $penugasan)
            <div class="alert alert-secondary" role="status">
                Pilih penugasan mengajar untuk mulai menginput nilai.
            </div>
        @else
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-body-secondary small">Kelas</div>
                            <div class="fw-semibold">{{ $penugasan->kelas->nama ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-body-secondary small">Mata Pelajaran</div>
                            <div class="fw-semibold">{{ $penugasan->mapel->nama ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-body-secondary small">Total Bobot Komponen Aktif</div>
                            <div class="fw-semibold">{{ number_format($totalBobotAktif, 2, ',', '.') }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            @if (! $konfigurasiValid)
                <div class="alert alert-danger" role="alert">
                    <div class="fw-semibold">
                        <i class="fa-solid fa-circle-exclamation me-2" aria-hidden="true"></i>
                        Konfigurasi komponen nilai belum valid
                    </div>
                    <div class="mt-1">
                        Total bobot komponen aktif harus tepat 100%. Input nilai diblokir sampai
                        konfigurasi komponen diperbaiki oleh administrator.
                    </div>
                </div>
            @endif

            @if ($terkunci)
                <div class="alert alert-warning" role="alert">
                    <div class="fw-semibold">
                        <i class="fa-solid fa-lock me-2" aria-hidden="true"></i>
                        Nilai terkunci
                    </div>
                    <div class="mt-1">
                        Rapor kelas ini sudah difinalisasi atau snapshot nilai akhir sudah dibuat.
                        Perubahan nilai memerlukan mekanisme koreksi resmi yang belum tersedia pada tahap ini.
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                    <div>
                        <span class="fw-semibold">Daftar Nilai</span>
                        <span class="text-body-secondary small ms-2">
                            {{ $rekap->count() }} siswa &middot; {{ $komponen->count() }} komponen aktif
                        </span>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        wire:click="simpan"
                        wire:loading.attr="disabled"
                        @disabled(! $konfigurasiValid || $terkunci)
                    >
                        <span wire:loading.remove wire:target="simpan">
                            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i> Simpan Nilai
                        </span>
                        <span wire:loading wire:target="simpan">Menyimpan...</span>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 45px;">No</th>
                                <th style="min-width: 180px;">Nama Siswa</th>
                                @foreach ($komponen as $item)
                                    <th class="text-center" style="min-width: 110px;">
                                        {{ $item->nama }}
                                        <div class="text-body-secondary small fw-normal">
                                            Bobot {{ rtrim(rtrim(number_format((float) $item->bobot, 2, ',', '.'), '0'), ',') }}%
                                        </div>
                                    </th>
                                @endforeach
                                <th class="text-center" style="min-width: 110px;">Nilai Draft</th>
                                <th class="text-center" style="min-width: 130px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekap as $siswaId => $baris)
                                <tr wire:key="siswa-{{ $siswaId }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $baris['siswa']->nama }}</div>
                                        <div class="text-body-secondary small">NIS {{ $baris['siswa']->nis ?? '-' }}</div>
                                    </td>
                                    @foreach ($komponen as $item)
                                        <td>
                                            <input
                                                type="number"
                                                class="form-control form-control-sm text-center"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                inputmode="decimal"
                                                placeholder="-"
                                                wire:model="nilai.{{ $siswaId }}.{{ $item->idkomponen_nilai }}"
                                                @disabled(! $konfigurasiValid || $terkunci)
                                            >
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold">
                                        {{ $baris['nilai_draft'] === null ? '—' : number_format($baris['nilai_draft'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if (! $konfigurasiValid)
                                            <span class="badge bg-danger">Konfigurasi belum valid</span>
                                        @elseif ($terkunci)
                                            <span class="badge bg-warning text-dark">Rapor final</span>
                                        @elseif ($baris['lengkap'])
                                            <span class="badge bg-success">Lengkap (draft)</span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Belum lengkap {{ $baris['jumlah_terisi'] }}/{{ $baris['jumlah_komponen'] }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + $komponen->count() }}" class="text-center text-body-secondary py-3">
                                        Belum ada siswa aktif pada kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="alert alert-light border mt-3 small mb-0" role="note">
                <div class="fw-semibold mb-1">Catatan perhitungan draft</div>
                <ul class="mb-0 ps-3">
                    <li>Nilai draft dihitung dari komponen aktif yang sudah diisi saja, dinormalisasi terhadap bobot yang sudah terisi.</li>
                    <li>Jika komponen aktif belum lengkap, hasilnya sementara dan bukan nilai final.</li>
                    <li>Nilai draft bukan <em>nilai akhir</em> dan tidak difinalisasi pada tahap ini.</li>
                    <li>Mengosongkan kolom lalu menyimpan tidak menghapus nilai yang sudah tersimpan.</li>
                </ul>
            </div>
        @endif
    @endif
</div>
