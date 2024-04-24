<?php

use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



Route::get('/recuperar-contraseña', [ResetPasswordController::class,'cargarRestablecerContraseña']);
Route::post('/recuperar-contraseña', [ResetPasswordController::class,'restablecerContraseña'])->name('recuperar-contraseña');
