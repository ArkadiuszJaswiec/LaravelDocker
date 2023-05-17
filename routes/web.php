<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [App\Http\Controllers\Api\HealthCheckController::class, 'index']);
Route::post('/auth/registration', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/auth/registration/confirm', [App\Http\Controllers\Api\AuthController::class, 'confirmRegistration']);
