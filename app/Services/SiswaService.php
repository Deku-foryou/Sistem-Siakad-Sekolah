<?php

namespace App\Services;

use App\Models\Siswa;
use App\Support\JenisKelamin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SiswaService
{
    public static function buat(array $data): array
    {
        $result = ['success' => false, 'user' => null, 'siswa' => null, 'errors' => []];

        // Validate required fields
        $required = ['nisn', 'nama', 'jenis_kelamin', 'tanggal_lahir'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $result['errors'][$field] = 'Field '.$field.' wajib diisi';
            }
        }

        if (! empty($result['errors'])) {
            return $result;
        }

        // Format nisn: trim and add leading zeros if needed
        $nisn = trim($data['nisn']);
        if (ctype_digit($nisn) && strlen($nisn) < 10) {
            $nisn = str_pad($nisn, 10, '0', STR_PAD_LEFT);
        }
        if (strlen($nisn) > 10 || ! ctype_digit($nisn)) {
            $result['errors']['nisn'] = 'NISN harus berupa angka dan maksimal 10 digit';

            return $result;
        }
        if (preg_match('/[Ee]/', $nisn)) {
            $result['errors']['nisn'] = 'NISN tidak boleh dalam notasi ilmiah/desimal';

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

        DB::transaction(function () use ($nisn, $tglLahir, $jk, $data, &$result) {
            // Create user via AkunService
            $user = AkunService::buat($nisn, $data['nama'], 'siswa', $tglLahir);
            $result['user'] = $user;

            // Create siswa
            $siswa = new Siswa;
            $siswa->user_iduser = $user->id;
            $siswa->nisn = $nisn;
            $siswa->nis = $data['nis'] ?? $nisn;
            $siswa->nama = $data['nama'];
            $siswa->jenis_kelamin = $jk;
            $siswa->tempat_lahir = $data['tempat_lahir'] ?? '';
            $siswa->tanggal_lahir = $data['tanggal_lahir'];
            $siswa->agama = $data['agama'] ?? '';
            $siswa->alamat = $data['alamat'] ?? '';
            $siswa->nik = $data['nik'] ?? '';
            $siswa->telepon_siswa = $data['telepon_siswa'] ?? '';
            $siswa->email = $data['email'] ?? '';
            $siswa->asal_sekolah = $data['asal_sekolah'] ?? '';
            $siswa->nama_ayah = $data['nama_ayah'] ?? '';
            $siswa->nama_ibu = $data['nama_ibu'] ?? '';
            $siswa->telepon_ortu = $data['telepon_ortu'] ?? '';
            $siswa->tahun_masuk = $data['tahun_masuk'] ?? null;
            $siswa->foto = $data['foto'] ?? null;
            $siswa->status = $data['status'] ?? 'aktif';
            $siswa->save();

            $result['siswa'] = $siswa;
            $result['success'] = true;

            // Apply default paket menu
            $paketStatus = PaketMenuService::terapkanDefault($user);
            if ($paketStatus === 'paket_tidak_ada') {
                $result['warnings']['paket'] = 'Paket menu "Siswa" belum dibuat; akun dibuat tanpa menu';
            }
        });

        if (isset($result['user']) && $result['user']) {
            $result['wajib_ganti_password'] = true;
        }

        return $result;
    }
}
