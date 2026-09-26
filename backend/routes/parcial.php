<?php

use App\Http\Controllers\Parcial\AuthTokenController;
use App\Http\Controllers\Parcial\CitaController;
use App\Http\Controllers\Parcial\ContactoController;
use App\Http\Controllers\Parcial\DoctorController;
use App\Http\Controllers\Parcial\PacienteController;
use App\Http\Controllers\Parcial\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Parcial II (enunciado docente)
|--------------------------------------------------------------------------
| Prefijo /api (Laravel ya aplica "api"). Auth por token Sanctum.
*/

Route::post('/register', [AuthTokenController::class, 'register']);
Route::post('/login', [AuthTokenController::class, 'login']);
Route::post('/login/verify-2fa', [AuthTokenController::class, 'verify2fa']);
Route::post('/contacto', [ContactoController::class, 'enviar']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthTokenController::class, 'logout']);
    Route::get('/me', [AuthTokenController::class, 'me']);

    Route::apiResource('pacientes', PacienteController::class);
    Route::apiResource('doctores', DoctorController::class);
    Route::apiResource('citas', CitaController::class);

    Route::get('/reportes/citas-por-estado', [ReporteController::class, 'citasPorEstado']);
    Route::get('/reportes/citas-por-doctor', [ReporteController::class, 'citasPorDoctor']);

    Route::get('/contacto', [ContactoController::class, 'index']);
});
