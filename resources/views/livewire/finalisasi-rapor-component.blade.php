@section('title', 'Finalisasi Rapor')
<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')
    {{-- Header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-lock me-2 text-success" aria-hidden="true"></i>Finalisasi Rapor</h1>
            @if ($konteks->valid())
                <p class="sa-sub">Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot; Semester {{ $konteks->semesterNama() }}</p>
            @endif
        </div>
        <span class="sa-chip"><i class="fa-solid {{ $levelLengkap === 'superadmin' ? 'fa-user-shield' : 'fa-user-tie' }}" aria-hidden="true"></i>{{ $levelLengkap === 'superadmin' ? 'Superadmin' : 'Wali Kelas / Guru' }}</span>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Konteks akademik belum valid</div>
            <div class="mt-1">Tahun ajaran/semester aktif tidak tersedia. Finalisasi tidak dapat dilakukan sampai konteks diperbaiki.</div>
        </div>
    @endif

    @if ($pesanGalat)
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ $pesanGalat }}
            <button type="button" class="btn-close" wire:click="$set('galat', '')" aria-label="Tutup" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
        </div>
    @endif

    @if ($sukses)
        <div class="alert alert-success alert-dismissible" role="status">
            {{ $sukses }}
            <button type="button" class="btn-close" wire:click="$set('sukses', '')" aria-label="Tutup" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup pesan"></button>
        </div>
    @endif

    @if ($daftarKesalahan !== [])
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2"><i class="fa-solid fa-circle-exclamation me-2" aria-hidden="true"></i>Finalisasi dibatalkan. Perbaiki {{ count($daftarKesalahan) }} hal berikut:</div>
            <ul class="mb-0 ps-3">
                @foreach ($daftarKesalahan as $kesalahan)
                    <li>{{ $kesalahan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($konteks->valid())
        {{-- Pilih kelas --}}
        <div class="sa-card sa-filter mb-3">
            <label class="form-label fw-semibold" for="kelas">Kelas yang Difinalisasi</label>
            <select id="kelas" class="form-select" wire:model.live="kelasId">
                <option value="">Pilih kelas yang difinalisasi</option>
                @foreach ($daftarKelas as $item)
                    <option value="{{ $item->idkelas }}">
                        {{ $item->nama }} ({{ $item->tingkat }}) &middot; Wali: {{ $item->wali->nama ?? 'Belum ada wali' }} &middot; {{ $item->jumlah_siswa_aktif }} siswa aktif
                    </option>
                @endforeach
            </select>
            @if ($daftarKelas->isEmpty())
                <div class="form-text text-danger">Tidak ada kelas yang menjadi tanggung jawab Anda pada tahun ajaran aktif. Wali kelas hanya melihat kelas sesuai penugasan wali (`kelas.guru_idguru`).</div>
            @endif
        </div>

        @if ($kelas)
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="sa-card h-100">
                        <div class="sa-card-body">
                            <div class="sa-hint">Kelas</div>
                            <div class="fw-semibold fs-5">{{ $kelas->nama }} ({{ $kelas->tingkat }})</div>
                            <div class="sa-hint mt-1">
                                Wali: {{ $kelas->wali->nama ?? 'Belum ada wali' }}
                                @if ($kelas->guru_idguru)
                                    &middot; Guru #{{ $kelas->guru_idguru }}
                                @endif
                            </div>
                            <div class="sa-hint">
                                Siswa aktif: {{ $hasilPeriksa['ringkasan']['jumlah_siswa'] ?? '-' }} &middot; Mapel (penugasan): {{ $hasilPeriksa['ringkasan']['jumlah_mapel'] ?? '-' }} &middot; Nilai tersimpan: {{ $hasilPeriksa['ringkasan']['jumlah_nilai'] ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="sa-card h-100">
                        <div class="sa-card-body">
                            <div class="sa-hint">Status Rapor</div>
                            @if ($status && $status->status === 'final')
                                <span class="sa-badge sa-hadir fs-6 mt-1"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i> Final</span>
                                <div class="sa-hint mt-2">Difinalisasi oleh {{ $status->difinalisasiOleh->name ?? '-' }} pada {{ $status->difinalisasi_pada?->format('d/m/Y H:i') ?? '-' }}</div>
                            @else
                                <span class="sa-badge sa-netral fs-6 mt-1">Draf / Belum difinalisasi</span>
                                <div class="sa-hint mt-2">Snapshot nilai akhir akan dibuat saat finalisasi.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="sa-card mb-3">
                <div class="sa-card-head">
                    <h2 class="sa-card-title"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>Kesiapan Finalisasi</h2>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="periksa" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Periksa ulang data">
                            <i class="fa-solid fa-rotate me-1" aria-hidden="true"></i> Periksa Ulang
                        </button>
                        <button type="button" class="btn btn-sm btn-success" wire:click="finalisasi" wire:loading.attr="disabled" @disabled($status && $status- data-bs-toggle="tooltip" data-bs-placement="top" title="Finalisasi rapor">status === 'final')>
                            <i class="fa-solid fa-clipboard-check me-1" aria-hidden="true"></i>
                            <span wire:loading.remove wire:target="finalisasi">Finalisasi Sekarang</span>
                            <span wire:loading wire:target="finalisasi">Memfinalisasi...</span>
                        </button>
                    </div>
                </div>
                <div class="sa-card-body">
                    @if ($hasilPeriksa['errors'] === [])
                        <div class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Rapor kelas ini siap difinalisasi.</div>
                        <div class="sa-hint">Seluruh siswa aktif, seluruh mapel dengan penugasan, konfigurasi bobot 100%, dan KKTP tersedia.</div>
                    @else
                        <div class="fw-semibold text-danger mb-2"><i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i>{{ count($hasilPeriksa['errors']) }} hal yang harus diperbaiki:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($hasilPeriksa['errors'] as $kesalahan)
                                <li>{{ $kesalahan }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="alert alert-light border small mb-0" role="note">
                <div class="fw-semibold mb-1">Setelah finalisasi</div>
                <ul class="mb-0 ps-3">
                    <li>Nilai final tidak dapat diubah melalui input nilai biasa.</li>
                    <li>Koreksi berjalan melalui jalur koreksi terkendali dan hanya wewenang superadmin; setiap koreksi mencatat nilai lama/baru, alasan, dan pelaku.</li>
                    <li>Snapshot nilai akhir dan perubahan status rapor menjadi `final` dibuat dalam satu transaksi.</li>
                </ul>
            </div>
        @endif
    @endif
</div>
