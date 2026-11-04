<?php

namespace App\Services;

use App\Models\Kelassiswa;
use App\Models\Pengumuman;
use App\Models\PengumumanBaca;
use App\Models\PengumumanTarget;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Pusat logika bisnis pengumuman.
 *
 * Aturan:
 * - Status hanya boleh maju: draf -> terbit -> arsip.
 * - Pengumuman terbit tidak dapat diedit atau dikembalikan menjadi draf.
 * - Pengumuman arsip tidak dapat diterbitkan kembali (buat baru untuk revisi).
 * - Tidak ada penghapusan pengumuman.
 * - Snapshot penerima dibuat saat publikasi (bukan lazy saat siswa membuka detail).
 * - Publikasi atomik: validasi, lockForUpdate/status draf, penyimpanan target,
 *   pembuatan snapshot unik (distinct), dan perubahan status dalam satu transaksi.
 * - Publikasi tanpa penerima ditolak.
 * - Validasi berlaku_sampai >= berlaku_dari bila keduanya diisi.
 */
class PengumumanService
{
    /**
     * Buat pengumuman baru (selalu sebagai draf) beserta target.
     *
     * @param  User  $pembuat  superadmin
     */
    public function buatDraf(array $data, User $pembuat): Pengumuman
    {
        $this->assertLevelSuperadmin($pembuat);

        $validated = $this->validasi($data, true);

        return DB::transaction(function () use ($validated, $pembuat) {
            $pengumuman = Pengumuman::create([
                'judul' => $validated['judul'],
                'isi' => $validated['isi'],
                'kategori' => $validated['kategori'],
                'prioritas' => $validated['prioritas'],
                'status' => 'draf',
                'berlaku_dari' => $validated['berlaku_dari'] ?? null,
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'target' => $validated['target'],
                'dibuat_oleh' => $pembuat->id,
                'diperbarui_oleh' => $pembuat->id,
            ]);

            $this->simpanTarget($pengumuman, $validated);

            return $pengumuman;
        });
    }

    /**
     * Ubah pengumuman draf (judul, isi, kategori, prioritas, target, periode).
     *
     * @throws DomainException Bila pengumuman tidak berstatus draf.
     */
    public function ubahDraf(int $pengumumanId, array $data, User $pengubah): Pengumuman
    {
        $this->assertLevelSuperadmin($pengubah);

        return DB::transaction(function () use ($pengumumanId, $data, $pengubah) {
            $pengumuman = Pengumuman::query()->lockForUpdate()->findOrFail($pengumumanId);

            if ($pengumuman->status !== 'draf') {
                throw new DomainException('Pengumuman yang sudah terbit atau arsip tidak dapat diedit. Buat pengumuman baru untuk revisi.');
            }

            $validated = $this->validasi($data, false);

            $pengumuman->update([
                'judul' => $validated['judul'],
                'isi' => $validated['isi'],
                'kategori' => $validated['kategori'],
                'prioritas' => $validated['prioritas'],
                'berlaku_dari' => $validated['berlaku_dari'] ?? null,
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'target' => $validated['target'],
                'diperbarui_oleh' => $pengubah->id,
            ]);

            // Tulis ulang target (hapus yang lama).
            $pengumuman->targets()->delete();
            $this->simpanTarget($pengumuman, $validated);

            return $pengumuman;
        });
    }

