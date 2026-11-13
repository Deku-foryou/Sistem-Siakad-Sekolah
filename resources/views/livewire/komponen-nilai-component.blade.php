@section('title', 'Komponen Nilai')
<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')
    {{-- Header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-layer-group me-2 text-success" aria-hidden="true"></i>Komponen Nilai</h1>
            <p class="sa-sub">Atur komponen dan bobot penilaian per semester dan mata pelajaran. Total bobot komponen aktif harus tepat 100%.</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-6">
                <label for="kn-semester" class="form-label">Semester</label>
                <select id="kn-semester" class="form-select" wire:model.live="filterSemester">
                    <option value="">Pilih semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="kn-mapel" class="form-label">Mata Pelajaran</label>
                <select id="kn-mapel" class="form-select" wire:model.live="mapelDipilih">
                    <option value="">Pilih mata pelajaran</option>
                    @foreach ($mapels as $mapel)
                        <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if ((int) $filterSemester < 1 || (int) $mapelDipilih < 1)
        <div class="sa-card"><div class="sa-card-body sa-empty">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>
            <p class="sa-empty-title">Pilih semester dan mata pelajaran</p>
            <p class="sa-empty-text">Setelah dipilih, daftar komponen penilaian dan bobotnya akan ditampilkan.</p>
        </div></div>
    @else
        {{-- Status bobot --}}
        <div class="sa-card mb-3">
            <div class="sa-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="sa-badge {{ abs($totalAktif - 100) < 0.001 ? 'sa-hadir' : 'sa-alpha' }}">
                        Total bobot aktif: {{ number_format((float) $totalAktif, 2, ',', '.') }}%
                    </span>
                    <span class="sa-hint ms-2">Total harus tepat 100% sebelum dapat disimpan.</span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" wire:click="tambahBaris" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i> Tambah Komponen</button>
                    <button class="btn btn-sm btn-primary" wire:click="simpan" wire:loading.attr="disabled" wire:target="simpan" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                        <span wire:loading.remove wire:target="simpan"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i> Simpan Konfigurasi</span>
                        <span wire:loading wire:target="simpan"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyimpan&hellip;</span>
                    </button>
                </div>
            </div>
        </div>

        @if ($terkunci)
            <div class="alert alert-warning" role="alert">
                <i class="fa-solid fa-lock me-2" aria-hidden="true"></i>Sebagian komponen pada semester dan mata pelajaran ini sudah memiliki nilai. Nama, bobot, dan status aktif komponen tersebut tidak dapat diubah, dan komponen tidak dapat dihapus.
            </div>
        @endif

        {{-- Tabel komponen --}}
        <div class="sa-card">
            <div class="sa-card-head">
                <h2 class="sa-card-title"><i class="fa-solid fa-list-ul" aria-hidden="true"></i>Daftar Komponen</h2>
                <span class="sa-count">{{ count($baris) }} komponen</span>
            </div>
            <div class="table-responsive">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Urutan</th>
                            <th>Nama Komponen</th>
                            <th style="width: 150px;">Bobot (%)</th>
                            <th style="width: 90px;" class="text-center">Aktif</th>
                            <th style="width: 90px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as $index => $row)
                            <tr wire:key="komponen-{{ $row['idkomponen_nilai'] ?? 'baru-'.$index }}">
                                <td>
                                    <input type="number" min="1" class="form-control form-control-sm" wire:model="baris.{{ $index }}.urutan" aria-label="Urutan komponen {{ $index + 1 }}">
                                </td>
                                <td>
                                    <input type="text" maxlength="100" class="form-control form-control-sm" placeholder="Contoh: Tugas, UTS, UAS" wire:model.blur="baris.{{ $index }}.nama" aria-label="Nama komponen {{ $index + 1 }}">
                                </td>
                                <td>
                                    <input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm" wire:model.live="baris.{{ $index }}.bobot" aria-label="Bobot komponen {{ $index + 1 }}">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" wire:model.live="baris.{{ $index }}.aktif" aria-label="Aktif {{ $index + 1 }}">
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger" wire:click="hapusBaris({{ $index }})" aria-label="Hapus baris {{ $index + 1 }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"><div class="sa-empty"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><p class="sa-empty-title">Belum ada komponen</p><p class="sa-empty-text">Klik "Tambah Komponen" untuk mulai mengatur.</p></div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
