<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FONDASI HUBUNGAN WALI–SISWA — Tahap 2B.7.4B
 *
 * Trigger MySQL anti-mutasi histori (append-only):
 *   walimurid_history_bu  BEFORE UPDATE  → tolak
 *   walimurid_history_bd  BEFORE DELETE  → tolak
 *   waliakun_history_bu   BEFORE UPDATE  → tolak
 *   waliakun_history_bd   BEFORE DELETE  → tolak
 *
 * INSERT histori TETAP DIPERBOLEHKAN.
 *
 * Penolakan memakai SIGNAL SQLSTATE '45000' — MySQL Math Error / user exception.
 * Semua pesan SIGNAL tidak mengandung data sensitif.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createHistoryTriggers('walimurid_history');
        $this->createHistoryTriggers('waliakun_history');
    }

    public function down(): void
    {
        $triggers = [
            'walimurid_history_bu',
            'walimurid_history_bd',
            'waliakun_history_bu',
            'waliakun_history_bd',
        ];

        foreach ($triggers as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
        }
    }

    private function createHistoryTriggers(string $table): void
    {
        $short = str_replace('_history', '', $table); // walimurid / waliakun
        $updateTrigger = "{$short}_history_bu";
        $deleteTrigger = "{$short}_history_bd";

        DB::unprepared(
            "CREATE TRIGGER `{$updateTrigger}` BEFORE UPDATE ON `{$table}` FOR EACH ROW
             BEGIN
               SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$table} bersifat append-only: UPDATE ditolak.';
             END"
        );

        DB::unprepared(
            "CREATE TRIGGER `{$deleteTrigger}` BEFORE DELETE ON `{$table}` FOR EACH ROW
             BEGIN
               SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$table} bersifat append-only: DELETE ditolak.';
             END"
        );
    }
};
