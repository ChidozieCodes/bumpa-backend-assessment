<?php

namespace App\Http\Controllers;

use App\Actions\RecordPurchase;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserPurchaseController extends Controller
{
    public function store(StorePurchaseRequest $request, User $user, RecordPurchase $record): JsonResponse
    {
        $purchase = $record->execute(
            $user,
            $request->integer('amount_kobo'),
            $request->string('reference')->toString(),
        );

        return response()->json(['data' => $purchase], 201);
    }
}
