{{-- Design system bersama halaman Jadwal & Absensi (2B.22). Prefix kelas: sa-*. Tidak mengubah perilaku Livewire. --}}
<style>
    :root {
        --sa-green: #0e7d6a;
        --sa-green-dark: #0b5f52;
        --sa-green-faint: #eef7f3;
        --sa-ink: #1f2937;
        --sa-muted: #6b7280;
        --sa-border: #e5e7eb;
        --sa-shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 1px 3px rgba(16, 24, 40, .08);
        --sa-radius: .75rem;
    }

    /* ---------- Page header ---------- */
    .sa-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: .75rem; margin-bottom: 1rem; }
    .sa-title { font-size: 1.3rem; font-weight: 800; color: var(--sa-ink); margin: 0; letter-spacing: -.01em; line-height: 1.25; }
    .sa-sub { color: var(--sa-muted); font-size: .85rem; margin: .25rem 0 0; }
    .sa-context { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .4rem; }
    .sa-chip { display: inline-flex; align-items: center; gap: .35rem; font-size: .77rem; padding: .2rem .6rem; border-radius: 999px; background: #fff; border: 1px solid var(--sa-border); color: var(--sa-muted); white-space: nowrap; }
    .sa-chip b { color: var(--sa-ink); font-weight: 700; }

    /* ---------- Cards ---------- */
    .sa-card { background: #fff; border: 1px solid var(--sa-border); border-radius: var(--sa-radius); box-shadow: var(--sa-shadow); }
    .sa-card + .sa-card { margin-top: 1rem; }
    .sa-card-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; padding: .65rem 1rem; background: var(--sa-green-faint); border-bottom: 1px solid var(--sa-border); border-radius: var(--sa-radius) var(--sa-radius) 0 0; }
    .sa-card-title { font-size: .92rem; font-weight: 700; color: var(--sa-green-dark); margin: 0; display: flex; align-items: center; gap: .45rem; }
    .sa-card-body { padding: 1rem; }
    .sa-card-body.flush { padding: 0; }
    .sa-card-foot { padding: .6rem 1rem; border-top: 1px solid var(--sa-border); background: #fff; border-radius: 0 0 var(--sa-radius) var(--sa-radius); font-size: .8rem; color: var(--sa-muted); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem; }

    /* ---------- Filter panel ---------- */
    .sa-filter { padding: .8rem 1rem; }
    .sa-filter .form-label { font-size: .78rem; font-weight: 600; color: var(--sa-ink); margin-bottom: .3rem; }
    .sa-filter .form-text { font-size: .76rem; color: var(--sa-muted); }

    /* ---------- Tables ---------- */
    .sa-table { margin: 0; font-size: .88rem; }
    .sa-table thead th { background: var(--sa-green-faint); color: var(--sa-green-dark); border-bottom: 2px solid var(--sa-green); font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; padding: .55rem .75rem; }
    .sa-table tbody td, .sa-table tbody th { padding: .5rem .75rem; vertical-align: middle; border-color: var(--sa-border); }
    .sa-table tbody tr:hover { background: #fbfdfc; }
    .sa-row-active { background: #fff8e6 !important; }
    .sa-cell-muted { color: var(--sa-muted); font-size: .8rem; }

    /* ---------- Status badge (teks + warna, bukan warna saja) ---------- */
    .sa-badge { display: inline-flex; align-items: center; gap: .4rem; padding: .22rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 600; border: 1px solid transparent; line-height: 1.45; white-space: nowrap; }
    .sa-badge::before { content: ""; width: .45rem; height: .45rem; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .sa-hadir { color: #0b6e4f; background: #e7f6ec; border-color: #bfe6cd; }
    .sa-izin { color: #1d4ed8; background: #e8efff; border-color: #c7dcff; }
    .sa-sakit { color: #92400e; background: #fdf0d5; border-color: #f3d9a4; }
    .sa-alpha { color: #b91c1c; background: #fdecec; border-color: #f5c2c2; }
    .sa-netral { color: #374151; background: #f3f4f6; border-color: #e5e7eb; }
    .sa-aktif { color: #0b6e4f; background: #e7f6ec; border-color: #bfe6cd; }
    .sa-nonaktif { color: #6b7280; background: #f3f4f6; border-color: #e5e7eb; }

    /* ---------- Summary stats ---------- */
    .sa-stats { display: flex; flex-wrap: wrap; gap: .5rem; margin: .75rem 0; }
    .sa-stat { display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .75rem; border: 1px solid var(--sa-border); border-radius: .55rem; font-size: .8rem; background: #fff; }
    .sa-stat b { font-size: .95rem; font-weight: 800; }

    /* ---------- Empty state ---------- */
    .sa-empty { text-align: center; padding: 2rem 1rem; }
    .sa-empty i { font-size: 1.7rem; color: #9ca3af; margin-bottom: .5rem; display: block; }
    .sa-empty .sa-empty-title { font-weight: 700; color: var(--sa-ink); margin-bottom: .15rem; }
    .sa-empty .sa-empty-text { color: var(--sa-muted); font-size: .86rem; margin: 0; }

    /* ---------- Misc ---------- */
    .sa-hint { font-size: .78rem; color: var(--sa-muted); }
    .sa-divider { border-top: 1px solid var(--sa-border); margin: .75rem 0; }
    .sa-focus:focus-visible, .sa-card .form-control:focus-visible, .sa-card .form-select:focus-visible { outline: 2px solid var(--sa-green); outline-offset: 1px; box-shadow: none; }
    .sa-count { display: inline-flex; align-items: center; font-size: .76rem; padding: .15rem .6rem; border-radius: 999px; background: #fff; border: 1px solid var(--sa-border); color: var(--sa-muted); font-weight: 600; white-space: nowrap; }

    @media (max-width: 575.98px) {
        .sa-title { font-size: 1.12rem; }
        .sa-card-body { padding: .85rem; }
        .sa-table { font-size: .82rem; }
        .sa-chip { font-size: .72rem; }
    }
</style>
