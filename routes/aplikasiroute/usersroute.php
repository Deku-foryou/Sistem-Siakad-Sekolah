<?php 
Route::get('/users', [App\Http\Controllers\UsersController::class, 'index'])->name('users.index')->middleware(['can:view-users']); 
Route::post('/users', [App\Http\Controllers\UsersController::class, 'store'])->name('users.store');
Route::get('/users-edit', [App\Http\Controllers\UsersController::class, 'edit'])->name('users.edit');
Route::get('/users-hapus', [App\Http\Controllers\UsersController::class, 'delete'])->name('users.delete');


Route::get('/pegawailov', [App\Http\Controllers\PegawaiController::class, 'pegawailov'])->name('pegawai.pegawailov');
?>
