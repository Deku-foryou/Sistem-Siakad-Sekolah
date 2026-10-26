<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Appmenucat extends Model
{
   use HasFactory, LogsActivity;

   protected $table = 'appmenucat';
   protected $primaryKey = 'idappmenucat';
   protected $fillable = [
      'idappmenucat',
      'appmenucat',
      'urut',
   ];
   // public $incrementing = false;
// public $timestamps = false;

   public function scopeSearch($query, $value)
   {
      $query->where('idappmenucat', 'like', "%{$value}%")
         ->orWhere('appmenucat', 'like', "%{$value}%")
         ->orWhere('urut', 'like', "%{$value}%")
      ;
   }


   public function getActivitylogOptions(): LogOptions
   {
      return LogOptions::defaults()
         ->logAll() // log semua kolom
         ->logOnlyDirty() // hanya jika ada perubahan
         ->dontSubmitEmptyLogs();
   }

   /*
   public function scopeFilterAppmenucat($query, $value){
     if (!empty($value)) {$query->where('NAMA_FILTER_RELASI', $value);}
   }

   */
   /*
   public function scopeRangeTanggal($query, $value1, $value2)
   {
      if ($value1 && $value2) {
          $query->whereBetween('tanggal', [$value1, $value2]);
      }
   }
   */


   //contoh relasi untu menampilkan tabel child atau detail 

   public function appmenunew()
   {
      return $this->hasMany(Appmenunew::class, 'appmenucat_idappmenucat');
   }


   //contoh relasi untu mengambil data parent
/*
   public function nama_table_parent()
   {
   return $this->belongsTo(nama_model_parent::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

}