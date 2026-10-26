<?php 
Route::get('/role_has_permissions/{id}', [App\Http\Controllers\Role_has_permissionsController::class, 'index'])->name('role_has_permissions.index')->middleware(['can:view-role_has_permissions']); 
Route::post('/role_has_permissions', [App\Http\Controllers\Role_has_permissionsController::class, 'store'])->name('role_has_permissions.store');
Route::get('/role_has_permissions-edit', [App\Http\Controllers\Role_has_permissionsController::class, 'edit'])->name('role_has_permissions.edit');
Route::get('/role_has_permissions-hapus', [App\Http\Controllers\Role_has_permissionsController::class, 'delete'])->name('role_has_permissions.delete');
?>
