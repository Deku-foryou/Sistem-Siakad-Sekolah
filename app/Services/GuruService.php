<?php

namespace App\Services;

use App\Models\Guru;
use App\Support\JenisKelamin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GuruService
{
    public static function buat(array $data): array
    {
        $result = ['success' => false, 'user' => null, 'guru' => null, 'errors' => []];

        // Validate required fields
        $required = ['nip', 'nama', 'jenis_kelamin', 'tanggal_lahir'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $result['errors'][$field] = 'Field '.$field.' wajib diisi';
            }
        }

        if (! empty($result['errors'])) {
            return $result;
        }

        // Format nip: digit only, max 25, no padding, reject scientific notation
        $nip = trim($data['nip']);
        if (! ctype_digit($nip)) {
            $result['errors']['nip'] = 'NIP harus berupa digit saja';

            return $result;
        }
        if (strlen($nip) > 25) {
            $result['errors']['nip'] = 'NIP maksimal 25 digit';

            return $result;
        }
        if (preg_match('/[Ee]/', $nip)) {
            $result['errors']['nip'] = 'NIP tidak boleh dalam notasi ilmiah/desimal (presisi angka hilang di Excel)';

            return $result;
        }

        // Format tanggal_lahir and check not in future
        $tglLahir = Carbon::parse($data['tanggal_lahir']);
        if ($tglLahir->gt(now())) {
            $result['errors']['tanggal_lahir'] = 'Tanggal lahir tidak boleh di masa depan';

            return $result;
        }

        try {
            $jk = JenisKelamin::normalisasi($data['jenis_kelamin']);
        } catch (InvalidArgumentException $exception) {
            $result['errors']['jenis_kelamin'] = $exception->getMessage();

            return $result;
        }

        DB::transaction(function () use ($nip, $tglLahir, $jk, $data, &$result) {
            // Create user via AkunService
            $user = AkunService::buat($nip, $data['nama'], 'guru', $tglLahir);
            $result['user'] = $user;

            // Create guru
            $guru = new Guru;
            $guru->user_iduser = $user->id;
            $guru->nip = $nip;
            $guru->kode_guru = $data['kode_guru'] ?? null;
            $guru->nama = $data['nama'];
            $guru->jenis_kelamin = $jk;
            $guru->tempat_lahir = $data['tempat_lahir'] ?? '';
            $guru->tanggal_lahir = $data['tanggal_lahir'];
            $guru->telepon = $data['telepon'] ?? '';
            $guru->alamat = $data['alamat'] ?? '';
            $guru->foto = $data['foto'] ?? null;
            $guru->status = $data['status'] ?? 'aktif';
            $guru->save();

            $result['guru'] = $guru;
            $result['success'] = true;

            // Apply default paket menu
            $paketStatus = PaketMenuService::terapkanDefault($user);
            if ($paketStatus === 'paket_tidak_ada') {
                $result['warnings']['paket'] = 'Paket menu "Guru" belum dibuat; akun dibuat tanpa menu';
            }
        });

        if (isset($result['user']) && $result['user']) {
            $result['wajib_ganti_password'] = true;
        }

        return $result;
    }

    public static function sinkronMapel(Guru $guru, array $mapelIds): void
    {
        // Normalisasi
        $mapelIds = array_unique(array_filter(array_map('intval', $mapelIds)));

        // Verifikasi semua mapel ada
        foreach ($mapelIds as $id) {
            if (!\App\Models\Mapel::where('idmapel', $id)->exists()) {
                throw new \InvalidArgumentException("Mapel dengan ID {$id} tidak ditemukan.");
            }
        }

        // Sinkronisasi: Sync relationship
        $guru->mapel()->sync($mapelIds);
    }
}
