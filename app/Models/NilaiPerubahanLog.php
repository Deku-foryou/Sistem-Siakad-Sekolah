<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;

class NilaiPerubahanLog extends Model
{
    protected $table = 'nilai_perubahan_log';

    protected $primaryKey = 'idnilai_perubahan_log';

    protected $fillable = [
        'nilai_siswa_idnilai_siswa',
        'nilai_akhir_idnilai_akhir',
        'nilai_lama',
        'nilai_baru',
        'alasan',
        'diubah_oleh',
    ];

    protected $casts = [
        'nilai_lama' => 'decimal:2',
        'nilai_baru' => 'decimal:2',
    ];

    /**
     * Setiap log harus menunjuk tepat satu target.
     *
     * CHECK constraint pada database menjadi backstop; validasi ini memberi
     * pesan yang jelas dan mencegah penulisan ganda sebelum query dijalankan.
     */
    protected static function booted(): void
    {
        static::saving(function (self $log): void {
            $menunjukNilaiSiswa = $log->nilai_siswa_idnilai_siswa !== null;
            $menunjukNilaiAkhir = $log->nilai_akhir_idnilai_akhir !== null;

            if ($menunjukNilaiSiswa === $menunjukNilaiAkhir) {
                throw new DomainException(
                    'Log perubahan nilai harus menunjuk tepat satu target: nilai siswa atau nilai akhir.'
                );
            }
        });
    }

    public function nilaiSiswa()
    {
        return $this->belongsTo(NilaiSiswa::class, 'nilai_siswa_idnilai_siswa', 'idnilai_siswa');
    }

    public function nilaiAkhir()
    {
        return $this->belongsTo(NilaiAkhir::class, 'nilai_akhir_idnilai_akhir', 'idnilai_akhir');
    }

    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'id');
    }
}
