<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $primaryKey = 'idpembayaran';

    protected $fillable = [
        'tagihan_idtagihan',
        'siswa_idsiswa',
        'nominal_dibayar',
        'biaya_gateway',
        'metode',
        'provider',
        'order_id',
        'provider_ref',
        'referensi',
        'status',
        'dibayar_pada',
        'tanggal_pembayaran',
        'alasan_gagal',
        'alasan_penolakan',
        'catatan',
        'bukti_path',
        'bukti_nama_asli',
        'bukti_mime',
        'bukti_ukuran',
        'diinput_oleh',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'payment_attempt_id',
        'payer_jenis',
        'payer_nama',
        'nama_pembayar',
        'payer_user_id',
    ];

    protected $casts = [
        'nominal_dibayar' => 'integer',
        'biaya_gateway' => 'integer',
        'bukti_ukuran' => 'integer',
        'dibayar_pada' => 'datetime',
        'diverifikasi_pada' => 'datetime',
        'tanggal_pembayaran' => 'date',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_idtagihan', 'idtagihan');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function inputer()
    {
        return $this->belongsTo(User::class, 'diinput_oleh', 'id');
    }

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh', 'id');
    }

    public function attempt()
    {
        return $this->belongsTo(PaymentAttempt::class, 'payment_attempt_id', 'idpayment_attempt');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function alokasi()
    {
        return $this->hasMany(PembayaranAlokasi::class, 'pembayaran_idpembayaran', 'idpembayaran');
    }

    public function kuitansi()
    {
        return $this->hasOne(Kuitansi::class, 'pembayaran_idpembayaran', 'idpembayaran');
    }
}
