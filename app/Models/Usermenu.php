<?php
namespace App\Models;

use App\Models\Appmenunew;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usermenu extends Model
{
   use HasFactory;

   protected $table = 'usermenu';
   protected $primaryKey = 'idusermenu';
   protected $fillable = [
      'idusermenu',
      'appmenunew_idappmenunew',
      'user_iduser',
   ];
   // public $incrementing = false;
// public $timestamps = false;

   public function scopeSearch($query, $value)
   {
      $query->where('idusermenu', 'like', "%{$value}%")
         ->orWhereHas('appmenunew', function ($q) use ($value) {
            $q->where('appmenunew', 'like', "%{$value}%");
         })
      ;
   }


   /*
   public function scopeFilterUsermenu($query, $value){
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

   public function appmenunew()
   {
      return $this->belongsTo(Appmenunew::class, 'appmenunew_idappmenunew', 'idappmenunew');
   }

   public function user()
   {
      return $this->belongsTo(User::class, 'user_iduser', 'id');
   }

}