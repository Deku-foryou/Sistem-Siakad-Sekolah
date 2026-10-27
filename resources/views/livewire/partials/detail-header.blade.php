@php
    $nameParts = collect(preg_split('/\s+/', trim((string) $name)))->filter()->values();
    $initials = $nameParts->count() > 1
        ? $nameParts->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('')
        : mb_strtoupper(mb_substr((string) $name, 0, 2));
    $statusLabel = match ($status) {
        'aktif' => 'Aktif',
        'lulus' => 'Lulus',
        'pindah' => 'Pindah',
        'keluar' => 'Keluar',
        'nonaktif' => 'Nonaktif',
        default => $status ?: '-',
    };
    $statusClass = match ($status) {
        'aktif' => 'bg-success',
        'lulus' => 'bg-info text-dark',
        'pindah', 'nonaktif' => 'bg-secondary',
        'keluar' => 'bg-danger',
        default => 'bg-secondary',
    };
@endphp

<div class="detail-identity d-flex align-items-center gap-3 pb-3 mb-3">
    <div class="detail-avatar rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" aria-hidden="true">
        {{ $initials ?: '-' }}
    </div>
    <div class="min-w-0">
        <div class="fw-bold fs-5 text-break">{{ $name ?: '-' }}</div>
        <div class="detail-identity-number">{{ $numberLabel }}: {{ $number ?: '-' }}</div>
    </div>
    <span class="badge {{ $statusClass }} ms-auto flex-shrink-0">{{ $statusLabel }}</span>
</div>
