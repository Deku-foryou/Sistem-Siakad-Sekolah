<?php

namespace Database\Seeders;

use App\Models\Siswa;
use App\Models\User;
use App\Models\WaliakunHistory;
use App\Models\Walimurid;
use App\Models\WalimuridHistory;
use App\Models\WalimuridPermintaanPutus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Portal Wali Murid — akun & relasi demo (sintetis).
 *
 * Skenario:
 * - 4 relasi VERIFIED (satu wali punya 2 anak)
 * - 1 relasi PENDING (menunggu verifikasi admin)
 * - 1 relasi REJECTED (ditolak + history)
 * - 1 relasi VERIFIED dengan permintaan putus MENUNGGU (antrean)
 * History tables bersifat immutable (trigger) — insert-only, tanpa UPDATE/DELETE.
 */
class DemoWaliSeeder extends Seeder
{
    public function run(): void
    {
        $superadminId = User::where('level', 'superadmin')->value('id');
        if (! $superadminId) {
            throw new \RuntimeException('DemoWaliSeeder: superadmin belum ada.');
        }

        // [nama, username, password demo, hubungan, objek relasi]
        $walis = [
            // [waliName, username, hubungan, anak (username siswa), status]
            ['Budi Santoso', 'budi.santoso.wali', 'ayah', ['andre.santoso.001'], 'verified'],
            ['Siti Rahma', 'siti.rahma.wali', 'ibu', ['putri.rahma.021'], 'verified'],
            ['Dewi Anggraini', 'dewi.anggraini.wali', 'ibu', ['rizky.ramadhan.009', 'raka.ramadhan.041'], 'verified'],
            ['Andi Wijaya', 'andi.wijaya.wali', 'ayah', ['bima.wijaya.031'], 'pending'],
            ['Rina Marlina', 'rina.marlina.wali', 'ibu', ['salsa.marlina.051'], 'rejected'],
            ['Eko Prasetyo', 'eko.prasetyo.wali', 'ayah', ['galih.prasetyo.042'], 'verified_putus'],
        ];

        foreach ($walis as $idx => $def) {
            [$waliName, $username, $hubungan, $children, $skenario] = $def;
            $u = User::firstOrCreate(
                ['username' => $username],
                ['name' => $waliName, 'email' => $username.'@demo.sekolah.test', 'password' => Hash::make('demo123'), 'level' => 'wali', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]
            );

            WaliakunHistory::firstOrCreate(
                ['user_iduser' => $u->id, 'event' => 'akun_dibuat'],
                ['aktor_id' => $superadminId, 'aktor_peran' => 'superadmin', 'status_sesudah' => 'aktif', 'alasan' => 'Pembuatan akun wali (demo)']
            );

            foreach ($children as $childUsername) {
                $siswa = Siswa::whereHas('user', fn ($q) => $q->where('username', $childUsername))->first();
                if (! $siswa) {
                    $this->command?->warn("DemoWaliSeeder: siswa '{$childUsername}' tidak ditemukan; dilewati.");

                    continue;
                }

                $relasiVerified = in_array($skenario, ['verified', 'verified_putus'], true);
                $relasiStatus = match ($skenario) {
                    'pending' => 'pending',
                    'rejected' => 'rejected',
                    default => 'verified',
                };

                $relasi = Walimurid::firstOrCreate(
                    ['user_iduser' => $u->id, 'siswa_idsiswa' => $siswa->idsiswa],
                    [
                        'hubungan' => $hubungan,
                        'status_hubungan' => $relasiStatus,
                        'tanggal_mulai' => '2024-07-01',
                        'tanggal_verifikasi' => $relasiVerified ? now()->subYears(1)->startOfDay() : null,
                        'pengaju_id' => $u->id,
                        'verifikator_id' => $relasiVerified || $skenario === 'rejected' ? $superadminId : null,
                        'catatan' => $skenario === 'rejected' ? 'Dokumen belum lengkap (contoh demo)' : null,
                    ]
                );

                WalimuridHistory::firstOrCreate(
                    ['walimurid_id' => $relasi->idwalimurid, 'event' => 'pengajuan'],
                    [
                        'user_iduser' => $u->id, 'siswa_idsiswa' => $siswa->idsiswa,
                        'aktor_id' => $u->id, 'aktor_peran' => 'wali',
                        'status_sesudah' => 'pending', 'created_at' => now()->subYears(1),
                    ]
                );

                if ($relasiVerified) {
                    WalimuridHistory::firstOrCreate(
                        ['walimurid_id' => $relasi->idwalimurid, 'event' => 'verifikasi'],
                        [
                            'user_iduser' => $u->id, 'siswa_idsiswa' => $siswa->idsiswa,
                            'aktor_id' => $superadminId, 'aktor_peran' => 'superadmin',
                            'status_sebelum' => 'pending', 'status_sesudah' => 'verified',
                            'created_at' => now()->subYears(1)->addDays(2),
                        ]
                    );
                } elseif ($skenario === 'rejected') {
                    WalimuridHistory::firstOrCreate(
                        ['walimurid_id' => $relasi->idwalimurid, 'event' => 'penolakan'],
                        [
                            'user_iduser' => $u->id, 'siswa_idsiswa' => $siswa->idsiswa,
                            'aktor_id' => $superadminId, 'aktor_peran' => 'superadmin',
                            'status_sebelum' => 'pending', 'status_sesudah' => 'rejected',
                            'alasan' => 'Dokumen belum lengkap (contoh demo)',
                            'created_at' => now()->subMonths(3),
                        ]
                    );
                }
            }

            // Skenario: relasi verified dengan permintaan putus menunggu
            if ($skenario === 'verified_putus') {
                $child = Siswa::whereHas('user', fn ($q) => $q->where('username', $children[0]))->first();
                $relasi = Walimurid::where('user_iduser', $u->id)->where('siswa_idsiswa', $child->idsiswa)->first();
                if ($relasi) {
                    WalimuridPermintaanPutus::firstOrCreate(
                        ['walimurid_id' => $relasi->idwalimurid, 'status_permintaan' => 'menunggu'],
                        ['diminta_oleh' => $u->id, 'alasan' => 'Pindah domisili ke luar kota (contoh demo)', 'created_at' => now()->subDays(2)]
                    );
                    WalimuridHistory::firstOrCreate(
                        ['walimurid_id' => $relasi->idwalimurid, 'event' => 'permintaan_putus'],
                        [
                            'user_iduser' => $u->id, 'siswa_idsiswa' => $child->idsiswa,
                            'aktor_id' => $u->id, 'aktor_peran' => 'wali',
                            'status_sebelum' => 'verified', 'status_sesudah' => 'verified',
                            'alasan' => 'Pindah domisili ke luar kota (contoh demo)',
                            'created_at' => now()->subDays(2),
                        ]
                    );
                }
            }
        }

        $this->command?->info('DemoWaliSeeder: 6 akun wali, 4 relasi verified (2 anak untuk 1 wali), 1 pending, 1 rejected, 1 permintaan putus.');
    }
}
