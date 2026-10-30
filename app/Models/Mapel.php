<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapel';

    protected $primaryKey = 'idmapel';

    protected $fillable = [
        'idmapel',
        'kode',
        'nama',
        'kelompok',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idmapel', 'like', "%{$value}%")
            ->orWhere('kode', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%")
            ->orWhere('kelompok', 'like', "%{$value}%");
    }

    public function guru()
    {
        return $this->belongsToMany(Guru::class, 'gurumapel', 'mapel_idmapel', 'guru_idguru', 'idmapel', 'idguru');
    }
}
