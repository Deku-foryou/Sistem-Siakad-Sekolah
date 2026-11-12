<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    protected $table = 'tagihan';

    protected $primaryKey = 'idtagihan';

    protected $fillable = [
        'siswa_idsiswa',
        'jenis',
        'nama',
        'periode',
        'nominal',
        'diskon_nominal',
        'diskon_persen',
        'total_tagihan',
        'tarif_idtarif',
        'tarif_nama',
        'tarif_jenis',
        'tarif_nominal',
        'kelas_idkelas',
        'kelas_nama',
        'tahunajaran_idtahunajaran',
        'semester_idsemester',
        'status',
        'denda_id',
        'tanggal_terbit',
        'tanggal_jatuh_tempo',
        'catatan',
        'alasan_batal',
        'dibuat_oleh',
        'dibatalkan_oleh',
        'dibatalkan_pada',
    ];

    protected $casts = [
        'nominal' => 'integer',
        'diskon_nominal' => 'integer',
        'diskon_persen' => 'integer',
        'total_tagihan' => 'integer',
        'tarif_nominal' => 'integer',
        'tanggal_terbit' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'dibatalkan_pada' => 'datetime',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function tahunajaran()
    {
        return $this->belongsTo(Tahunajaran::class, 'tahunajaran_idtahunajaran', 'idtahunajaran');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function tarif()
    {
        return $this->belongsTo(Tarif::class, 'tarif_idtarif', 'idtarif');
    }

    public function kreator()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }

    public function pembatal()
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh', 'id');
    }

    public function denda()
    {
        return $this->belongsTo(Denda::class, 'denda_id', 'iddenda');
    }

    public function adjustment()
    {
        return $this->hasMany(TagihanAdjustment::class, 'tagihan_idtagihan', 'idtagihan');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'tagihan_idtagihan', 'idtagihan');
    }

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'belum_dibayar' => 'Belum Dibayar',
            'dibayar_sebagian' => 'Dibayar Sebagian',
            'lunas' => 'Lunas',
            'dibebaskan' => 'Dibebaskan',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
