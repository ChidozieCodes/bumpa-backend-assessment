<?php

namespace App\Payments;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class PaystackCashbackGateway implements CashbackGateway
{
    public function send(User $user, int $amountKobo, string $reference): PaymentResult
    {
        if (! $user->bank_code || ! $user->account_number || ! $user->account_name) {
            throw new InvalidArgumentException('The user does not have complete bank details.');
        }

        $client = Http::baseUrl(config('payments.paystack.base_url'))
            ->withToken(config('payments.paystack.secret_key'))
            ->acceptJson()
            ->retry(2, 250)
            ->throw();

        $recipient = $client->post('/transferrecipient', [
            'type' => 'nuban',
            'name' => $user->account_name,
            'account_number' => $user->account_number,
            'bank_code' => $user->bank_code,
            'currency' => 'NGN',
        ])->json('data.recipient_code');

        $providerReference = $client->post('/transfer', [
            'source' => 'balance',
            'amount' => $amountKobo,
            'recipient' => $recipient,
            'reason' => 'Achievement badge cashback',
            'reference' => $reference,
        ])->json('data.reference');

        return new PaymentResult($providerReference);
    }

    public function provider(): string
    {
        return 'paystack';
    }
}
