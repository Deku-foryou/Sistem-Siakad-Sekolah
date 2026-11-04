@section('title', 'Pengumuman')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Pengumuman</h1>
            <p class="text-body-secondary mb-0">
                Informasi resmi dari sekolah untuk Anda.
                @if($belumDibaca > 0) <span class="badge bg-danger">{{ $belumDibaca }} belum dibaca</span> @endif
            </p>
        </div>
        <span class="badge bg-success px-3 py-2">Siswa</span>
    </div>

    @if (! $siswa)
        <div class="alert alert-warning" role="alert">
            Akun ini belum terhubung ke data siswa. Pengumuman belum dapat ditampilkan. Hubungi administrator.
        </div>
    @else
        <ul class="nav nav-tabs mb-3" role="tablist" aria-label="Jenis pengumuman">
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === 'aktif' ? 'true' : 'false' }}"
                    class="nav-link {{ $tab === 'aktif' ? 'active' : '' }}"
                    wire:click="gantiTab('aktif')"
                >Pengumuman Aktif</button>
            </li>
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === 'riwayat' ? 'true' : 'false' }}"
                    class="nav-link {{ $tab === 'riwayat' ? 'active' : '' }}"
                    wire:click="gantiTab('riwayat')"
                >Riwayat Pengumuman</button>
            </li>
        </ul>

        @if ($tab === 'aktif')
            @php $daftar = $daftarAktif; $judulKosong = 'Belum ada pengumuman aktif'; @endphp
        @else
            @php $daftar = $riwayat; $judulKosong = 'Belum ada riwayat pengumuman'; @endphp
        @endif

        @if ($daftar && $daftar->total() > 0)
            <div class="row g-3">
                @foreach ($daftar as $item)
                    @php $rowBaca = $item->baca->firstWhere('siswa_idsiswa', $siswa->idsiswa); @endphp
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100 {{ $rowBaca && $rowBaca->dibaca_pada === null ? 'border border-success border-2' : '' }}">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <h2 class="h6 fw-bold mb-0 flex-grow-1">{{ $item->judul }}</h2>
                                    @if($rowBaca && $rowBaca->dibaca_pada === null)
                                        <span class="badge bg-success flex-shrink-0">Baru</span>
                                    @endif
                                </div>
                                <p class="text-body-secondary small mb-2" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                                    {!! \Illuminate\Support\Str::limit($item->isi, 160) !!}
                                </p>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <span class="badge bg-secondary">{{ ucfirst($item->kategori) }}</span>
                                    @if($item->prioritas === 'mendesak')
                                        <span class="badge bg-danger">Mendesak</span>
                                    @elseif($item->prioritas === 'tinggi')
                                        <span class="badge bg-warning text-dark">Tinggi</span>
                                    @endif
                                    @if($item->status === 'arsip')
                                        <span class="badge bg-secondary">Arsip</span>
                                    @endif
                                </div>
                                <div class="text-body-secondary small mb-2">
                                    <i class="fa-regular fa-calendar-days me-1" aria-hidden="true"></i>
                                    {{ $item->waktu_terbit?->format('d/m/Y') ?? '-' }}
                                    @if($item->berlaku_dari || $item->berlaku_sampai)
                                        &middot; Berlaku {{ $item->berlaku_dari?->format('d/m/Y') ?? '-' }} – {{ $item->berlaku_sampai?->format('d/m/Y') ?? '-' }}
                                    @endif
                                </div>
                                <button class="btn btn-sm btn-outline-success" wire:click="bukaDetail({{ $item->idpengumuman }})">Baca selengkapnya</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <small class="text-muted">Menampilkan {{ $daftar->firstItem() }}–{{ $daftar->lastItem() }} dari {{ $daftar->total() }} pengumuman</small>
                <div>{{ $daftar->links() }}</div>
            </div>
        @else
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fa-regular fa-clipboard fs-2 text-secondary mb-2" aria-hidden="true"></i>
                    <p class="fw-semibold mb-1">{{ $judulKosong }}</p>
                    <p class="text-body-secondary mb-0">
                        @if($tab === 'aktif')
                            Belum ada pengumuman aktif yang ditujukan kepada Anda.
                        @else
                            Belum ada riwayat pengumuman untuk Anda.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        @if($detail)
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <h2 class="h5 fw-bold mb-0 flex-grow-1">{{ $detail->judul }}</h2>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="tutupDetail">Tutup</button>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="badge bg-secondary">{{ ucfirst($detail->kategori) }}</span>
                        @if($detail->prioritas === 'mendesak')
                            <span class="badge bg-danger">Mendesak</span>
                        @elseif($detail->prioritas === 'tinggi')
                            <span class="badge bg-warning text-dark">Tinggi</span>
                        @elseif($detail->prioritas === 'rendah')
                            <span class="badge bg-secondary">Rendah</span>
                        @else
                            <span class="badge bg-info text-dark">Sedang</span>
                        @endif
                        <span class="badge bg-light text-dark">{{ $detail->waktu_terbit?->format('d/m/Y H:i') ?? '-' }}</span>
                    </div>
                    <div class="mb-3" style="white-space: pre-wrap;">{{ $detail->isi }}</div>
                    <div class="text-body-secondary small">
                        @if($baca && $baca->dibaca_pada)
                            Dibaca pada {{ $baca->dibaca_pada->format('d/m/Y H:i') }}
                        @else
                            <span class="text-success fw-semibold">Ditandai sudah dibaca</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
