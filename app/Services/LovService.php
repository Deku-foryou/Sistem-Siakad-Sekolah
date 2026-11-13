<?php

namespace App\Services;

use App\Support\Lov;
use Illuminate\Database\Eloquent\Model;

/**
 * Layanan pencarian LOV (List of Values) — reusable untuk seluruh modul.
 *
 * Kontrak:
 * - Query TIDAK menerima nama model/kolom dari luar. Kunci didefinisikan di
 *   App\Support\Lov (whitelist). Query hanya boleh mengandung kolom terdaftar.
 * - Hasil selalu array polos: ['id' => int, 'label' => string, 'sublabel' => string].
 *   Jangan pernah mengirim instance Eloquent ke browser.
 * - Limit hasil maksimal 50 (default 20 — sesuai standar LOV proyek).
 * - Pencarian butuh minimal 2 karakter; selain itu kembalikan [].
 * - Hasil diurutkan stabil (order_by registry, tie-break primary key) dan tanpa duplikat.
 */
final class LovService
{
    /**
     * Pencarian LOV.
     *
     * @param  string  $key  Kunci terdaftar di Lov (mis. Lov::KEY_SISWA).
     * @param  string  $query  Kata pencarian (min 2 karakter).
     * @param  int  $limit  Batas hasil (default 20, maks 50).
     * @param  callable|null  $scope  Constraint opsional dari pemanggil (closure $query => void).
     * @return array<int, array{id:int,label:string,sublabel:string}>
     */
    public static function search(string $key, string $query, int $limit = Lov::LIMIT_DEFAULT, ?callable $scope = null): array
    {
        $def = Lov::definisi($key);
        $limit = max(1, min($limit, Lov::LIMIT_MAKSIMAL));

        $kata = trim($query);
        if (mb_strlen($kata) < 2) {
            return [];
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $kata).'%';

        /** @var class-string<Model> $model */
        $model = $def['model'];
        $q = $model::query();

        foreach ($def['where'] as [$col, $val]) {
            $q->where($col, $val);
        }

        $q->where(function ($w) use ($def, $like) {
            foreach ($def['cari'] as $i => $col) {
                $i === 0 ? $w->where($col, 'like', $like) : $w->orWhere($col, 'like', $like);
            }
        });

        if ($scope) {
            $scope($q);
        }

        // Tie-break dengan primary key agar urutan stabil; distinct hindari duplikat.
        $q->select($def['pilih'])
            ->distinct()
            ->orderBy($def['order_by'])
            ->orderBy($model::query()->getModel()->getKeyName())
            ->limit($limit);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Model> $rows */
        $rows = $q->get();

        return $rows->map(fn (Model $row) => self::wujud($row, $def))->all();
    }

    /**
     * Ambil satu record untuk ID yang dipilih (untuk validasi ulang backend).
     * Menolak record di luar scope wajib registry (mis. siswa nonaktif).
     */
    public static function selected(string $key, mixed $id): ?Model
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        $def = Lov::definisi($key);

        /** @var class-string<Model> $model */
        $model = $def['model'];
        $q = $model::query()->whereKey((int) $id);

        foreach ($def['where'] as [$col, $val]) {
            $q->where($col, $val);
        }

        return $q->first();
    }

    /**
     * Ambil banyak record untuk ID-ID yang dipilih (mis. siswa_ids multi).
     * Hasil difilter sesuai scope registry + deduplikat; ID yang tidak valid/nonaktif dibuang.
     *
     * @param  array<int|string>  $ids
     * @return \Illuminate\Database\Eloquent\Collection<int, Model>
     */
    public static function selectedMany(string $key, array $ids)
    {
        $angka = collect($ids)
            ->map(fn ($x) => (int) $x)
            ->filter(fn ($x) => $x > 0)
            ->unique()
            ->values();

        if ($angka->isEmpty()) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        $def = Lov::definisi($key);

        /** @var class-string<Model> $model */
        $model = $def['model'];
        $q = $model::query()->whereIn((new $model)->getKeyName(), $angka->all());

        foreach ($def['where'] as [$col, $val]) {
            $q->where($col, $val);
        }

        return $q->orderBy($def['order_by'])->get();
    }

    /** Wujud baris untuk konsumsi browser: id/label/sublabel saja. */
    private static function wujud(Model $row, array $def): array
    {
        return [
            'id' => (int) $row->getKey(),
            'label' => (string) ($def['label'])($row),
            'sublabel' => (string) ($def['sublabel'])($row),
        ];
    }

    /** Label siap pakai untuk `siswa` terpilih (tampilan read-only). */
    public static function labelUntuk(string $key, mixed $id): string
    {
        $row = self::selected($key, $id);
        if ($row === null) {
            return '';
        }

        return (string) (Lov::definisi($key)['label'])($row);
    }
}
