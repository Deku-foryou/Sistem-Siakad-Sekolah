<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\NotifikasiPenerima;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Fondasi modul Notifikasi (kanal database) — lihat LAPORAN_TAHAP_2B6_2.md.
 *
 * Keputusan:
 * - Validasi penerima hanya guru/siswa/wali; superadmin bukan penerima otomatis.
 * - Snapshot penerima dibuat saat pembuatan; user login selalu sebagai scope.
 * - Identitas pemilik tidak pernah dari frontend.
 * - Buat notifikasi hanya untuk actor guru/superadmin (dipakai pemicu 2B.6.3);
 *   UI tidak menyediakan form pembuatan.
 * - Tandai dibaca: scoped, idempoten, global per user untuk "semua".
 * - Tidak ada lock untuk operasi baca scoped (update where user).
 */
class NotifikasiService
{
    /**
     * Role yang boleh menerima notifikasi (2B.12: guru, siswa, wali).
     * Notifikasi wali dibuat hanya melalui pemicu server-side; wali tidak
     * pernah menjadi pembuat dan superadmin bukan penerima.
     */
    private const ROLE_PENERIMA = ['guru', 'siswa', 'wali'];

    /**
     * Role yang boleh membuat notifikasi (aktor).
     */
    private const ROLE_PEMBUAT = ['guru', 'superadmin', 'admin'];