    /**
     * Terbitkan pengumuman draf beserta snapshot penerima (atomik).
     *
     * @throws DomainException Bila status bukan draf, target tidak valid, atau tanpa penerima.
     */
    public function terbitkan(int $pengumumanId, User $penerbit): Pengumuman
    {
        $this->assertLevelSuperadmin($penerbit);

        return DB::transaction(function () use ($pengumumanId, $penerbit) {
            $pengumuman = Pengumuman::query()->lockForUpdate()->findOrFail($pengumumanId);

            if ($pengumuman->status !== 'draf') {
                throw new DomainException('Hanya pengumuman berstatus draf yang dapat diterbitkan.');
            }

            // Validasi ulang seluruh data dan target.
            $data = [
                'judul' => $pengumuman->judul,
                'isi' => $pengumuman->isi,
                'kategori' => $pengumuman->kategori,
                'prioritas' => $pengumuman->prioritas,
                'target' => $pengumuman->target,
                'berlaku_dari' => $pengumuman->berlaku_dari?->format('Y-m-d'),
                'berlaku_sampai' => $pengumuman->berlaku_sampai?->format('Y-m-d'),
                'jurusan_ids' => $pengumuman->targets->pluck('jurusan_idjurusan')->filter()->values()->all(),
                'kelas_ids' => $pengumuman->targets->pluck('kelas_idkelas')->filter()->values()->all(),
                'siswa_ids' => $pengumuman->targets->pluck('siswa_idsiswa')->filter()->values()->all(),
            ];
            $this->validasi($data, false);

            // Target harus sesuai jenisnya; simpan ulang agar konsisten.
            $validated = $this->validasi($data, false);
            $pengumuman->targets()->delete();
            $this->simpanTarget($pengumuman, $validated);

            $targetsSegar = $pengumuman->targets()->get();
            $penerimaIds = $this->penerimaIds($targetsSegar, $pengumuman->target);

            if ($penerimaIds->isEmpty()) {
                throw new DomainException('Pengumuman tidak memiliki penerima. Publikasi dibatalkan.');
            }

            // Buat snapshot (satu baris per siswa; dibaca_pada null).
            foreach ($penerimaIds as $siswaId) {
                PengumumanBaca::firstOrCreate([
                    'pengumuman_idpengumuman' => $pengumuman->idpengumuman,
                    'siswa_idsiswa' => $siswaId,
                ], [
                    'dibaca_pada' => null,
                ]);
            }

            $pengumuman->update([
                'status' => 'terbit',
                'waktu_terbit' => now(),
                'diperbarui_oleh' => $penerbit->id,
            ]);

            return $pengumuman->fresh();
        });
    }

    /**
     * Arsipkan pengumuman.
     *
     * @throws DomainException Bila pengumuman tidak berstatus terbit.
     */
    public function arsipkan(int $pengumumanId, User $pengarsip): Pengumuman
    {
        $this->assertLevelSuperadmin($pengarsip);

        return DB::transaction(function () use ($pengumumanId, $pengarsip) {
            $pengumuman = Pengumuman::query()->lockForUpdate()->findOrFail($pengumumanId);

            if ($pengumuman->status !== 'terbit') {
                throw new DomainException('Hanya pengumuman berstatus terbit yang dapat diarsipkan.');
            }

            $pengumuman->update([
                'status' => 'arsip',
                'diperbarui_oleh' => $pengarsip->id,
            ]);

            return $pengumuman->fresh();
        });
    }

