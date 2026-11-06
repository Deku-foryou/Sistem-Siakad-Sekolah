@section('title', 'Komponen Nilai')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="mb-3">
        <h3 class="fw-bold mb-1">Komponen Nilai</h3>
        <span class="text-muted">Atur komponen dan bobot penilaian per semester dan mata pelajaran. Total bobot komponen aktif harus tepat 100%.</span>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
            <div class="col-md-6">
                <label class="form-label">Semester</label>
                <select class="form-select" wire:model.live="filterSemester">
                    <option value="">Pilih semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Mata Pelajaran</label>
                <select class="form-select" wire:model.live="mapelDipilih">
                    <option value="">Pilih mata pelajaran</option>
                    @foreach ($mapels as $mapel)
                        <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if ((int) $filterSemester < 1 || (int) $mapelDipilih < 1)
        <div class="alert alert-info">Pilih semester dan mata pelajaran untuk mengatur komponen penilaian.</div>
    @else
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div>
                <span class="badge {{ abs($totalAktif - 100) < 0.001 ? 'bg-success' : 'bg-danger' }}">
                    Total bobot aktif: {{ number_format($totalAktif, 2, ',', '.') }}%
                </span>
                <span class="text-muted ms-2">Total harus tepat 100% sebelum dapat disimpan.</span>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm" wire:click="tambahBaris"><i class="fa-solid fa-plus me-1"></i> Tambah Komponen</button>
                <button class="btn btn-primary btn-sm" wire:click="simpan"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Konfigurasi</button>
            </div>
        </div>

        @if ($terkunci)
            <div class="alert alert-warning">
                Sebagian komponen pada semester dan mata pelajaran ini sudah memiliki nilai. Nama, bobot, dan status aktif komponen tersebut tidak dapat diubah, dan komponen tidak dapat dihapus.
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width:70px">Urutan</th>
                        <th>Nama Komponen</th>
                        <th style="width:150px">Bobot (%)</th>
                        <th style="width:90px">Aktif</th>
                        <th style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $index => $row)
                        <tr wire:key="komponen-{{ $row['idkomponen_nilai'] ?? 'baru-'.$index }}">
                            <td>
                                <input type="number" min="1" class="form-control form-control-sm" wire:model="baris.{{ $index }}.urutan">
                            </td>
                            <td>
                                <input type="text" maxlength="100" class="form-control form-control-sm" placeholder="Contoh: Tugas, UTS, UAS" wire:model.blur="baris.{{ $index }}.nama">
                            </td>
                            <td>
                                <input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm" wire:model.live="baris.{{ $index }}.bobot">
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" wire:model.live="baris.{{ $index }}.aktif">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-danger" wire:click="hapusBaris({{ $index }})" title="Hapus baris"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada komponen. Klik "Tambah Komponen" untuk mulai mengatur.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
