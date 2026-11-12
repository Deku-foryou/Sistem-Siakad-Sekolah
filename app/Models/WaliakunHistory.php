<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Desain final §5.4 — histori akun wali (append-only).
 * Tidak boleh diubah/dihapus dari aplikasi; alasan tidak pernah berisi sandi.
 */
class WaliakunHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'waliakun_history';

    protected $primaryKey = 'idwaliakun_history';

    protected $fillable = [
        'user_iduser',
        'aktor_id',
        'aktor_peran',
        'event',
        'status_sebelum',
        'status_sesudah',
        'alasan',
        'created_at',
    ];

    public const AKTOR_PERAN = ['superadmin', 'admin', 'sistem'];

    public const EVENT = ['akun_dibuat', 'akun_diaktifkan', 'akun_dinonaktifkan', 'password_direset_paksa'];

    public function akun()
    {
        return $this->belongsTo(User::class, 'user_iduser', 'id');
    }

    public function aktor()
    {
        return $this->belongsTo(User::class, 'aktor_id', 'id');
    }
}
