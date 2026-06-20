<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\PaymentMethod;
use App\Models\Invoice;
use App\Models\SyncedTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AndroidApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $merchant;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::create([
            'name' => 'Merchant User',
            'email' => 'merchant@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant',
            'api_key' => 'merchant_key_123',
            'sms_sync_key' => 'sync_key_abc',
        ]);

        $this->store = Store::create([
            'user_id' => $this->merchant->id,
            'name' => 'Test Store',
            'domain' => 'localhost',
            'api_key' => 'store_key_456',
            'is_active' => true,
        ]);
    }

    public function test_api_requires_valid_key()
    {
        $response = $this->getJson(route('api.transactions.list'));
        $response->assertStatus(401);

        $response2 = $this->getJson(route('api.transactions.list'), [
            'X-API-KEY' => 'invalid_key_789'
        ]);
        $response2->assertStatus(401);
    }

    public function test_can_list_transactions_with_user_key()
    {
        // Create a few invoices
        Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-USER-1',
            'customer_name' => 'John User',
            'customer_email' => 'john@user.com',
            'amount' => 10.00,
            'expected_amount' => 10.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token1',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->getJson(route('api.transactions.list'), [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'invoices.data')
            ->assertJsonPath('invoices.data.0.invoice_id', 'INV-USER-1');
    }

    public function test_can_list_transactions_with_store_key()
    {
        Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-STORE-1',
            'customer_name' => 'John Store',
            'customer_email' => 'john@store.com',
            'amount' => 20.00,
            'expected_amount' => 20.0002,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token2',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->getJson(route('api.transactions.list'), [
            'X-API-KEY' => 'store_key_456'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'invoices.data')
            ->assertJsonPath('invoices.data.0.invoice_id', 'INV-STORE-1');
    }

    public function test_can_filter_invoices_by_status()
    {
        Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-PAID',
            'customer_name' => 'John Paid',
            'customer_email' => 'paid@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.0001,
            'currency' => 'USDT',
            'status' => 'paid',
            'payment_link' => 'http://localhost/checkout/token1',
            'expires_at' => now()->addMinutes(30),
        ]);

        Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-PENDING',
            'customer_name' => 'John Pending',
            'customer_email' => 'pending@test.com',
            'amount' => 20.00,
            'expected_amount' => 20.0002,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token2',
            'expires_at' => now()->addMinutes(30),
        ]);

        Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-CANCELLED',
            'customer_name' => 'John Cancelled',
            'customer_email' => 'cancelled@test.com',
            'amount' => 30.00,
            'expected_amount' => 30.0003,
            'currency' => 'USDT',
            'status' => 'cancelled',
            'payment_link' => 'http://localhost/checkout/token3',
            'expires_at' => now()->addMinutes(30),
        ]);

        // 1. Filter by paid
        $responsePaid = $this->getJson(route('api.transactions.list', ['status' => 'paid']), [
            'X-API-KEY' => 'merchant_key_123'
        ]);
        $responsePaid->assertStatus(200)->assertJsonCount(1, 'invoices.data');
        $responsePaid->assertJsonPath('invoices.data.0.invoice_id', 'INV-PAID');

        // 2. Filter by canceled (single L mapping test)
        $responseCanceled = $this->getJson(route('api.transactions.list', ['status' => 'canceled']), [
            'X-API-KEY' => 'merchant_key_123'
        ]);
        $responseCanceled->assertStatus(200)->assertJsonCount(1, 'invoices.data');
        $responseCanceled->assertJsonPath('invoices.data.0.invoice_id', 'INV-CANCELLED');
    }

    public function test_can_mark_invoice_as_paid()
    {
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-MARK-PAID',
            'customer_name' => 'John',
            'customer_email' => 'john@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(route('api.transactions.mark-paid', ['id' => $invoice->invoice_id]), [], [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.status', 'paid');

        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_can_mark_invoice_as_cancelled()
    {
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-MARK-CANCELLED',
            'customer_name' => 'John',
            'customer_email' => 'john@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(route('api.transactions.mark-cancelled', ['id' => $invoice->invoice_id]), [], [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.status', 'cancelled');

        $this->assertEquals('cancelled', $invoice->fresh()->status);
    }

    public function test_can_delete_invoice()
    {
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-DELETE',
            'customer_name' => 'John',
            'customer_email' => 'john@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->deleteJson(route('api.transactions.delete', ['id' => $invoice->invoice_id]), [], [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNull(Invoice::find($invoice->id));
    }

    public function test_can_list_synced_transactions()
    {
        SyncedTransaction::create([
            'user_id' => $this->merchant->id,
            'sender' => 'bkash',
            'amount' => 1300.00,
            'trxid' => 'TRX123456',
            'raw_message' => 'Bkash cashin 1300',
            'is_used' => false,
            'timestamp' => time(),
        ]);

        $response = $this->getJson(route('api.synced-transactions.list'), [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'transactions.data')
            ->assertJsonPath('transactions.data.0.trxid', 'TRX123456');
    }
}
