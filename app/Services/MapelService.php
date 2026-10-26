<?php

namespace App\Services;

use App\Models\Mapel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MapelService
{
    public static function buat(array $data): array
    {
        $result = ['success' => false, 'mapel' => null, 'user' => null, 'errors' => []];

        // Validate required fields
        if (empty($data['kode'])) {
            $result['errors']['kode'] = 'Kode mapel wajib diisi';
        }
        if (empty($data['nama'])) {
            $result['errors']['nama'] = 'Nama mapel wajib diisi';
        }

        if (! empty($result['errors'])) {
            return $result;
        }

        // Check for duplicate kode mapel (case-insensitive after trim)
        $kode = trim($data['kode']);
        if (Mapel::whereRaw('LOWER(kode) = ?', [strtolower($kode)])->exists()) {
            $result['errors']['kode'] = 'Kode mapel sudah terdaftar';

            return $result;
        }

        // Check for duplicate nama mapel (case-insensitive after trim)
        $nama = trim($data['nama']);
        if (Mapel::whereRaw('LOWER(nama) = ?', [strtolower($nama)])->exists()) {
            $result['errors']['nama'] = 'Nama mapel sudah terdaftar';

            return $result;
        }

        DB::transaction(function () use ($kode, &$result) {
            // Create user (placeholder - typically handled separately, but service should be self-contained)
            // For mapel, we create the mapel record only
            $mapel = new Mapel;
            $mapel->kode = $kode;
            $mapel->nama = $data['nama'];
            $mapel->kelompok = $data['kelompok'] ?? '';
            $mapel->save();

            $result['mapel'] = $mapel;
            $result['success'] = true;
        });

        return $result;
    }
}
