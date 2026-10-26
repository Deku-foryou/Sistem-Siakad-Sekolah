<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appmenunew extends Model
{
   use HasFactory;

   protected $table = 'appmenunew';
   protected $primaryKey = 'idappmenunew';
   protected $fillable = [
      'idappmenunew',
      'appmenumew',
      'url',
      'icon',
      'urut',
      'appmenucat_idappmenucat',
      'created_by',
      'updated_by',
   ];
   // public $incrementing = false;
// public $timestamps = false;

   public function scopeSearch($query, $value)
   {
      $query->where('idappmenunew', 'like', "%{$value}%")
         ->orWhere('appmenunew', 'like', "%{$value}%")
         ->orWhere('url', 'like', "%{$value}%")
         ->orWhere('created_by', 'like', "%{$value}%")
         ->orWhere('updated_by', 'like', "%{$value}%")
      ;
   }


   /*
   public function scopeFilterAppmenu($query, $value){
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
/*
   public function nama_table_detail()
   {
   return $this->hasMany(nama_model_child::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

   //contoh relasi untu mengambil data parent

   public function appmenucat()
   {
      return $this->belongsTo(Appmenucat::class, 'appmenucat_idappmenucat', 'idappmenucat');
   }


}