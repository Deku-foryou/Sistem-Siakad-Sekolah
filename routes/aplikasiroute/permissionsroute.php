<?php 
Route::get('/permissions', [App\Http\Controllers\PermissionsController::class, 'index'])->name('permissions.index')->middleware(['can:view-permissions']); 
Route::post('/permissions', [App\Http\Controllers\PermissionsController::class, 'store'])->name('permissions.store');
Route::get('/permissions-edit', [App\Http\Controllers\PermissionsController::class, 'edit'])->name('permissions.edit');
Route::get('/permissions-hapus', [App\Http\Controllers\PermissionsController::class, 'delete'])->name('permissions.delete');
?>
