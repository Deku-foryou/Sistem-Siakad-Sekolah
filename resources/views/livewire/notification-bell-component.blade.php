@php
    // Guru, siswa & wali; superadmin tidak mendapatkan tampilan bell.
    $level = auth()->user()?->level;
    $boleh = in_array($level, ['guru', 'siswa', 'wali'], true);
@endphp
@if ($boleh)
    <a href="/notifikasi" class="position-relative me-3 d-inline-flex align-items-center text-decoration-none" wire:poll.30s aria-label="Notifikasi">
        <i class="fa-regular fa-bell text-secondary" style="font-size: 18px;"></i>
        @if ($belumDibaca > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size: 8px; border: 2px solid #fff;">
                {{ $belumDibaca > 99 ? '99+' : $belumDibaca }}
            </span>
        @endif
    </a>
@endif
