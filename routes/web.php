<?php

use App\Http\Controllers\UserAchievementController;
use App\Http\Controllers\UserPurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('users/{user}/achievements', UserAchievementController::class);
Route::post('users/{user}/purchases', [UserPurchaseController::class, 'store']);
