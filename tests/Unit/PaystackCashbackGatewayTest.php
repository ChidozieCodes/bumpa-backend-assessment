<?php

namespace Tests\Unit;

use App\Models\User;
use App\Payments\PaystackCashbackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackCashbackGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_recipient_then_initiates_a_transfer(): void
    {
        config([
            'payments.paystack.base_url' => 'https://api.paystack.test',
            'payments.paystack.secret_key' => 'secret-test-key',
        ]);
        Http::fake([
            '*/transferrecipient' => Http::response(['data' => ['recipient_code' => 'RCP_123']]),
            '*/transfer' => Http::response(['data' => ['reference' => 'TRF_123']]),
        ]);
        $user = User::factory()->create([
            'bank_code' => '058',
            'account_number' => '0123456789',
            'account_name' => 'Ada Candidate',
        ]);

        $result = (new PaystackCashbackGateway)->send($user, 30000, 'badge-1-user-1');

        $this->assertSame('TRF_123', $result->providerReference);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/transfer')
            && $request['amount'] === 30000
            && $request['recipient'] === 'RCP_123'
            && $request['reference'] === 'badge-1-user-1'
        );
    }

    public function test_it_rejects_users_without_bank_details_before_calling_provider(): void
    {
        Http::fake();
        $this->expectException(\InvalidArgumentException::class);

        try {
            (new PaystackCashbackGateway)->send(User::factory()->create(), 30000, 'badge-1');
        } finally {
            Http::assertNothingSent();
        }
    }
}
