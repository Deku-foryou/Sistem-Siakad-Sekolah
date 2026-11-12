@section('title', 'Notifikasi')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Notifikasi</h1>
            <p class="text-body-secondary mb-0">Pemberitahuan terbaru untuk Anda &middot; urut terbaru</p>
        </div>
        @if ($jumlahBelumDibaca > 0)
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="tandaiSemuaDibaca" wire:loading.attr="disabled">
                <i class="fa-solid fa-check-double me-1"></i> Tandai Semua Dibaca
            </button>
        @endif
    </div>

    @if ($galat)
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ $galat }}
            <button type="button" class="btn-close" wire:click="$set('galat', '')" aria-label="Tutup"></button>
        </div>
    @endif

    @if ($sukses)
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ $sukses }}
            <button type="button" class="btn-close" wire:click="$set('sukses', '')" aria-label="Tutup"></button>
        </div>
    @endif

    <!-- Tabs & filter jenis -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item">
                    <button type="button" class="nav-link @if ($tab === 'all') active @endif" wire:click="$set('tab', 'all')">
                        Semua
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link @if ($tab === 'unread') active @endif" wire:click="$set('tab', 'unread')">
                        Belum Dibaca
                        @if ($jumlahBelumDibaca > 0)
                            <span class="badge bg-danger ms-1">{{ $jumlahBelumDibaca }}</span>
                        @endif
                    </button>
                </li>
            </ul>
            <label class="visually-hidden" for="filterJenis">Jenis</label>
            <select id="filterJenis" class="form-select form-select-sm" style="width: auto;" wire:model.live="filterJenis">
                <option value="">Semua jenis</option>
                @foreach ($jenisLabel as $kode => $label)
                    <option value="{{ $kode }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @forelse ($daftar as $notifikasi)
        @php
            $dibaca = $notifikasi->penerima->first()?->dibaca_pada !== null;
        @endphp
        <div class="card border-0 shadow-sm mb-2 {{ $dibaca ? '' : 'border-start border-4 border-success' }}">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark border">{{ $jenisLabel[$notifikasi->jenis] ?? ucfirst($notifikasi->jenis) }}</span>
                            @if ($notifikasi->prioritas === 'tinggi')
                                <span class="badge bg-danger">Penting</span>
                            @elseif ($notifikasi->prioritas === 'rendah')
                                <span class="badge bg-secondary">Rendah</span>
                            @endif
                            @if (! $dibaca)
                                <span class="badge bg-success">Baru</span>
                            @endif
                            <span class="text-body-secondary small">{{ $notifikasi->waktu_dibuat?->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="fw-semibold mt-1">{{ $notifikasi->judul }}</div>
                        <div class="text-body-secondary small mt-1">{{ $notifikasi->isi }}</div>
                        @if ($notifikasi->link)
                            @if (str_starts_with($notifikasi->link, '/'))
                                <a class="small" href="{{ $notifikasi->link }}">Lihat detail &rarr;</a>
                            @endif
                        @endif
                    </div>
                    @if (! $dibaca)
                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="tandaiDibaca({{ $notifikasi->idnotifikasi }})" wire:loading.attr="disabled">
                            <i class="fa-solid fa-check me-1"></i> Tandai Dibaca
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa-regular fa-bell-slash fa-2x text-body-secondary mb-3"></i>
                @if ($tab === 'unread')
                    <div class="fw-semibold">Semua notifikasi sudah dibaca</div>
                    <div class="text-body-secondary small mt-1">Tidak ada notifikasi belum dibaca.</div>
                @elseif ($filterJenis !== '')
                    <div class="fw-semibold">Tidak ada notifikasi jenis ini</div>
                    <div class="text-body-secondary small mt-1">Coba ubah filter jenis.</div>
                @else
                    <div class="fw-semibold">Belum ada notifikasi</div>
                    <div class="text-body-secondary small mt-1">Pemberitahuan dari sistem akan tampil di sini.</div>
                @endif
            </div>
        </div>
    @endforelse

    <div class="mt-3">
        {{ $daftar->links() }}
    </div>
</div>
