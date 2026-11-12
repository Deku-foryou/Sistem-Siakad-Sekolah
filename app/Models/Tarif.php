<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $table = 'tarif';

    protected $primaryKey = 'idtarif';

    protected $fillable = [
        'jenis',
        'nama',
        'nominal',
        'berlaku_dari',
        'berlaku_sampai',
        'status',
        'kelas_idkelas',
        'siswa_idsiswa',
        'alasan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'nominal' => 'integer',
        'berlaku_dari' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function kreator()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }

    public function riwayat()
    {
        return $this->hasMany(TarifRiwayat::class, 'tarif_idtarif', 'idtarif');
    }

    /**
     * Apakah tarif berlaku pada tanggal tertentu.
     */
    public function berlakuPada(?string $tanggal = null): bool
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        return $this->status === 'aktif'
            && $this->berlaku_dari->format('Y-m-d') <= $tanggal
            && ($this->berlaku_sampai === null || $this->berlaku_sampai->format('Y-m-d') >= $tanggal);
    }
}
