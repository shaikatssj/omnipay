<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Invoice;
use App\Models\ApiLog;
use App\Models\ActivityLog;

class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $merchant;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::create([
            'name' => 'Merchant Test',
            'email' => 'merchant@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant',
            'api_key' => 'mch_test_123',
        ]);

        $this->store = Store::create([
            'user_id' => $this->merchant->id,
            'name' => 'Test Store',
            'domain' => 'localhost',
            'api_key' => 'st_test_key_123',
            'is_active' => true,
        ]);
    }

    /**
     * Test creating manual invoice with is_sandbox = 1.
     */
    public function test_can_create_sandbox_invoice_manually()
    {
        $this->actingAs($this->merchant);

        $response = $this->post(route('dashboard.invoices.store'), [
            'store_id' => $this->store->id,
            'amount' => 10.50,
            'currency' => 'USDT',
            'customer_name' => 'John Client',
            'customer_email' => 'client@test.com',
            'is_sandbox' => '1',
        ]);

        $response->assertRedirect(route('dashboard.invoices'));
        $this->assertDatabaseHas('invoices', [
            'customer_name' => 'John Client',
            'amount' => 10.50,
            'is_sandbox' => true,
        ]);
    }

    /**
     * Test creating sandbox invoice via API.
     */
    public function test_can_create_sandbox_invoice_via_api()
    {
        $response = $this->withHeaders([
            'X-API-KEY' => $this->store->api_key,
        ])->postJson(route('api.payment.create'), [
            'amount' => 25.00,
            'currency' => 'USDT',
            'customer_name' => 'API Client',
            'customer_email' => 'apiclient@test.com',
            'is_sandbox' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'customer_name' => 'API Client',
            'amount' => 25.00,
            'is_sandbox' => true,
        ]);
    }

    /**
     * Test sandbox simulation updates status to paid.
     */
    public function test_sandbox_simulation_works()
    {
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-SANDBOX',
            'customer_name' => 'Tester',
            'customer_email' => 'tester@test.com',
            'amount' => 15.00,
            'expected_amount' => 15.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'is_sandbox' => true,
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(route('checkout.simulate-sandbox', ['invoice' => $invoice->invoice_id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Sandbox payment simulated successfully.',
        ]);

        $this->assertEquals('paid', $invoice->refresh()->status);
        $this->assertNotNull($invoice->paid_at);
    }

    /**
     * Test non-sandbox invoice simulation returns 400.
     */
    public function test_cannot_simulate_non_sandbox_invoice()
    {
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-REAL',
            'customer_name' => 'Real Tester',
            'customer_email' => 'tester@test.com',
            'amount' => 50.00,
            'expected_amount' => 50.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'is_sandbox' => false,
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(route('checkout.simulate-sandbox', ['invoice' => $invoice->invoice_id]));

        $response->assertStatus(400);
        $this->assertEquals('pending', $invoice->refresh()->status);
    }

    /**
     * Test API middleware logs requests.
     */
    public function test_api_middleware_logs_requests()
    {
        $this->assertEquals(0, ApiLog::count());

        $this->withHeaders([
            'X-API-KEY' => $this->store->api_key,
        ])->postJson(route('api.payment.create'), [
            'amount' => 15.00,
            'currency' => 'USDT',
            'customer_name' => 'API Client',
            'customer_email' => 'apiclient@test.com',
        ]);

        $this->assertEquals(1, ApiLog::count());
        $log = ApiLog::first();
        $this->assertEquals('POST', $log->method);
        $this->assertEquals('/api/v1/payment', $log->url);
        $this->assertEquals($this->store->id, $log->store_id);
        $this->assertEquals($this->merchant->id, $log->user_id);
    }

    /**
     * Test user login activity is logged.
     */
    public function test_login_activity_is_logged()
    {
        $this->assertEquals(0, ActivityLog::count());

        $this->post(route('login'), [
            'email' => $this->merchant->email,
            'password' => 'password',
        ]);

        $this->assertEquals(1, ActivityLog::count());
        $log = ActivityLog::first();
        $this->assertEquals('login', $log->action);
        $this->assertEquals($this->merchant->id, $log->user_id);
        $this->assertStringContainsString('logged in successfully', $log->description);
    }

    /**
     * Test store creation activity is logged.
     */
    public function test_store_creation_activity_is_logged()
    {
        $this->actingAs($this->merchant);
        
        $this->post(route('stores.store'), [
            'name' => 'New Store Log Test',
            'domain' => 'logtest.com',
        ]);

        // Only 1 log since actingAs doesn't hit login endpoint
        $this->assertEquals(1, ActivityLog::count());
        $log = ActivityLog::where('action', 'store_create')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->merchant->id, $log->user_id);
        $this->assertStringContainsString("Created store 'New Store Log Test'", $log->description);
    }
}
