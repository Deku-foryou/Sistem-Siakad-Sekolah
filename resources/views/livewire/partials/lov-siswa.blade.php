{{--
    LOV Siswa (reusable) — pola pencarian terbatas (whitelist), debounce 300ms di input,
    maksimal 20 hasil dari server. Komponen pemanggil menyertakan data:
    - string $lovMode       : 'single' | 'multi'
    - string $siswaLabel    : label siswa terpilih (mode single) — string kosong jika belum
    - array  $siswaTerpilih : daftar terpilih ['id' => int, 'nama' => string, 'nis' => string|null] (mode multi)
    - array  $lovHasil      : hasil pencarian ['id' => int, 'label' => string, 'sublabel' => string]
    - string $lovSearch     : teks pencarian saat ini
    Komponen mendefinisikan method: pilihLovSiswa(int), hapusLovSiswa(), gantiLovSiswa(), hapusSiswaTerpilih(int).
--}}
@php
    $lovMode = $lovMode ?? 'single';
    $siswaLabel = $siswaLabel ?? '';
    $siswaTerpilih = $siswaTerpilih ?? [];
@endphp

<div class="lov-siswa">
    {{-- ===== Mode single: tampilkan label terpilih + aksi Ganti/Hapus ===== --}}
    @if ($lovMode === 'single' && $siswaLabel !== '')
        <div class="sa-card sa-filter d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2" style="border-color: var(--sa-border) !important; background: var(--sa-green-faint);">
            <span><span class="sa-hint">Siswa terpilih</span><br><strong>{{ $siswaLabel }}</strong></span>
            <span class="d-flex gap-2">
                <button type="button" wire:click="gantiLovSiswa" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Ganti siswa">Ganti</button>
                <button type="button" wire:click="hapusLovSiswa" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus pilihan siswa" aria-label="Hapus pilihan siswa"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="d-none d-sm-inline"> Hapus</span></button>
            </span>
        </div>
    @endif

    {{-- ===== Mode multi: daftar siswa terpilih (chips) ===== --}}
    @if ($lovMode === 'multi')
        @if (count($siswaTerpilih) > 0)
            <div class="d-flex flex-wrap gap-1 mb-2" aria-label="Daftar siswa terpilih">
                @foreach ($siswaTerpilih as $s)
                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold" style="background: var(--sa-green-faint); color: var(--sa-green-dark); border: 1px solid var(--sa-green); padding: .35em .7em;">
                        {{ $s['nama'] }} <span class="fw-normal">· NIS {{ $s['nis'] ?? '-' }}</span>
                        <button type="button" wire:click="hapusSiswaTerpilih({{ (int) $s['id'] }})" class="btn btn-link btn-sm text-danger p-0 lh-1 ms-1" style="text-decoration: none;" aria-label="Hapus {{ $s['nama'] }} dari daftar" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></button>
                    </span>
                @endforeach
            </div>
        @else
            <p class="sa-hint mb-1">Belum ada siswa dipilih{{ $lovMode === 'multi' ? '' : '' }}. Gunakan pencarian.</p>
        @endif
    @endif

    {{-- ===== Input pencarian + hasil ===== --}}
    @if ($lovMode === 'multi' || $siswaLabel === '')
        <input type="text" id="lov-siswa-search" class="form-control @error('lovSearch') is-invalid @enderror"
               wire:model.live.debounce.300ms="lovSearch" placeholder="Cari nama / NIS / NISN (min. 2 karakter)"
               autocomplete="off" aria-label="Cari siswa">
        @error('lovSearch') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
    @endif

    @if (mb_strlen(trim((string) $lovSearch)) >= 2)
        @if (count($lovHasil) > 0)
            <ul class="list-group mt-2" aria-label="Hasil pencarian siswa">
                @foreach ($lovHasil as $h)
                    <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2">
                        <span><strong>{{ $h['label'] }}</strong><br><small class="text-body-secondary">{{ $h['sublabel'] }}</small></span>
                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="pilihLovSiswa({{ (int) $h['id'] }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Pilih siswa">Pilih</button>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="sa-hint mt-2 mb-0"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Tidak ada siswa aktif yang cocok.</p>
        @endif
    @endif
</div>
