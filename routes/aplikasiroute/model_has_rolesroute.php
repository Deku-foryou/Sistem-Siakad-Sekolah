<?php 
Route::get('/model_has_roles/{id}', [App\Http\Controllers\Model_has_rolesController::class, 'index'])->name('model_has_roles.index')->middleware(['can:view-model_has_roles']); 
Route::post('/model_has_roles', [App\Http\Controllers\Model_has_rolesController::class, 'store'])->name('model_has_roles.store');
Route::get('/model_has_roles-edit', [App\Http\Controllers\Model_has_rolesController::class, 'edit'])->name('model_has_roles.edit');
Route::get('/model_has_roles-hapus', [App\Http\Controllers\Model_has_rolesController::class, 'delete'])->name('model_has_roles.delete');
?>
