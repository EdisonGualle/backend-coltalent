<?php

use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;


Route::get('/recuperar-contraseña', [ResetPasswordController::class,'cargarRestablecerContraseña']);
Route::post('/recuperar-contraseña', [ResetPasswordController::class,'restablecerContraseña'])->name('recuperar-contraseña');


Route::get('/show/{path}', [FileController::class, 'show'])->where('path', '.*');

// Envolver las rutas de broadcasting con el middleware auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    Broadcast::routes(); // Esta línea asegura que /broadcasting/auth esté protegido
});