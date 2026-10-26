<?php 
Route::get('/roles', [App\Http\Controllers\RolesController::class, 'index'])->name('roles.index')->middleware(['can:view-roles']); 
Route::post('/roles', [App\Http\Controllers\RolesController::class, 'store'])->name('roles.store');
Route::get('/roles-edit', [App\Http\Controllers\RolesController::class, 'edit'])->name('roles.edit');
Route::get('/roles-hapus', [App\Http\Controllers\RolesController::class, 'delete'])->name('roles.delete');
?>