    /**
     * Daftar pengumuman aktif untuk siswa pemilik akun.
     *
     * Syarat: status terbit, waktu_terbit wajib ada dan <= now(),
     * berlaku_dari null atau <= hari ini,
     * berlaku_sampai null atau >= hari ini,
     * siswa memiliki snapshot (baris pengumuman_baca).
     */
    public function daftarAktif(int $siswaId)
    {
        return Pengumuman::query()
            ->with(['baca' => fn ($q) => $q->where('siswa_idsiswa', $siswaId)])
            ->where('status', 'terbit')
            ->whereNotNull('waktu_terbit')
            ->where('waktu_terbit', '<=', now())
            ->where(function ($q) {
                $tanggal = now()->toDateString();
                $q->whereNull('berlaku_dari')->orWhere('berlaku_dari', '<=', $tanggal);
            })
            ->where(function ($q) {
                $tanggal = now()->toDateString();
                $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $tanggal);
            })
            ->whereHas('baca', fn ($q) => $q->where('siswa_idsiswa', $siswaId))
            ->orderByDesc('waktu_terbit')
            ->paginate(15, ['*'], 'aktif');
    }

    /**
     * Riwayat pengumuman siswa (arsip atau terbit yang sudah kedaluwarsa).
     *
     * Syarat: status arsip, atau status terbit tetapi berlaku_sampai sudah
     * melewati hari ini, dan waktu_terbit wajib ada dan <= now().
     * Pengumuman yang masih aktif tidak muncul di riwayat.
     * Pengumuman yang belum mencapai berlaku_dari tidak muncul di kedua tab.
     * Siswa hanya dapat melihat pengumuman yang memiliki snapshot untuk dirinya.
     */
    public function riwayat(int $siswaId)
    {
        return Pengumuman::query()
            ->with(['baca' => fn ($q) => $q->where('siswa_idsiswa', $siswaId)])
            ->where(function ($q) {
                $tanggal = now()->toDateString();
                $q->where('status', 'arsip')
                    ->orWhere(function ($inner) use ($tanggal) {
                        $inner->where('status', 'terbit')
                            ->whereNotNull('berlaku_sampai')
                            ->where('berlaku_sampai', '<', $tanggal);
                    });
            })
            ->whereNotNull('waktu_terbit')
            ->where('waktu_terbit', '<=', now())
            ->where(function ($q) {
                $tanggal = now()->toDateString();
                $q->whereNull('berlaku_dari')->orWhere('berlaku_dari', '<=', $tanggal);
            })
            ->whereHas('baca', fn ($q) => $q->where('siswa_idsiswa', $siswaId))
            ->orderByDesc('waktu_terbit')
            ->paginate(15, ['*'], 'riwayat');
    }

    /**
     * Detail pengumuman untuk siswa pemilik akun.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Bila siswa bukan penerima (IDOR 404).
     */
    public function detailUntukSiswa(int $siswaId, int $pengumumanId): Pengumuman
    {
        $pengumuman = Pengumuman::query()
            ->with(['baca' => fn ($q) => $q->where('siswa_idsiswa', $siswaId)])
            ->where('idpengumuman', $pengumumanId)
            ->whereIn('status', ['terbit', 'arsip'])
            ->whereNotNull('waktu_terbit')
            ->where('waktu_terbit', '<=', now())
            ->where(function ($q) {
                $tanggal = now()->toDateString();
                $q->whereNull('berlaku_dari')->orWhere('berlaku_dari', '<=', $tanggal);
            })
            ->whereHas('baca', fn ($q) => $q->where('siswa_idsiswa', $siswaId))
            ->firstOrFail();

        return $pengumuman;
    }

    /**
     * Tandai pengumuman sudah dibaca (idempoten, tanpa firstOrCreate).
     */
    public function tandaiSudahDibaca(int $siswaId, int $pengumumanId): PengumumanBaca
    {
        return DB::transaction(function () use ($siswaId, $pengumumanId) {
            $baca = PengumumanBaca::query()
                ->where('pengumuman_idpengumuman', $pengumumanId)
                ->where('siswa_idsiswa', $siswaId)
                ->lockForUpdate()
                ->first();

            if (! $baca) {
                abort(404);
            }

            if ($baca->dibaca_pada === null) {
                $baca->dibaca_pada = now();
                $baca->save();
            }

            return $baca;
        });
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function assertLevelSuperadmin(User $user): void
    {
        if ($user->level !== 'superadmin') {
            abort(403);
        }
    }

    /**
     * Validasi data pengumuman & target; melempar ValidationException bila gagal.
     */
    private function validasi(array $data, bool $isCreate): array
    {
        $rules = [
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'kategori' => ['required', 'in:'.implode(',', Pengumuman::KATEGORI)],
            'prioritas' => ['required', 'in:'.implode(',', Pengumuman::PRIORITAS)],
            'target' => ['required', 'in:'.implode(',', Pengumuman::TARGET)],
            'berlaku_dari' => ['nullable', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:berlaku_dari'],
            'jurusan_ids' => ['nullable', 'array'],
            'jurusan_ids.*' => ['integer', 'exists:jurusan,idjurusan'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['integer', 'exists:kelas,idkelas'],
            'siswa_ids' => ['nullable', 'array'],
            'siswa_ids.*' => ['integer', 'exists:siswa,idsiswa'],
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->all());
        }

        $v = $validator->validated();

        if (! empty($v['berlaku_dari']) && ! empty($v['berlaku_sampai'])) {
            $dari = Carbon::parse($v['berlaku_dari']);
            $sampai = Carbon::parse($v['berlaku_sampai']);
            if ($sampai->lt($dari)) {
                throw ValidationException::withMessages(['Tanggal akhir berlaku harus lebih besar atau sama dengan tanggal mulai.']);
            }
        }

        $target = $v['target'];
        $jurusanIds = array_values(array_filter($v['jurusan_ids'] ?? []));
        $kelasIds = array_values(array_filter($v['kelas_ids'] ?? []));
        $siswaIds = array_values(array_filter($v['siswa_ids'] ?? []));

        // Tepat satu jenis target terisi sesuai jenis target.
        if ($target === 'jurusan') {
            if ($jurusanIds === []) {
                throw ValidationException::withMessages(['Target jurusan harus dipilih.']);
            }
            if ($kelasIds !== [] || $siswaIds !== []) {
                throw ValidationException::withMessages(['Target tidak sesuai jenis.']);
            }
            $this->assertUnik('jurusan', $jurusanIds);
        } elseif ($target === 'kelas') {
            if ($kelasIds === []) {
                throw ValidationException::withMessages(['Target kelas harus dipilih.']);
            }
            if ($jurusanIds !== [] || $siswaIds !== []) {
                throw ValidationException::withMessages(['Target tidak sesuai jenis.']);
            }
            $this->assertUnik('kelas', $kelasIds);
        } elseif ($target === 'siswa') {
            if ($siswaIds === []) {
                throw ValidationException::withMessages(['Target siswa harus dipilih.']);
            }
            if ($jurusanIds !== [] || $kelasIds !== []) {
                throw ValidationException::withMessages(['Target tidak sesuai jenis.']);
            }
            $this->assertUnik('siswa', $siswaIds);
            // Pastikan semua siswa aktif & tersedia.
            $aktif = Siswa::whereIn('idsiswa', $siswaIds)->where('status', 'aktif')->count();
            if ($aktif !== count($siswaIds)) {
                throw ValidationException::withMessages(['Sebagian siswa target tidak tersedia atau tidak aktif.']);
            }
        } elseif ($target === 'semua') {
            if ($jurusanIds !== [] || $kelasIds !== [] || $siswaIds !== []) {
                throw ValidationException::withMessages(['Target tidak sesuai jenis.']);
            }
        }

        return array_merge($v, [
            'jurusan_ids' => $jurusanIds,
            'kelas_ids' => $kelasIds,
            'siswa_ids' => $siswaIds,
        ]);
    }

    private function assertUnik(string $jenis, array $ids): void
    {
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['Target '.$jenis.' duplikat.']);
        }
    }

    /**
     * Simpan baris pengumuman_target untuk pengumuman.
     *
     * @param  array{jurusan_ids: array, kelas_ids: array, siswa_ids: array}  $validated
     */
    private function simpanTarget(Pengumuman $pengumuman, array $validated): void
    {
        foreach ($validated['jurusan_ids'] as $id) {
            PengumumanTarget::firstOrCreate([
                'pengumuman_idpengumuman' => $pengumuman->idpengumuman,
                'jurusan_idjurusan' => $id,
                'kelas_idkelas' => null,
                'siswa_idsiswa' => null,
            ]);
        }

        foreach ($validated['kelas_ids'] as $id) {
            PengumumanTarget::firstOrCreate([
                'pengumuman_idpengumuman' => $pengumuman->idpengumuman,
                'jurusan_idjurusan' => null,
                'kelas_idkelas' => $id,
                'siswa_idsiswa' => null,
            ]);
        }

        foreach ($validated['siswa_ids'] as $id) {
            PengumumanTarget::firstOrCreate([
                'pengumuman_idpengumuman' => $pengumuman->idpengumuman,
                'jurusan_idjurusan' => null,
                'kelas_idkelas' => null,
                'siswa_idsiswa' => $id,
            ]);
        }
    }

    /**
     * Tentukan penerima snapshot (unik, distinct).
     *
     * @param  \Illuminate\Database\Eloquent\Collection|null  $targets  target pengumuman
     */
    private function penerimaIds($targets, string $targetType)
    {
        if ($targetType === 'semua') {
            return Siswa::query()
                ->where('status', 'aktif')
                ->distinct()
                ->pluck('idsiswa');
        }

        if ($targetType === 'jurusan') {
            $jurusanIds = $targets->pluck('jurusan_idjurusan')->filter()->unique()->values();
            if ($jurusanIds->isEmpty()) {
                return collect();
            }

            return Kelassiswa::query()
                ->where('status', 'aktif')
                ->whereHas('kelas', fn ($q) => $q->whereIn('jurusan_id', $jurusanIds->all()))
                ->distinct()
                ->pluck('siswa_idsiswa');
        }

        if ($targetType === 'kelas') {
            $kelasIds = $targets->pluck('kelas_idkelas')->filter()->unique()->values();
            if ($kelasIds->isEmpty()) {
                return collect();
            }

            return Kelassiswa::query()
                ->where('status', 'aktif')
                ->whereIn('kelas_idkelas', $kelasIds->all())
                ->distinct()
                ->pluck('siswa_idsiswa');
        }

        if ($targetType === 'siswa') {
            return $targets->pluck('siswa_idsiswa')->filter()->unique()->values();
        }

        return collect();
    }
}
