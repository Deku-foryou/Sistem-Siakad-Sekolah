# 🎨 Panduan Desain Sistem — Silika Lab

> Referensi utama: `parametermst-component.blade.php`  
> Berlaku untuk semua halaman menu utama: **Pendaftaran, Pemeriksaan, Verifikasi, Download TTE**

---

## 1. Struktur Halaman (Page Layout)

Setiap halaman menggunakan struktur berikut:

```html
<!-- Wrapper Utama -->
<div class="container-fluid mb-5 px-4" style="margin-top: 20px">

    <!-- Header Section -->
    <div class="container-fluid px-0 p-3 rounded-top">
        <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
            <div class="mb-3 mb-lg-0">
                <p class="p-0 m-0 h3 fw-bold">Judul Halaman</p>
                <div class="mt-2" style="color: #868686">
                    <i class="fa-regular fa-circle-question me-1 text-danger"></i>
                    Deskripsi singkat menu ini.
                </div>
            </div>
            <div>
                <!-- Tombol Aksi Utama (opsional) -->
                <button class="btn btn-primary">...</button>
            </div>
        </div>
    </div>

    <!-- Action Toolbar Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-body p-3">
            <!-- Search Box + Action Buttons -->
        </div>
    </div>

    <!-- Content Area -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-2 pb-0 border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <!-- Tabel Data -->
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
```

---

## 2. Tipografi

| Elemen           | Class / Style                         |
|------------------|---------------------------------------|
| Judul Halaman    | `h3 fw-bold`                          |
| Deskripsi Info   | `color: #868686`, ikon `fa-circle-question text-danger` |
| Label Form       | `col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label` |
| Error Validasi   | `text-danger`, `font-size: 11.5px`    |
| Teks Kecil/Info  | `small`, `text-muted`                 |

---

## 3. Warna Sistem

| Tujuan              | Warna             | Kode Hex / Class         |
|---------------------|-------------------|--------------------------|
| Aksi Utama (Primary)| Biru              | `btn-primary` / `#0D6EFD`|
| Sukses / Simpan     | Hijau             | `btn-success` / `#198754`|
| Edit / Ubah         | Kuning            | `btn-warning text-dark`  |
| Hapus               | Merah             | `btn-danger`             |
| Info / Netral       | Abu-abu terang    | `bg-light`, `#f8f9fa`    |
| Import Excel        | Hijau muda        | `bg: #ecfdf5`, `color: #059669`, `border: #d1fae5` |
| Import Sub          | Oranye muda       | `bg: #fff7ed`, `color: #c2410c`, `border: #ffedd5` |
| Teks sekunder       | Abu-abu           | `#868686` atau `text-muted` |

---

## 4. Komponen: Action Toolbar

```html
<div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

            <!-- Search Box -->
            <div class="flex-grow-1" style="max-width: 450px;">
                <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #e9ecef;">
                    <span class="input-group-text bg-white border-0 ps-3">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-0 ps-2 py-2"
                        placeholder="Cari..."
                        style="font-size: 14px; box-shadow: none;" wire:model.live.debounce.300ms="search">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Tombol Export Excel -->
                <button class="btn btn-success text-white d-flex align-items-center gap-2 px-3"
                    style="border-radius: 10px; font-size: 13px; font-weight: 600;">
                    <i class="fa-solid fa-download"></i>
                    <span>Excel</span>
                </button>
                <!-- Tombol Export PDF -->
                <button class="btn btn-danger text-white d-flex align-items-center gap-2 px-3"
                    style="border-radius: 10px; font-size: 13px; font-weight: 600;">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>PDF</span>
                </button>
            </div>

        </div>
    </div>
</div>
```

---

## 5. Komponen: Tabel Data

