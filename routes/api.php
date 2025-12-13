<?php

use App\Http\Controllers\Api\StarWarsController;
use Illuminate\Support\Facades\Route;

Route::get('/search', [StarWarsController::class, 'search']);
Route::get('/people/{id}', [StarWarsController::class, 'showPerson']);
Route::get('/movies/{id}', [StarWarsController::class, 'showMovie']);
