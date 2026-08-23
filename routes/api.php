<?php

use App\Http\Controllers\UserPurchaseController;
use Illuminate\Support\Facades\Route;

Route::post('users/{user}/purchases', [UserPurchaseController::class, 'store']);
