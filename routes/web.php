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

Route::get('/health',[App\Http\Controllers\Api\HealthCheckController::class, 'index']);
Route::post('/auth/registration', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/auth/registration/confirm', [App\Http\Controllers\Api\AuthController::class, 'confirmRegistration']);

Route::post('/auth/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/auth/refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);


//Projekty
    //Szczegóły na temat projektu
        Route::get('/project/{id}', [App\Http\Controllers\Api\ProjectController::class, 'show']);
    //Lista wszystich projektów
        Route::get('/project', [App\Http\Controllers\Api\ProjectController::class, 'index']);
    //Tworzenie nowego projektu
        Route::post('/project',[App\Http\Controllers\Api\ProjectController::class, 'store']);
    //Aktualizacja projektu
        Route::put('/project/{id}',[App\Http\Controllers\Api\ProjectController::class, 'update']);
    //Usunięcie projektu
        Route::delete('/project/{id}', [App\Http\Controllers\Api\ProjectController::class, 'destroy']);

//Zadania
    //Szczegóły na temat zadania
        Route::get('/project/task/{id}', 'App\Http\Controllers\Api\TaskController@show');
    //Lista wszystich zadan w projekcie
        Route::get('/project/{id_project}/task', 'App\Http\Controllers\Api\TaskController@index');
    //Tworzenie nowego zadania
        Route::post('/project/{id_project}/task', 'App\Http\Controllers\Api\TaskController@store');
    //Aktualizacja zadania
        Route::put('/project/task/{id}', 'App\Http\Controllers\Api\TaskController@update');
    //Usunięcie zadania
        Route::delete('/project/task/{id}', 'App\Http\Controllers\Api\TaskController@destroy');

//Członkowie zespołu
    //Lista wszystich członków danego projektu
        Route::get('/project/{id_project}/team', 'App\Http\Controllers\Api\TeamController@index');
    //Dodawanie nowego członka zespołu
        Route::post('/project/{id_project}/team', 'App\Http\Controllers\Api\TeamController@store');
    //Usunięcie członka zespołu
        Route::delete('/project/{id_project}/team', 'App\Http\Controllers\Api\TeamController@destroy');

//Komentarze
    //Lista wszystkich komentarzy dodanych do zadania
        Route::get('/project/task/{id_task}/comment', 'App\Http\Controllers\Api\CommentsController@index');
    //Dodawanie nowego komentarza do zadania
        Route::post('/project/task/{id_task}/comment', 'App\Http\Controllers\Api\CommentsController@store');
    //Usunięcie konkretnego komentarza
        Route::delete('/project/task/comment/{id}', 'App\Http\Controllers\Api\CommentsController@destroy');
