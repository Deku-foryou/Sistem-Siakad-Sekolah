<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $primaryKey = 'idpengumuman';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'judul',
        'isi',
        'kategori',
        'prioritas',
        'status',
        'waktu_terbit',
        'berlaku_dari',
        'berlaku_sampai',
        'target',
        'dibuat_oleh',
        'diperbarui_oleh',
    ];

    protected $casts = [
        'waktu_terbit' => 'datetime',
        'berlaku_dari' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public const STATUS = ['draf', 'terbit', 'arsip'];

    public const KATEGORI = ['akademik', 'acara', 'penting', 'darurat'];

    public const PRIORITAS = ['rendah', 'sedang', 'tinggi', 'mendesak'];

    public const TARGET = ['semua', 'jurusan', 'kelas', 'siswa'];

    public function targets()
    {
        return $this->hasMany(PengumumanTarget::class, 'pengumuman_idpengumuman', 'idpengumuman');
    }

    public function baca()
    {
        return $this->hasMany(PengumumanBaca::class, 'pengumuman_idpengumuman', 'idpengumuman');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }

    public function pengubah()
    {
        return $this->belongsTo(User::class, 'diperbarui_oleh', 'id');
    }

    public function scopeSearch($query, $value)
    {
        $query->where('idpengumuman', 'like', "%{$value}%")
            ->orWhere('judul', 'like', "%{$value}%")
            ->orWhere('isi', 'like', "%{$value}%")
            ->orWhere('kategori', 'like', "%{$value}%")
            ->orWhere('status', 'like', "%{$value}%");
    }

    public function scopeTerbit($query)
    {
        return $query->where('status', 'terbit')
            ->where(function ($q) {
                $q->whereNull('waktu_terbit')
                    ->orWhere('waktu_terbit', '<=', now());
            });
    }

    public function scopeBerlaku($query, $tanggal = null)
    {
        $tanggal = $tanggal ? \Carbon\Carbon::parse($tanggal) : now();

        return $query->where(function ($q) use ($tanggal) {
            $q->whereNull('berlaku_dari')
                ->orWhere('berlaku_dari', '<=', $tanggal);
        })->where(function ($q) use ($tanggal) {
            $q->whereNull('berlaku_sampai')
                ->orWhere('berlaku_sampai', '>=', $tanggal);
        });
    }
}
