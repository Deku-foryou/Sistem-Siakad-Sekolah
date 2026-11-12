<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Backup dinonaktifkan secara default: aktifkan hanya bila config('siakad.backup.enabled') === true
        // dan direktori eksternal telah ditetapkan lewat config('siakad.backup.path') atau opsi --path.
        if (config('siakad.backup.enabled') === true) {
            $schedule->command('siakad:backup-db')
                ->dailyAt('02:30')
                ->withoutOverlapping()
                ->runInBackground();
        }

        // Penerbitan tagihan SPP bulanan: tanggal 1, 00:30, zona waktu aplikasi
        // (default Asia/Jakarta). Idempotent — aman dipanggil berkali-kali.
        $schedule->command('spp:terbit')
            ->monthlyOn(1, '00:30')
            ->withoutOverlapping()
            ->runInBackground();

        // Pengingat tagihan: H-7, H-3, H, H+1, lalu setiap 7 hari sampai lunas/selesai.
        // Idempotent (dibatasi tahap); melewati tagihan lunas/dibebaskan/dibatalkan;
        // dijeda bila pembayaran manual pending menutup sisa tagihan.
        $schedule->command('tagihan:pengingat')
            ->dailyAt('07:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Pengaman status Midtrans: setiap 5 menit, server-side check attempt pending/created.
        // No-op bila Midtrans belum dikonfigurasi (di-log, tidak menggagalkan scheduler).
        $schedule->command('pembayaran:cek-status')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