    /**
     * Buat satu notifikasi + snapshot penerima dalam satu transaksi.
     *
     * @param  array<int, int|string>  $penerimaUserIds  ID user (guru/siswa/wali); duplikat dihilangkan
     * @param  array{judul: string, isi: string, jenis: string, prioritas?: string, sumber_tabel?: ?string, sumber_id?: ?int, link?: ?string}  $data
     * @return Notifikasi|null null bila tidak ada penerima valid (notifikasi tidak dibuat)
     */
    public static function buat(array $penerimaUserIds, array $data, User $pembuat): ?Notifikasi
    {
        abort_unless(in_array($pembuat->level, self::ROLE_PEMBUAT, true), 403);

        $judul = trim((string) ($data['judul'] ?? ''));
        $isi = trim((string) ($data['isi'] ?? ''));
        $jenis = trim((string) ($data['jenis'] ?? ''));
        $prioritas = trim((string) ($data['prioritas'] ?? Notifikasi::PRIORITAS_SEDANG));
        $sumberTabel = isset($data['sumber_tabel']) ? trim((string) $data['sumber_tabel']) : null;
        $sumberId = isset($data['sumber_id']) && $data['sumber_id'] !== '' ? (int) $data['sumber_id'] : null;
        $link = isset($data['link']) ? trim((string) $data['link']) : null;

        if ($judul === '' || mb_strlen($judul) > 255) {
            throw new DomainException('Judul notifikasi wajib diisi (maks 255 karakter).');
        }

        if ($isi === '') {
            throw new DomainException('Isi notifikasi wajib diisi.');
        }

        if (! in_array($jenis, Notifikasi::JENIS_LIST, true)) {
            throw new DomainException('Jenis notifikasi tidak valid.');
        }

        if (! in_array($prioritas, Notifikasi::PRIORITAS, true)) {
            throw new DomainException('Prioritas notifikasi tidak valid.');
        }

        if ($sumberTabel !== null && $sumberTabel === '') {
            $sumberTabel = null;
        }

        if ($link !== null && $link === '') {
            $link = null;
        }

        // Link hanya boleh path internal (dimulai dengan /), mencegah javascript:/data:.
        if ($link !== null && ! str_starts_with($link, '/')) {
            throw new DomainException('Link notifikasi harus berupa path internal (contoh: /raporsiswa).');
        }

        // Filter penerima: hanya guru/siswa/wali yang benar-benar ada, unik.
        $ids = collect($penerimaUserIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $validIds = User::query()
            ->whereIn('id', $ids)
            ->whereIn('level', self::ROLE_PENERIMA)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Jangan membuat notifikasi yatim tanpa penerima valid.
        if ($validIds === []) {
            return null;
        }

        return DB::transaction(function () use ($validIds, $pembuat, $judul, $isi, $jenis, $prioritas, $sumberTabel, $sumberId, $link) {
            $notifikasi = Notifikasi::create([
                'judul' => $judul,
                'isi' => $isi,
                'jenis' => $jenis,
                'prioritas' => $prioritas,
                'sumber_tabel' => $sumberTabel,
                'sumber_id' => $sumberId,
                'link' => $link,
                'dibuat_oleh' => $pembuat->id,
                'waktu_dibuat' => now(),
            ]);

            foreach ($validIds as $userId) {
                NotifikasiPenerima::query()->firstOrCreate([
                    'notifikasi_idnotifikasi' => $notifikasi->idnotifikasi,
                    'user_iduser' => $userId,
                ], [
                    'dibaca_pada' => null,
                ]);
            }

            return $notifikasi->fresh('penerima');
        });
    }

    /**
     * Daftar notifikasi milik user login (dengan status baca miliknya).
     */
    public static function daftarUntukUser(User $user, ?string $jenis = null, int $perPage = 10): LengthAwarePaginator
    {
        return self::daftar($user, $jenis, false, $perPage);
    }

    /**
     * Daftar notifikasi milik user login dengan filter tab dan jenis.
     *
     * @param  string|null  $tab  'all'|'unread' (null/false = semua)
     */
    public static function daftar(User $user, ?string $jenis, ?string $tab, int $perPage = 10): LengthAwarePaginator
    {
        self::pastikanLevel($user);

        $query = Notifikasi::query()
            ->with(['penerima' => fn ($q) => $q->where('user_iduser', $user->id)])
            ->whereHas('penerima', fn ($q) => $q->where('user_iduser', $user->id))
            ->orderByDesc('waktu_dibuat')
            ->orderByDesc('idnotifikasi');

        if ($jenis !== null) {
            $jenis = trim($jenis);

            if ($jenis !== '' && in_array($jenis, Notifikasi::JENIS_LIST, true)) {
                $query->where('jenis', $jenis);
            }
        }

        if ($tab === 'unread') {
            $query->whereHas('penerima', fn ($q) => $q->where('user_iduser', $user->id)->whereNull('dibaca_pada'));
        }

        return $query->paginate($perPage);
    }

    /**
     * Detail notifikasi milik user login; notifikasi user lain → modelNotFound (404).
     */
    public static function detailUntukUser(User $user, int $idNotifikasi): Notifikasi
    {
        self::pastikanLevel($user);

        return Notifikasi::query()
            ->with(['penerima' => fn ($q) => $q->where('user_iduser', $user->id)])
            ->whereHas('penerima', fn ($q) => $q->where('user_iduser', $user->id))
            ->findOrFail($idNotifikasi);
    }

    /**
     * Tandai satu notifikasi milik user login sudah dibaca (idempoten).
     *
     * @return bool true bila baris penerima ditemukan milik user
     */
    public static function tandaiDibaca(User $user, int $idNotifikasi): bool
    {
        self::pastikanLevel($user);

        $updated = NotifikasiPenerima::query()
            ->where('notifikasi_idnotifikasi', $idNotifikasi)
            ->where('user_iduser', $user->id)
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);

        if ($updated > 0) {
            return true;
        }

        // Idempoten: sudah dibaca atau tidak dimiliki terdeteksi terpisah.
        return NotifikasiPenerima::query()
            ->where('notifikasi_idnotifikasi', $idNotifikasi)
            ->where('user_iduser', $user->id)
            ->exists();
    }

    /**
     * Tandai seluruh notifikasi milik user login sudah dibaca (idempoten, scoped).
     *
     * @return int jumlah baris yang ditandai
     */
    public static function tandaiSemuaDibaca(User $user): int
    {
        self::pastikanLevel($user);

        return NotifikasiPenerima::query()
            ->where('user_iduser', $user->id)
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);
    }

    /**
     * Jumlah notifikasi belum dibaca milik user login (global, bukan per halaman).
     */
    public static function hitungBelumDibaca(User $user): int
    {
        if (! in_array($user->level, self::ROLE_PENERIMA, true)) {
            return 0;
        }

        return NotifikasiPenerima::query()
            ->where('user_iduser', $user->id)
            ->whereNull('dibaca_pada')
            ->count();
    }

    private static function pastikanLevel(User $user): void
    {
        abort_unless(in_array($user->level, self::ROLE_PENERIMA, true), 403);
    }
}
