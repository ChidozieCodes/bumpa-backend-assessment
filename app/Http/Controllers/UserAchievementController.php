<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AchievementProgress;
use Illuminate\Http\JsonResponse;

class UserAchievementController extends Controller
{
    public function __invoke(User $user, AchievementProgress $progress): JsonResponse
    {
        return response()->json($progress->for($user));
    }
}
