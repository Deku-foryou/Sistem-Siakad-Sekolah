<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'jamhari';

    private const GLOBAL_INDEX_COLUMNS = ['hari', 'jam_ke'];

    private const CLASS_INDEX_COLUMNS = ['kelas_id', 'hari', 'jam_ke'];

    private const GLOBAL_INDEX_NAME = 'jamhari_hari_jamke_unique';

    private const CLASS_INDEX_NAME = 'jamhari_kelas_hari_jamke_unique';

    public function up(): void
    {
        $this->assertRequiredSchema();

        $indexes = Schema::getIndexes(self::TABLE);
        $globalIndex = $this->findUniqueIndex($indexes, self::GLOBAL_INDEX_COLUMNS);
        $classIndex = $this->findUniqueIndex($indexes, self::CLASS_INDEX_COLUMNS);

        $this->assertIndexNameIsAvailable($indexes, self::GLOBAL_INDEX_NAME, self::GLOBAL_INDEX_COLUMNS);
        $this->assertIndexNameIsAvailable($indexes, self::CLASS_INDEX_NAME, self::CLASS_INDEX_COLUMNS);

        if ($classIndex !== null) {
            if ($globalIndex !== null) {
                Schema::table(self::TABLE, function (Blueprint $table) use ($globalIndex) {
                    $table->dropUnique($globalIndex['name']);
                });
            }

            return;
        }

        if ($globalIndex === null) {
            throw new \RuntimeException(
                'Tidak dapat menormalisasi jamhari: unique index global maupun kelas-per-kelas tidak ditemukan.'
            );
        }

        $this->assertNoClassDuplicates();

        Schema::table(self::TABLE, function (Blueprint $table) use ($globalIndex) {
            $table->dropUnique($globalIndex['name']);
            $table->unique(self::CLASS_INDEX_COLUMNS, self::CLASS_INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $this->assertRequiredColumns(self::CLASS_INDEX_COLUMNS);

        $indexes = Schema::getIndexes(self::TABLE);
        $globalIndex = $this->findUniqueIndex($indexes, self::GLOBAL_INDEX_COLUMNS);
        $classIndex = $this->findUniqueIndex($indexes, self::CLASS_INDEX_COLUMNS);

        if ($globalIndex === null && $classIndex === null) {
            throw new \RuntimeException(
                'Tidak dapat rollback normalizer jamhari: unique index kelas-per-kelas dan unique index global tidak ditemukan.'
            );
        }

        $this->assertNoGlobalDuplicates();

        if ($globalIndex !== null && $classIndex !== null) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($classIndex) {
                $table->dropUnique($classIndex['name']);
            });

            return;
        }

        if ($globalIndex !== null) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) use ($classIndex) {
            $table->dropUnique($classIndex['name']);
            $table->unique(self::GLOBAL_INDEX_COLUMNS, self::GLOBAL_INDEX_NAME);
        });
    }

    private function assertRequiredSchema(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException(
                'Tidak dapat menormalisasi jamhari: tabel jamhari tidak ditemukan setelah migration pembuatan tabel.'
            );
        }

        $this->assertRequiredColumns(self::CLASS_INDEX_COLUMNS);
    }

    private function assertRequiredColumns(array $columns): void
    {
        $missingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn(self::TABLE, $column)
        ));

        if ($missingColumns !== []) {
            throw new \RuntimeException(
                'Tidak dapat memigrasikan jamhari: kolom prasyarat tidak ditemukan: '.implode(', ', $missingColumns).'.'
            );
        }
    }

    private function findUniqueIndex(array $indexes, array $columns): ?array
    {
        $matches = array_values(array_filter(
            $indexes,
            fn (array $index): bool => $index['unique']
                && array_map('strtolower', $index['columns']) === $columns
        ));

        if (count($matches) > 1) {
            throw new \RuntimeException(
                'Schema jamhari ambigu: ditemukan lebih dari satu unique index untuk ('.implode(', ', $columns).').'
            );
        }

        return $matches[0] ?? null;
    }

    private function assertIndexNameIsAvailable(array $indexes, string $name, array $columns): void
    {
        foreach ($indexes as $index) {
            if ($index['name'] === strtolower($name)
                && (! $index['unique'] || array_map('strtolower', $index['columns']) !== $columns)) {
                throw new \RuntimeException(
                    "Schema jamhari ambigu: nama index {$name} digunakan oleh index dengan definisi berbeda."
                );
            }
        }
    }

    private function assertNoClassDuplicates(): void
    {
        $duplicate = DB::table(self::TABLE)
            ->whereNotNull('kelas_id')
            ->whereNotNull('jam_ke')
            ->select('kelas_id', 'hari', 'jam_ke')
            ->groupBy('kelas_id', 'hari', 'jam_ke')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException(
                "Tidak dapat menormalisasi jamhari: duplikasi kelas_id={$duplicate->kelas_id}, hari={$duplicate->hari}, jam_ke={$duplicate->jam_ke}."
            );
        }
    }

    private function assertNoGlobalDuplicates(): void
    {
        $duplicate = DB::table(self::TABLE)
            ->whereNotNull('jam_ke')
            ->select('hari', 'jam_ke')
            ->groupBy('hari', 'jam_ke')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException(
                "Tidak dapat rollback normalizer jamhari ke unique global: hari={$duplicate->hari}, jam_ke={$duplicate->jam_ke} digunakan lebih dari satu baris."
            );
        }
    }
};
