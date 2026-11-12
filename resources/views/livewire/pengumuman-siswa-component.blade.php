@section('title', 'Pengumuman')

<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-bullhorn me-2 text-success" aria-hidden="true"></i>Pengumuman</h1>
            <p class="sa-sub">Informasi resmi dari sekolah untuk Anda.</p>
        </div>
        @if($belumDibaca > 0)
            <span class="sa-badge sa-alpha">{{ $belumDibaca }} belum dibaca</span>
        @endif
    </div>

    @if (! $siswa)
        <div class="alert alert-warning" role="alert">
            Akun ini belum terhubung ke data siswa. Pengumuman belum dapat ditampilkan. Hubungi administrator.
        </div>
    @else
        <ul class="nav nav-tabs mb-3" role="tablist" aria-label="Jenis pengumuman">
            <li class="nav-item" role="presentation">
                <button type="button" role="tab" aria-selected="{{ $tab === 'aktif' ? 'true' : 'false' }}"
                    class="nav-link {{ $tab === 'aktif' ? 'active' : '' }}" wire:click="gantiTab('aktif')">Pengumuman Aktif</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" role="tab" aria-selected="{{ $tab === 'riwayat' ? 'true' : 'false' }}"
                    class="nav-link {{ $tab === 'riwayat' ? 'active' : '' }}" wire:click="gantiTab('riwayat')">Riwayat Pengumuman</button>
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
                    @php $rowBaca = $item->baca->firstWhere('siswa_idsiswa', $siswa->idsiswa); $belumBaca = $rowBaca && $rowBaca->dibaca_pada === null; @endphp
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="sa-card h-100" @if($belumBaca) style="border-color: var(--sa-green);" @endif>
                            <div class="sa-card-body d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <h2 class="h6 fw-bold mb-0 text-break">{{ $item->judul }}</h2>
                                    @if($belumBaca) <span class="sa-badge sa-hadir flex-shrink-0">Baru</span> @endif
                                </div>
                                <p class="text-body-secondary small mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    {{ \Illuminate\Support\Str::limit($item->isi, 140) }}
                                </p>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <span class="sa-badge sa-netral">{{ ucfirst($item->kategori) }}</span>
                                    @if($item->prioritas === 'mendesak')
                                        <span class="sa-badge sa-alpha">Mendesak</span>
                                    @elseif($item->prioritas === 'tinggi')
                                        <span class="sa-badge sa-sakit">Tinggi</span>
                                    @endif
                                    @if($item->status === 'arsip')
                                        <span class="sa-badge sa-netral">Arsip</span>
                                    @endif
                                </div>
                                <div class="sa-hint mb-2">
                                    <i class="fa-regular fa-calendar-days me-1" aria-hidden="true"></i>{{ $item->waktu_terbit?->format('d/m/Y') ?? '-' }}
                                    @if($item->berlaku_dari || $item->berlaku_sampai)
                                        &middot; {{ $item->berlaku_dari?->format('d/m/Y') ?? '-' }} &ndash; {{ $item->berlaku_sampai?->format('d/m/Y') ?? '-' }}
                                    @endif
                                </div>
                                <div class="mt-auto">
                                    <button class="btn btn-sm btn-outline-success" wire:click="bukaDetail({{ $item->idpengumuman }})" aria-label="Baca pengumuman {{ $item->judul }}">Baca selengkapnya</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sa-card-foot mt-3">
                <small class="text-body-secondary">Menampilkan {{ $daftar->firstItem() }}&ndash;{{ $daftar->lastItem() }} dari {{ $daftar->total() }} pengumuman</small>
                <div>{{ $daftar->links() }}</div>
            </div>
        @else
            <div class="sa-card">
                <div class="sa-card-body sa-empty">
                    <i class="fa-regular fa-clipboard" aria-hidden="true"></i>
                    <p class="sa-empty-title">{{ $judulKosong }}</p>
                    <p class="sa-empty-text">
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
            <div class="sa-card mt-4">
                <div class="sa-card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h2 class="h5 fw-bold mb-0 text-break">{{ $detail->judul }}</h2>
                        <button class="btn btn-sm btn-outline-secondary flex-shrink-0" wire:click="tutupDetail">Tutup</button>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="sa-badge sa-netral">{{ ucfirst($detail->kategori) }}</span>
                        @if($detail->prioritas === 'mendesak')
                            <span class="sa-badge sa-alpha">Mendesak</span>
                        @elseif($detail->prioritas === 'tinggi')
                            <span class="sa-badge sa-sakit">Tinggi</span>
                        @elseif($detail->prioritas === 'sedang')
                            <span class="sa-badge sa-izin">Sedang</span>
                        @else
                            <span class="sa-badge sa-netral">Rendah</span>
                        @endif
                        <span class="sa-badge sa-netral">{{ $detail->waktu_terbit?->format('d/m/Y H:i') ?? '-' }}</span>
                        @if($detail->status === 'arsip')
                            <span class="sa-badge sa-netral">Arsip</span>
                        @endif
                    </div>
                    <div class="mb-3" style="white-space: pre-wrap;">{{ $detail->isi }}</div>
                    <div class="sa-hint">
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