```html
<div class="card mb-2 pb-0 border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" style="width:100%; white-space:nowrap">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 3%">No</th>
                        <th style="width: 100px; text-align: center">Action</th>
                        <th>Kolom 1</th>
                        <th>Kolom 2</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $key => $item)
                        <tr class="px-4">
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td style="min-width: 90px; text-align: center">
                                <button class="btn btn-sm btn-warning text-dark">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger text-white">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                            <td>{{ $item->kolom1 }}</td>
                            <td>{{ $item->kolom2 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center">
                                <small>Data Tidak ditemukan</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
```

---

## 6. Komponen: Modal Form (Tambah / Edit)

```html
<div wire:ignore.self class="modal fade" id="addXxxModal" tabindex="-1"
    data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Menambah Data</h5>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="store">

                    <!-- Field Row -->
                    <div class="form-group row mb-3">
                        <label for="field_name" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">
                            Label Field
                        </label>
                        <div class="col-12 col-lg-9">
                            <input type="text" id="field_name" class="form-control"
                                wire:model="field_name" placeholder="Masukkan...">
                            @error('field_name')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span> Loading...
                        </span>
                        <span wire:loading.remove>Simpan</span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="close()">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
```

---

## 7. Komponen: Modal Konfirmasi Hapus

```html
<div wire:ignore.self class="modal fade" id="deleteXxxModal" tabindex="-1"
    data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Confirmation</h5>
            </div>
            <div class="modal-body pt-4 pb-4">
                <h6>Are you sure? You want to delete this data?</h6>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" wire:click="delete()">Ya</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
```

---

## 8. Komponen: Loader

```html
<div wire:loading wire:target="search, exportToExcel, exportToPdf" class="loading-spinner">
    <div class="loader"></div>
</div>
```

---

## 9. Komponen: Pagination

```html
<div class="row">
    <div class="pagination-container mt-2 d-flex justify-content-end gap-3 align-items-center">
        <x-pagination-controls
            :dataPagination="$dataList"
            :perPage="$paginationData['key']['perPage']"
            :keyPagination="'key'" />
    </div>
</div>
```

---

## 10. Aturan Border-Radius

| Komponen        | Nilai           |
|-----------------|-----------------|
| Card toolbar    | `border-radius: 15px` |
| Tombol aksi     | `border-radius: 10px` |
| Modal content   | Default Bootstrap |
| Modal import    | `border-radius: 20px` |
| Input group     | `border-radius: 10px; overflow: hidden` |

---

## 11. Penerapan Per Halaman

| Menu           | File Blade                                  | Status  |
|----------------|---------------------------------------------|---------|
| Pendaftaran    | `pendaftaran-component.blade.php`           | ✅ Diterapkan |
| Pemeriksaan    | `pemeriksaan-component.blade.php`           | ✅ Diterapkan |
| Verifikasi     | `verifikasihasil-component.blade.php`       | ✅ Diterapkan |
| Download TTE   | `downloadsiaptte-component.blade.php`       | ✅ Diterapkan |

---

## 12. Catatan Khusus Per Halaman

### Pendaftaran
- Menggunakan **layout 2 kolom**: form pendaftaran (kiri) + pilih parameter (kanan)
- Header harus tampilkan judul `h3 fw-bold` + deskripsi abu `#868686`
- Toolbar card berisi filter kategori lab (Klinik / Lingkungan) gaya mst

### Pemeriksaan
- Halaman daftar pasien menggunakan tabel gaya mst dengan search box di toolbar card
- Halaman input hasil menggunakan card dengan header biru muda `#cfe2ff`
- Tombol simpan menggunakan `btn-success` penuh lebar

### Verifikasi Hasil
- Tabel daftar antrian menggunakan toolbar card + search gaya mst
- Badge status: `bg-warning text-dark` (Menunggu) / `bg-success` (Terverifikasi)
- Modal detail verifikasi XL dengan header `bg-primary`

### Download TTE
- Tabel daftar dengan search box gaya mst
- Tombol aksi per baris: Word (`btn-outline-primary`) + PDF (`btn-outline-danger`) + Excel (`btn-outline-success`)
- Header card menggunakan `bg-success`
