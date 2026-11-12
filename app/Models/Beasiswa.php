<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beasiswa extends Model
{
    protected $table = 'beasiswa';

    protected $primaryKey = 'idbeasiswa';

    protected $fillable = [
        'nama',
        'jenis',
        'nilai',
        'berlaku_dari',
        'berlaku_sampai',
        'status',
        'alasan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'nilai' => 'integer',
        'berlaku_dari' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public function penerima()
    {
        return $this->hasMany(BeasiswaSiswa::class, 'beasiswa_idbeasiswa', 'idbeasiswa');
    }

    public function kreator()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }

    public function berlakuPada(?string $tanggal = null): bool
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        return $this->status === 'aktif'
            && $this->berlaku_dari->format('Y-m-d') <= $tanggal
            && ($this->berlaku_sampai === null || $this->berlaku_sampai->format('Y-m-d') >= $tanggal);
    }
}
