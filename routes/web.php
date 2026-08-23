<?php

use App\Http\Controllers\UserAchievementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('users/{user}/achievements', UserAchievementController::class);
});
