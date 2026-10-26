# Laporan Tahap 2A - Data Induk

## 1. Ringkasan
Tahap 2A selesai: perbaikan loop route dinamis, index usermenu, 5 tabel data induk, model, AkunService, CRUD komponen + views, penyesuaian Users, seeder menu. Import (2B) belum dikerjakan.

## 2. Git
Branch: `feature/data-induk` (dari `master`).

## 3. File dibuat
- Migrations: 2026_10_26_000004..000009
- Models: Guru, Siswa, Mapel, Kelas, Kelassiswa
- Service: App/Services/AkunService.php
- Livewire: GuruComponent, SiswaComponent, MapelComponent, KelasComponent, KelassiswaComponent
- Views: guru/siswa/mapel/kelas/kelassiswa-component.blade.php
- Seeder: DataIndukMenuSeeder

## 4. File diubah
- routes/web.php (loop dinamis + route baru + explicit urls)
- app/Models/User.php (relasi guru/siswa)
- app/Livewire/UsersComponent.php (reset password)
- resources/views/livewire/users-component.blade.php (level superadmin only, username readonly, reset button)

## 5. SQL --pretend
Pretend menunjukkan create table guru, siswa, mapel, kelas, kelassiswa dengan FK RESTRICT, unique, dan index usermenu. `php artisan migrate` hasil: 6 migration DONE.

## 6. Seeder menu
DataIndukMenuSeeder dijalankan. Kategori "Data Induk" + 5 item (guru, siswa, mapel, kelas, kelassiswa) ditempel ke semua superadmin.

## 7. Perbandingan route
- Sebelum: loop membaca Usermenu (join appmenunew/user).
- Sesudah: loop membaca url unik dari Appmenunew.
- `php artisan route:list` menampilkan route: guru, siswa, mapel, kelas, kelassiswa, tahunajaran, semester, users, dll. Satu per URL, tanpa duplikat.

## 8. Jumlah query (loop)
Data uji: 16 baris usermenu (bukan 300).
- Lama: 3 query.
- Baru: 1 query.

## 9. Verifikasi (dijalankan)
B1: user=yes level=siswa wgp=1; siswa=yes nisn_len=10; password_check=true. PASS
B2: duplicate store rejected (nisn sudah ada.); siswa count nisn=1; user count username=1. PASS
B3: store exception thrown (forced); users before=3 after=3. Edit exception thrown; nisn/username kembali ke nilai awal. PASS
B4: second induk member count kelas2=0; mapel_pilihan counts=1 + 1. PASS
B5: wali on mapel_pilihan after update=null (rejected); same guru two induk after update=null (rejected). PASS
B6: with relation siswa count 1 (before 1), user count 5 (before 5) (ditolak); no relation siswa count 1 (before 2), user count 5 (before 6), deleted exists=no. PASS
B7: nisn=9998887776 username=9998887776; password unchanged=true. PASS
B8: status=lulus aktif=false; status=aktif aktif=true (relasi siswa dimuat ulang via query, bukan cache). PASS
B9: usermenu rows=316; OLD avg 8.91 ms, 3 queries; NEW avg 1.38 ms, 1 query; 20 iterasi. PASS
B10: url activitylog ADA (web,auth,force.password,menu.access); tahunajaran/semester/guru/siswa/mapel/kelas/kelassiswa ADA (web,auth,force.password,check.level:superadmin); menu1 TIDAK (komponen tidak ada). PASS

Perbaikan A:
- Sinkron username pada edit NISN/NIP: users.username ikut berubah dalam transaction yang sama (B3/B7).
- Blokir login non-aktif: status lulus => aktif=false (B8).

Belum diuji (browser):
- Semua interaksi modal/UI (tambah/edit/hapus via browser).
Langkah uji browser (untuk user):
1. Login superadmin, buka menu Data Induk.
2. Tambah guru, cek akun terbuat (username=NIP).
3. Tambah siswa, cek akun terbuat (username=NISN).
4. Buat kelas induk dan mapel_pilihan; tempatkan siswa; coba duplikasi induk (harus ditolak).
5. Hapus data berelasi (harus ditolak) dan tanpa relasi (harus terhapus + akun).
6. Reset password dari halaman Users untuk guru/siswa.

## 10. Belum dikerjakan
- Tahap 2B (Import).
- Tahap 3 dan seterusnya.
- Pengujian browser/UI.
```

, "path": "C:\\project-reyhan\\template web\\LAPORAN_TAHAP2A.md"}