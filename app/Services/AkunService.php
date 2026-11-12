<?php

namespace App\Services;

use App\Models\User;
use App\Models\WaliakunHistory;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AkunService
{
    /**
     * Daftar peran aktor kelola akun (snapshot untuk audit, bukan pengecekan).
     * Kewenangan sebenarnya selalu ditegakkan oleh `pastikanBerwenangKelolaAkun()`.
     */
    public const AKTOR_PERAN = ['superadmin', 'admin'];

    // ----------------------------------------------------------------
    // API lama (guru/siswa) — TETAP DIPERTAHANKAN APA ADANYA.
    // ----------------------------------------------------------------

    public static function buat(string $username, string $nama, string $level, Carbon $tanggalLahir): User
    {
        $passwordAwal = $tanggalLahir->format('dmY');

        return User::create([
            'username' => $username,
            'name' => $nama,
            'email' => null,
            'password' => Hash::make($passwordAwal),
            'level' => $level,
            'wajib_ganti_password' => true,
        ]);
    }

    public static function resetPassword(User $user, Carbon $tanggalLahir): string
    {
        $passwordAwal = $tanggalLahir->format('dmY');
        $user->password = Hash::make($passwordAwal);
        $user->wajib_ganti_password = true;
        $user->save();

        return $passwordAwal;
    }

    // ----------------------------------------------------------------
    // API baru — Portal Wali Murid (desain final §5.4, §8.1, §8.8).
    // ----------------------------------------------------------------

    /**
     * Sandi sementara acak kuat (16 karakter: huruf, angka, simbol).
     * Disimpan sebagai hash; ditampilkan hanya satu kali kepada admin.
     */
    public static function buatSandiSementara(): string
    {
        return Str::password(16, letters: true, numbers: true, symbols: true, spaces: false);
    }

    /**
     * Buat akun wali oleh pengelola berwenang.
     *
     * @return array{0: User, 1: string} [akun, sandi mentah (hanya sekali tampilkan)]
     */
    public static function buatAkunWali(User $aktor, string $nama, string $username, ?string $email = null, ?string $alasan = null): array
    {
        self::pastikanBerwenangKelolaAkun($aktor);

        $username = trim($username);
        $nama = trim($nama);
        if ($username === '' || $nama === '') {
            throw new \InvalidArgumentException('Nama dan username wajib diisi.');
        }
        if (mb_strlen($username) > 30) {
            throw new \InvalidArgumentException('Username maksimal 30 karakter.');
        }
        if (User::where('username', $username)->exists()) {
            throw new \DomainException('Username sudah dipakai.');
        }
        $email = $email !== null && trim($email) !== '' ? trim($email) : null;
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Format email tidak valid.');
        }
        if ($email !== null && User::where('email', $email)->exists()) {
            throw new \DomainException('Email sudah dipakai.');
        }

        $sandi = self::buatSandiSementara();

        return DB::transaction(function () use ($aktor, $nama, $username, $email, $alasan, $sandi) {
            $user = new User;
            $user->forceFill([
                'name' => $nama,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($sandi),
                'level' => 'wali',
                'wajib_ganti_password' => true,
                'status_akun' => 'aktif',
            ]);
            $user->save();

            // Terapkan paket Wali (menu Beranda Wali) bila rolecat tersedia.
            $rolecatWali = \App\Models\Rolecat::where('rolecat', 'Wali')->first();
            if ($rolecatWali) {
                \App\Services\PaketMenuService::terapkanPaket($user, $rolecatWali->idrolecat);
            }

            self::catatHistoriAkun($user, $aktor, 'akun_dibuat', null, 'aktif', $alasan);

            return [$user, $sandi];
        });
    }

    /**
     * Terbitkan sandi sementara baru untuk akun wali.
     *
     * @return string sandi mentah (hanya sekali tampilkan)
     */
    public static function resetSandiSementara(User $aktor, User $target, string $alasan): string
    {
        self::pastikanBerwenangKelolaAkun($aktor);
        abort_unless($target->level === 'wali', 403, 'Hanya akun wali yang bisa direset.');
        self::pastikanAlasan($alasan);

        $sandi = self::buatSandiSementara();

        DB::transaction(function () use ($target, $aktor, $alasan, $sandi) {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            $locked->password = Hash::make($sandi);
            $locked->wajib_ganti_password = true;
            $locked->save();

            self::catatHistoriAkun($locked, $aktor, 'password_direset_paksa', $locked->status_akun, $locked->status_akun, $alasan);
        });

        return $sandi;
    }

    /**
     * Aktifkan/nonaktifkan akun wali oleh pengelola berwenang.
     */
    public static function setStatusAkun(User $aktor, User $target, string $statusBaru, string $alasan): void
    {
        self::pastikanBerwenangKelolaAkun($aktor);
        abort_unless($target->level === 'wali', 403, 'Hanya akun wali yang bisa diubah statusnya.');
        self::pastikanAlasan($alasan);

        if (! in_array($statusBaru, ['aktif', 'nonaktif'], true)) {
            throw new \InvalidArgumentException('Status akun harus aktif atau nonaktif.');
        }

        DB::transaction(function () use ($target, $aktor, $statusBaru, $alasan) {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            $sebelum = $locked->status_akun;

            if ($sebelum === $statusBaru) {
                throw new \DomainException('Status akun sudah '.$statusBaru.'.');
            }

            $event = $statusBaru === 'nonaktif' ? 'akun_dinonaktifkan' : 'akun_diaktifkan';

            if ($statusBaru === 'nonaktif') {
                $locked->status_akun = 'nonaktif';
                $locked->dinonaktifkan_pada = now();
            } else {
                $locked->status_akun = 'aktif';
                $locked->dinonaktifkan_pada = null;
            }
            $locked->save();

            self::catatHistoriAkun($locked, $aktor, $event, $sebelum, $statusBaru, $alasan);
        });
    }

    /**
     * Satu-satunya gerbang kewenangan kelola akun wali (T1c, §7.10).
     * W1: level ∈ {superadmin, admin}; W2: punya usermenu untuk menu `wali-akun`.
     */
    public static function pastikanBerwenangKelolaAkun(User $aktor): void
    {
        abort_unless(in_array($aktor->level, self::AKTOR_PERAN, true), 403, 'Hanya superadmin dan admin sekolah yang berwenang.');

        $punyaMenu = $aktor->usermenu()
            ->whereHas('appmenunew', fn ($query) => $query->where('url', 'wali-akun'))
            ->exists();

        abort_unless($punyaMenu, 403, 'Akun ini tidak memiliki izin menu Kelola Akun Wali.');
    }

    // ----------------------------------------------------------------
    // Internal
    // ----------------------------------------------------------------

    private static function pastikanAlasan(string $alasan): void
    {
        if (trim($alasan) === '') {
            throw new \InvalidArgumentException('Alasan wajib diisi.');
        }
        if (mb_strlen(trim($alasan)) > 500) {
            throw new \InvalidArgumentException('Alasan maksimal 500 karakter.');
        }
    }

    private static function catatHistoriAkun(User $target, User $aktor, string $event, ?string $sebelum, ?string $sesudah, ?string $alasan): void
    {
        $role = match ($aktor->level) {
            'superadmin' => 'superadmin',
            'admin' => 'admin',
            default => 'sistem',
        };

        try {
            WaliakunHistory::create([
                'user_iduser' => $target->id,
                'aktor_id' => $aktor->id,
                'aktor_peran' => $role,
                'event' => $event,
                'status_sebelum' => $sebelum,
                'status_sesudah' => $sesudah,
                'alasan' => $alasan !== null ? mb_substr(trim($alasan), 0, 500) : null,
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Histori tidak boleh gagal diam-diam: batalkan transaksi pemanggil.
            throw $e;
        }
    }
}
