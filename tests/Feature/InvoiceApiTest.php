<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\PaymentMethod;
use App\Models\StorePaymentConfig;
use App\Models\Invoice;
use App\Models\SyncedTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $merchant;
    protected Store $store;
    protected PaymentMethod $bkash;

    protected function createTestImage(string $filename): \Illuminate\Http\UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_img_');
        $tempPng = $tempFile . '.png';
        rename($tempFile, $tempPng);

        $img = imagecreatetruecolor(10, 10);
        $color = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $color);
        imagepng($img, $tempPng);
        imagedestroy($img);

        return new \Illuminate\Http\UploadedFile($tempPng, $filename, 'image/png', null, true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic users
        $this->merchant = User::create([
            'name' => 'Merchant User',
            'email' => 'merchant@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant',
            'api_key' => 'merchant_key_123',
        ]);

        $this->store = Store::create([
            'user_id' => $this->merchant->id,
            'name' => 'Test Store',
            'domain' => 'localhost',
            'api_key' => 'store_key_456',
            'is_active' => true,
        ]);

        $this->bkash = PaymentMethod::create([
            'name' => 'bKash',
            'code' => 'bkash',
            'is_active' => true,
        ]);

        StorePaymentConfig::create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->bkash->id,
            'is_active' => true,
            'settings' => [
                'phone' => '01700000000',
                'conversion_rate' => '130.00'
            ]
        ]);
    }

    /**
     * Test invoice creation via API.
     */
    public function test_can_create_invoice_via_api()
    {
        $response = $this->postJson(route('api.payment.create'), [
            'amount' => 10.00,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@test.com',
            'currency' => 'USDT',
        ], [
            'X-API-KEY' => 'store_key_456'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'invoice_id',
                'amount',
                'expected_amount',
                'currency',
                'payment_link',
                'expires_at'
            ]);

        $this->assertDatabaseHas('invoices', [
            'store_id' => $this->store->id,
            'customer_name' => 'John Doe',
            'amount' => 10.00,
            'currency' => 'USDT',
        ]);
    }

    /**
     * Test SMS sync parsing and database log generation.
     */
    public function test_can_sync_sms_transaction()
    {
        $response = $this->postJson(route('api.sync.sms'), [
            'sender' => 'bKash',
            'msg_data' => 'You have received Tk 1300.00 from 01711223344. Ref: Shop. TrxID 9J87X65Y4 at 19/06/2026 22:00.',
        ], [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'amount' => 1300.00,
                'trxid' => '9J87X65Y4',
                'sender' => 'bkash'
            ]);

        $this->assertDatabaseHas('synced_transactions', [
            'user_id' => $this->merchant->id,
            'trxid' => '9J87X65Y4',
            'amount' => 1300.00,
            'sender' => 'bkash'
        ]);
    }

    /**
     * Test verification polling and manual submission workflows.
     */
    public function test_checkout_verification_loop()
    {
        // 1. Create Invoice
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->bkash->id,
            'invoice_id' => 'INV-TEST101',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@test.com',
            'amount' => 10.00, // 10 USD * 130 = 1300 BDT
            'expected_amount' => 10.000321,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        // 2. Poll status (initially not found)
        $pollRes = $this->postJson(route('checkout.status', ['invoice' => $invoice->invoice_id]), [
            'poll' => true
        ]);
        $pollRes->assertJson(['status' => 'not_found']);

        // 3. Sync SMS transaction
        SyncedTransaction::create([
            'user_id' => $this->merchant->id,
            'sender' => 'bkash',
            'amount' => 1300.00,
            'trxid' => '9J87X65Y4',
            'raw_message' => 'Tk 1300.00 received TrxID 9J87X65Y4',
            'is_used' => false,
            'timestamp' => time()
        ]);

        // 4. Poll status again (should detect transaction list)
        $pollRes2 = $this->postJson(route('checkout.status', ['invoice' => $invoice->invoice_id]), [
            'poll' => true
        ]);
        $pollRes2->assertJsonStructure(['status', 'transactions', 'amount', 'received', 'total']);
        $this->assertEquals('found', $pollRes2->json('status'));

        // 5. Submit correct TrxID manually (should confirm paid)
        $verifyRes = $this->postJson(route('checkout.status', ['invoice' => $invoice->invoice_id]), [
            'trx_id' => '9J87X65Y4'
        ]);
        $verifyRes->assertJsonStructure(['status', 'redirect']);
        $this->assertEquals('success', $verifyRes->json('status'));

        // Check invoice is marked as paid
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    /**
     * Test MfsParser parsing of bkash, nagad, rocket, upay, tap, cellfin, okwallet messages.
     */
    public function test_mfs_parser_formats()
    {
        $bkashMsg = 'You have received Tk 1,500.00 from 01711223344. Ref: Shop. Fee Tk 0.00. Balance Tk 5,000.00. TrxID 9J87X65Y4 at 19/06/2026 22:00';
        $parsedBkash = \App\Services\MfsParser::parse('bkash', $bkashMsg);
        $this->assertNotNull($parsedBkash);
        $this->assertEquals(1500.0, $parsedBkash['amount']);
        $this->assertEquals('9J87X65Y4', $parsedBkash['trxid']);
        $this->assertEquals('01711223344', $parsedBkash['sender']);

        $nagadMsg = 'Money Received. Amount: Tk 2,500.00 Sender: 01811223344 TxnID: 8K76Y54X3 Balance: Tk 10,000.00 19/06/2026 22:05';
        $parsedNagad = \App\Services\MfsParser::parse('nagad', $nagadMsg);
        $this->assertNotNull($parsedNagad);
        $this->assertEquals(2500.0, $parsedNagad['amount']);
        $this->assertEquals('8K76Y54X3', $parsedNagad['trxid']);

        $rocketMsg = 'Tk500.00 received from A/C:019112233449 Fee:Tk0.00, Your A/C Balance: Tk2,000.00 TxnId:7J65X43Y2';
        $parsedRocket = \App\Services\MfsParser::parse('rocket', $rocketMsg);
        $this->assertNotNull($parsedRocket);
        $this->assertEquals(500.0, $parsedRocket['amount']);
        $this->assertEquals('7J65X43Y2', $parsedRocket['trxid']);

        $cellfinMsg = 'Islami Bank CellFin Received 1000 Tk From CellFin: 01700000000 To CellFin: 01800000000 TrxId: CF88X77Y';
        $parsedCellfin = \App\Services\MfsParser::parse('cellfin', $cellfinMsg);
        $this->assertNotNull($parsedCellfin);
        $this->assertEquals(1000.0, $parsedCellfin['amount']);
        $this->assertEquals('CF88X77Y', $parsedCellfin['trxid']);
    }

    /**
     * Test MfsParser fallback parsing and direct request parameter fallbacks.
     */
    public function test_mfs_parser_and_request_fallbacks()
    {
        // 1. Test generic fallback pattern for trnxid
        $genericMsg = 'Received Tk 1200 from 01711223344. trnxid: 9J87X65Y4';
        $parsedGeneric = \App\Services\MfsParser::parse('bkash', $genericMsg);
        $this->assertNotNull($parsedGeneric);
        $this->assertEquals(1200.0, $parsedGeneric['amount']);
        $this->assertEquals('9J87X65Y4', $parsedGeneric['trxid']);

        // 2. Test SmsSyncController fallback with direct query/request parameters (override)
        $response = $this->postJson(route('api.sync.sms'), [
            'sender' => 'bKash',
            'msg_data' => 'Some unrecognized message structure without amount or trxid',
            'trnxid' => 'TRNX123456',
            'amount' => '750.50'
        ], [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'amount' => 750.50,
                'trxid' => 'TRNX123456',
                'sender' => 'bkash'
            ]);

        $this->assertDatabaseHas('synced_transactions', [
            'user_id' => $this->merchant->id,
            'trxid' => 'TRNX123456',
            'amount' => 750.50,
            'sender' => 'bkash'
        ]);
    }

    /**
     * Test SMS sync with detailed parsed metadata.
     */
    public function test_sms_sync_saves_parsed_metadata()
    {
        $response = $this->postJson(route('api.sync.sms'), [
            'sender' => 'bKash',
            'msg_data' => 'You have received Tk 1,500.00 from 01711223344. Ref: Shop. Fee Tk 0.00. Balance Tk 5,000.00. TrxID 9J87X65Y4 at 19/06/2026 22:00',
        ], [
            'X-API-KEY' => 'merchant_key_123'
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('synced_transactions', [
            'user_id' => $this->merchant->id,
            'trxid' => '9J87X65Y4',
            'sender' => 'bkash'
        ]);

        $synced = SyncedTransaction::where('trxid', '9J87X65Y4')->first();
        $this->assertNotNull($synced->meta_data);
        $this->assertEquals('01711223344', $synced->meta_data['sender']);
        $this->assertEquals(1500.0, $synced->meta_data['amount']);
    }

    /**
     * Test refund endpoints.
     */
    public function test_merchant_can_initiate_refund()
    {
        // 1. Create a paid invoice
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->bkash->id,
            'invoice_id' => 'INV-PAID-102',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.000123,
            'currency' => 'USDT',
            'status' => 'paid',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
            'paid_at' => now(),
        ]);

        // 2. Perform refund as merchant user
        $response = $this->actingAs($this->merchant)
            ->post(route('dashboard.invoices.refund', ['invoice' => $invoice->id]), [
                'reason' => 'Customer request'
            ]);

        $response->assertRedirect();
        
        $invoice->refresh();
        $this->assertEquals('refunded', $invoice->status);
    }

    /**
     * Test merchant can manually create invoice.
     */
    public function test_merchant_can_manually_create_invoice()
    {
        $response = $this->actingAs($this->merchant)
            ->post(route('dashboard.invoices.store'), [
                'store_id' => $this->store->id,
                'amount' => 15.50,
                'customer_name' => 'Alice Smith',
                'customer_email' => 'alice@test.com',
                'currency' => 'USDT',
            ]);

        $response->assertRedirect(route('dashboard.invoices'));
        
        $this->assertDatabaseHas('invoices', [
            'store_id' => $this->store->id,
            'customer_name' => 'Alice Smith',
            'amount' => 15.50,
            'currency' => 'USDT',
            'status' => 'pending',
        ]);
    }

    /**
     * Test QR upload & register.
     */
    public function test_merchant_can_upload_and_register_qr()
    {
        $file = $this->createTestImage('qr.png');
        
        $response = $this->actingAs($this->merchant)
            ->post(route('dashboard.qr.upload'), [
                'qr_image' => $file,
                'qr_data' => 'https://bkash.com/pay/merchant_wallet_1'
            ]);

        $response->assertRedirect();
        
        $qrHash = hash('sha256', 'https://bkash.com/pay/merchant_wallet_1');
        
        $this->assertDatabaseHas('qr_registry', [
            'qr_data_hash' => $qrHash,
            'owner_user_id' => $this->merchant->id,
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('qr_checksums', [
            'user_id' => $this->merchant->id,
            'qr_data' => 'https://bkash.com/pay/merchant_wallet_1'
        ]);
    }

    /**
     * Test cannot upload blacklisted QR code.
     */
    public function test_cannot_upload_blacklisted_qr()
    {
        $qrData = 'https://bkash.com/pay/fraud_wallet';
        $qrHash = hash('sha256', $qrData);

        \App\Models\QrBlacklist::create([
            'qr_data_hash' => $qrHash,
            'note' => 'Fraud wallet block'
        ]);

        $file = $this->createTestImage('qr_fraud.png');

        $response = $this->actingAs($this->merchant)
            ->from(route('dashboard.qr'))
            ->post(route('dashboard.qr.upload'), [
                'qr_image' => $file,
                'qr_data' => $qrData
            ]);

        $response->assertRedirect(route('dashboard.qr'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Anti-Hijack Block', session('error'));
    }

    /**
     * Test cannot hijack another merchant\'s registered QR code.
     */
    public function test_cannot_hijack_other_merchant_qr()
    {
        $otherUser = User::create([
            'name' => 'Other Merchant',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant'
        ]);

        $qrData = 'https://bkash.com/pay/legit_wallet';
        $qrHash = hash('sha256', $qrData);

        \App\Models\QrRegistry::create([
            'qr_data_hash' => $qrHash,
            'owner_user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        $file = $this->createTestImage('qr_hijack.png');

        $response = $this->actingAs($this->merchant)
            ->from(route('dashboard.qr'))
            ->post(route('dashboard.qr.upload'), [
                'qr_image' => $file,
                'qr_data' => $qrData
            ]);

        $response->assertRedirect(route('dashboard.qr'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Anti-Hijack Block', session('error'));
    }

    /**
     * Test admin can manage QR blocklist.
     */
    public function test_admin_can_manage_qr_blocklist()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $qrData = 'https://bkash.com/pay/fraud_wallet_123';
        $qrHash = hash('sha256', $qrData);

        // 1. Admin can add to blocklist
        $response = $this->actingAs($admin)
            ->post(route('dashboard.qr.blacklist.store'), [
                'qr_data' => $qrData,
                'note' => 'Stolen wallet address'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('qr_blacklist', [
            'qr_data_hash' => $qrHash,
            'note' => 'Stolen wallet address'
        ]);

        $entry = \App\Models\QrBlacklist::where('qr_data_hash', $qrHash)->first();

        // 2. Admin can delete from blocklist
        $delResponse = $this->actingAs($admin)
            ->delete(route('dashboard.qr.blacklist.delete', ['id' => $entry->id]));

        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('qr_blacklist', [
            'qr_data_hash' => $qrHash
        ]);
    }

    /**
     * Test merchant cannot manage QR blocklist.
     */
    public function test_merchant_cannot_manage_qr_blocklist()
    {
        $qrData = 'https://bkash.com/pay/fraud_wallet_123';

        // 1. Merchant post request is rejected
        $response = $this->actingAs($this->merchant)
            ->post(route('dashboard.qr.blacklist.store'), [
                'qr_data' => $qrData,
                'note' => 'Hacker'
            ]);

        $response->assertStatus(403);

        // Seed blacklist entry
        $entry = \App\Models\QrBlacklist::create([
            'qr_data_hash' => hash('sha256', $qrData),
            'note' => 'Fraud'
        ]);

        // 2. Merchant delete request is rejected
        $delResponse = $this->actingAs($this->merchant)
            ->delete(route('dashboard.qr.blacklist.delete', ['id' => $entry->id]));

        $delResponse->assertStatus(403);
    }

    /**
     * Test blocklisting a QR payload deregisters existing registries.
     */
    public function test_blocklist_removes_existing_registries()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $qrData = 'https://bkash.com/pay/victim_wallet';
        $qrHash = hash('sha256', $qrData);

        // 1. Register a QR code
        \App\Models\QrRegistry::create([
            'qr_data_hash' => $qrHash,
            'owner_user_id' => $this->merchant->id,
            'status' => 'active'
        ]);

        \App\Models\QrChecksum::create([
            'user_id' => $this->merchant->id,
            'file_path' => 'uploads/qrcodes/temp.png',
            'checksum' => 'mock_md5_checksum',
            'qr_data' => $qrData,
            'qr_data_hash' => $qrHash
        ]);

        $this->assertDatabaseHas('qr_registry', ['qr_data_hash' => $qrHash]);
        $this->assertDatabaseHas('qr_checksums', ['qr_data_hash' => $qrHash]);

        // 2. Blocklist it via admin
        $response = $this->actingAs($admin)
            ->post(route('dashboard.qr.blacklist.store'), [
                'qr_data' => $qrData,
                'note' => 'Hijacked wallet'
            ]);

        $response->assertRedirect();

        // 3. Verify registry and checksum entries are gone (deregistered)
        $this->assertDatabaseMissing('qr_registry', ['qr_data_hash' => $qrHash]);
        $this->assertDatabaseMissing('qr_checksums', ['qr_data_hash' => $qrHash]);
        $this->assertDatabaseHas('qr_blacklist', ['qr_data_hash' => $qrHash]);
    }

    /**
     * Test selecting payment method returns configured QR code.
     */
    public function test_checkout_selection_returns_qr_code()
    {
        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->bkash->id,
            'invoice_id' => 'INV-QR-TEST',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.00,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();
        
        $config->update([
            'settings' => [
                'phone' => '01700000000',
                'conversion_rate' => '130.00',
                'qr_code' => 'uploads/configs/test_bkash_qr.png'
            ]
        ]);

        $response = $this->postJson(route('checkout.select', ['invoice' => $invoice->invoice_id]), [
            'method_code' => 'bkash'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'init_data' => [
                    'qr_code' => asset('uploads/configs/test_bkash_qr.png')
                ]
            ]);
    }

    /**
     * Test Binance Pay Transactions Note Verification workflow.
     */
    public function test_binance_pay_verification_loop()
    {
        $binance = PaymentMethod::create([
            'name' => 'Binance Pay',
            'code' => 'binance',
            'is_active' => true,
        ]);

        StorePaymentConfig::create([
            'store_id' => $this->store->id,
            'payment_method_id' => $binance->id,
            'is_active' => true,
            'settings' => [
                'api_key' => 'd41d8cd98f00b204e9800998ecf8427e12345678901234567890123456789012',
                'api_secret' => 'mock_secret'
            ]
        ]);

        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'payment_method_id' => $binance->id,
            'invoice_id' => 'INV-BIN-TEST',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@test.com',
            'amount' => 15.00,
            'expected_amount' => 15.00,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        // 1. Select method to generate 4-digit note
        $selectRes = $this->postJson(route('checkout.select', ['invoice' => $invoice->invoice_id]), [
            'method_code' => 'binance'
        ]);

        $selectRes->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $note = $selectRes->json('init_data.payment_note');
        $this->assertNotNull($note);
        $this->assertEquals(4, strlen($note));

        // 2. Poll status (initially pending since mock session isn't set)
        $pollRes = $this->postJson(route('checkout.status', ['invoice' => $invoice->invoice_id]));
        $pollRes->assertJson(['status' => 'pending']);

        // 3. Simulate payment via session
        session(['simulated_binance_paid_' . $invoice->invoice_id => true]);

        // 4. Poll status again (should detect mock transaction and mark as paid)
        $pollRes2 = $this->postJson(route('checkout.status', ['invoice' => $invoice->invoice_id]));
        $pollRes2->assertJson([
            'status' => 'success',
            'transaction_id' => 'SIM-BIN-' . $invoice->invoice_id
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('SIM-BIN-' . $invoice->invoice_id, $invoice->meta_data['binance_transaction_id']);
    }

    /**
     * Test merchant can view stores list.
     */
    public function test_merchant_can_view_stores_list()
    {
        $response = $this->actingAs($this->merchant)
            ->get(route('stores.index'));

        $response->assertStatus(200)
            ->assertSee($this->store->name);
    }

    /**
     * Test merchant can edit store details.
     */
    public function test_merchant_can_edit_store()
    {
        // 1. Show edit page
        $response = $this->actingAs($this->merchant)
            ->get(route('stores.edit', ['store' => $this->store->id]));
        $response->assertStatus(200);

        // 2. Put update details
        $updateRes = $this->actingAs($this->merchant)
            ->put(route('stores.update', ['store' => $this->store->id]), [
                'name' => 'Updated Store Name',
                'domain' => 'updateddomain.com'
            ]);

        $updateRes->assertRedirect(route('stores.index'));
        $this->assertDatabaseHas('stores', [
            'id' => $this->store->id,
            'name' => 'Updated Store Name',
            'domain' => 'updateddomain.com'
        ]);
    }

    /**
     * Test merchant can toggle store active status.
     */
    public function test_merchant_can_toggle_store_status()
    {
        $this->assertTrue($this->store->is_active);

        $response = $this->actingAs($this->merchant)
            ->post(route('stores.toggle-status', ['store' => $this->store->id]));

        $response->assertRedirect(route('stores.index'));
        
        $this->store->refresh();
        $this->assertFalse($this->store->is_active);
    }

    /**
     * Test merchant can regenerate store API key.
     */
    public function test_merchant_can_regenerate_api_key()
    {
        $oldKey = $this->store->api_key;

        $response = $this->actingAs($this->merchant)
            ->post(route('stores.regenerate-key', ['store' => $this->store->id]));

        $response->assertRedirect(route('stores.index'));
        
        $this->store->refresh();
        $this->assertNotEquals($oldKey, $this->store->api_key);
        $this->assertStringStartsWith('st_', $this->store->api_key);
    }

    /**
     * Test merchant can delete store.
     */
    public function test_merchant_can_delete_store()
    {
        $response = $this->actingAs($this->merchant)
            ->delete(route('stores.delete', ['store' => $this->store->id]));

        $response->assertRedirect(route('stores.index'));
        $this->assertDatabaseMissing('stores', ['id' => $this->store->id]);
    }

    /**
     * Test admin can upload custom gateway logo.
     */
    public function test_admin_can_upload_gateway_logo()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $file = $this->createTestImage('logo.png');

        $response = $this->actingAs($admin)
            ->post(route('admin.gateways.logo', ['id' => $this->bkash->id]), [
                'logo' => $file
            ]);

        $response->assertRedirect();
        
        $this->bkash->refresh();
        $this->assertNotNull($this->bkash->logo);
        $this->assertFileExists(public_path($this->bkash->logo));

        // Clean up uploaded test logo
        if (file_exists(public_path($this->bkash->logo))) {
            @unlink(public_path($this->bkash->logo));
        }
    }

    /**
     * Test merchant can upload custom store gateway logo.
     */
    public function test_merchant_can_upload_custom_gateway_logo()
    {
        $logo = $this->createTestImage('custom_logo.png');

        $response = $this->actingAs($this->merchant)
            ->post(route('stores.configs.update', ['store' => $this->store->id]), [
                'active_methods' => [$this->bkash->id],
                'settings' => [
                    $this->bkash->id => [
                        'phone' => '01711223344',
                        'conversion_rate' => '135.00',
                        'logo' => $logo
                    ]
                ]
            ]);

        $response->assertRedirect(route('stores.configs.edit', ['store' => $this->store->id]));

        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();

        $this->assertNotNull($config->settings['logo'] ?? null);
        $this->assertFileExists(public_path($config->settings['logo']));

        // Clean up
        if (file_exists(public_path($config->settings['logo']))) {
            @unlink(public_path($config->settings['logo']));
        }
    }

    /**
     * Test merchant can remove custom store gateway logo.
     */
    public function test_merchant_can_remove_custom_gateway_logo()
    {
        // 1. First save configuration with a custom logo path mock
        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();
        
        $fakeLogoPath = 'uploads/logos/fake_logo.png';
        @file_put_contents(public_path($fakeLogoPath), 'fake image data');

        $config->update([
            'settings' => [
                'phone' => '01711223344',
                'conversion_rate' => '135.00',
                'logo' => $fakeLogoPath
            ]
        ]);

        $this->assertFileExists(public_path($fakeLogoPath));

        // 2. Submit remove request
        $response = $this->actingAs($this->merchant)
            ->post(route('stores.configs.update', ['store' => $this->store->id]), [
                'active_methods' => [$this->bkash->id],
                'settings' => [
                    $this->bkash->id => [
                        'phone' => '01711223344',
                        'conversion_rate' => '135.00',
                        'remove_logo' => '1'
                    ]
                ]
            ]);

        $response->assertRedirect(route('stores.configs.edit', ['store' => $this->store->id]));

        $config->refresh();
        $this->assertNull($config->settings['logo'] ?? null);
        $this->assertFileDoesNotExist(public_path($fakeLogoPath));
    }

    /**
     * Test checkout selection endpoint returns custom gateway logo.
     */
    public function test_checkout_endpoint_returns_custom_gateway_logo()
    {
        // 1. Set custom logo path
        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();
        
        $config->update([
            'settings' => [
                'phone' => '01711223344',
                'conversion_rate' => '135.00',
                'logo' => 'uploads/logos/custom_test_logo.png'
            ]
        ]);

        $invoice = Invoice::create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->bkash->id,
            'invoice_id' => 'INV-TEST-LOGO-1',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.000321,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(route('checkout.select', ['invoice' => $invoice->invoice_id]), [
            'method_code' => 'bkash'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'init_data' => [
                    'gateway_logo' => asset('uploads/logos/custom_test_logo.png')
                ]
            ]);
    }

    /**
     * Test verification of API keys via endpoint.
     */
    public function test_key_verification_endpoint()
    {
        // 1. Test missing API key
        $response1 = $this->getJson(route('api.verify.key'));
        $response1->assertStatus(401)
            ->assertJson([
                'valid' => false,
                'message' => 'API key is required'
            ]);

        // 2. Test invalid API key
        $response2 = $this->getJson(route('api.verify.key'), [
            'X-API-KEY' => 'invalid_key'
        ]);
        $response2->assertStatus(401)
            ->assertJson([
                'valid' => false,
                'message' => 'Invalid or inactive API key'
            ]);

        // 3. Test valid Merchant API key
        $response3 = $this->getJson(route('api.verify.key'), [
            'X-API-KEY' => 'merchant_key_123'
        ]);
        $response3->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'type' => 'merchant',
                'name' => 'Merchant User',
                'email' => 'merchant@test.com',
                'stores_count' => 1
            ]);

        // 4. Test valid Store API key
        $response4 = $this->getJson(route('api.verify.key'), [
            'X-API-KEY' => 'store_key_456'
        ]);
        $response4->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'type' => 'store',
                'name' => 'Test Store',
                'domain' => 'localhost',
                'merchant_name' => 'Merchant User'
            ]);
    }

    /**
     * Test compatibility charge creation and verification endpoints.
     */
    public function test_compatibility_endpoints()
    {
        // 1. Create charge via POST /api/create-charge
        $response1 = $this->postJson('/api/create-charge', [
            'full_name' => 'Legacy Customer',
            'email_mobile' => 'legacy@customer.com',
            'amount' => '25.00',
            'currency' => 'BDT',
            'webhook_url' => 'https://my-whmcs.com/callback',
        ], [
            'mh-piprapay-api-key' => 'store_key_456'
        ]);

        $response1->assertStatus(200)
            ->assertJson([
                'status' => true,
                'success' => true,
                'currency' => 'BDT',
            ])
            ->assertJsonStructure(['pp_url', 'invoice_id']);

        $invoiceId = $response1->json('invoice_id');

        // 2. Verify payment via POST /api/verify-payments
        $response2 = $this->postJson('/api/verify-payments', [
            'pp_id' => $invoiceId
        ], [
            'mh-piprapay-api-key' => 'store_key_456'
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'status' => 'pending',
                'pp_id' => $invoiceId,
                'amount' => 25.00
            ]);

        // 3. Create charge via POST /checkout/redirect (non-api web route)
        $response3 = $this->postJson('/checkout/redirect', [
            'full_name' => 'Legacy Customer 2',
            'email_mobile' => 'legacy2@customer.com',
            'amount' => '30.00',
            'currency' => 'USDT',
        ], [
            'MHS-PIPRAPAY-API-KEY' => 'store_key_456'
        ]);

        $response3->assertStatus(200)
            ->assertJson([
                'status' => true,
                'success' => true,
            ])
            ->assertJsonStructure(['pp_url', 'invoice_id']);

        $invoiceId2 = $response3->json('invoice_id');

        // 4. Verify payment via POST /verify-payment (non-api web route)
        $response4 = $this->postJson('/verify-payment', [
            'pp_id' => $invoiceId2
        ], [
            'X-API-KEY' => 'store_key_456'
        ]);

        $response4->assertStatus(200)
            ->assertJson([
                'status' => 'pending',
                'pp_id' => $invoiceId2,
                'amount' => 30.00
            ]);
    }

    public function test_merchant_system_toggles()
    {
        // 1. Setup Admin and Merchant users and stores
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'password' => bcrypt('admin123')]);
        $merchant = \App\Models\User::factory()->create(['role' => 'merchant', 'password' => bcrypt('merchant123')]);

        $adminStore = \App\Models\Store::create([
            'user_id' => $admin->id,
            'name' => 'Admin Store',
            'api_key' => 'admin_store_key',
            'is_active' => true,
        ]);

        $merchantStore = \App\Models\Store::create([
            'user_id' => $merchant->id,
            'name' => 'Merchant Store',
            'api_key' => 'merchant_store_key',
            'is_active' => true,
        ]);

        // 2. Turn merchant system OFF
        \App\Models\Setting::set('merchant_system_enabled', '0');

        // Verify registration is disabled
        $response = $this->get('/register');
        $response->assertRedirect('/login');

        $responsePost = $this->post('/register', [
            'name' => 'New Merchant',
            'email' => 'new@merchant.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responsePost->assertRedirect('/login');

        // Verify merchant login is blocked
        $responseLogin = $this->post('/login', [
            'email' => $merchant->email,
            'password' => 'merchant123',
        ]);
        $responseLogin->assertSessionHasErrors(['email']);
        $this->assertFalse(\Illuminate\Support\Facades\Auth::check());

        // Verify admin login is allowed
        $responseAdminLogin = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'admin123',
        ]);
        $responseAdminLogin->assertRedirect('/dashboard');
        $this->assertTrue(\Illuminate\Support\Facades\Auth::check());
        \Illuminate\Support\Facades\Auth::logout();

        // Verify merchant API requests are blocked
        $apiResponse = $this->postJson('/api/v1/payment', [
            'amount' => 10.00,
            'customer_name' => 'Customer',
            'customer_email' => 'customer@test.com',
        ], [
            'X-API-KEY' => 'merchant_store_key'
        ]);
        $apiResponse->assertStatus(403);

        // Verify admin API requests are allowed
        $adminApiResponse = $this->postJson('/api/v1/payment', [
            'amount' => 10.00,
            'customer_name' => 'Customer',
            'customer_email' => 'customer@test.com',
        ], [
            'X-API-KEY' => 'admin_store_key'
        ]);
        $adminApiResponse->assertStatus(201);

        // 3. Turn merchant system back ON
        \App\Models\Setting::set('merchant_system_enabled', '1');

        // Verify registration works again
        $responseOn = $this->get('/register');
        $responseOn->assertStatus(200);

        // Verify merchant login works again
        $responseLoginOn = $this->post('/login', [
            'email' => $merchant->email,
            'password' => 'merchant123',
        ]);
        $responseLoginOn->assertRedirect('/dashboard');
        $this->assertTrue(\Illuminate\Support\Facades\Auth::check());
        \Illuminate\Support\Facades\Auth::logout();

        // Verify merchant API key works again
        $apiResponseOn = $this->postJson('/api/v1/payment', [
            'amount' => 10.00,
            'customer_name' => 'Customer',
            'customer_email' => 'customer@test.com',
        ], [
            'X-API-KEY' => 'merchant_store_key'
        ]);
        $apiResponseOn->assertStatus(201);
    }

    public function test_mobile_auth_login_api()
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'password' => bcrypt('admin123'), 'api_key' => 'admin_user_api_key']);
        $merchant = \App\Models\User::factory()->create(['role' => 'merchant', 'password' => bcrypt('merchant123'), 'api_key' => 'merchant_user_api_key']);

        // 1. Test successful merchant login in Multi-User Mode
        \App\Models\Setting::set('merchant_system_enabled', '1');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $merchant->email,
            'password' => 'merchant123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'role' => 'merchant',
            ]);

        $smsSyncKey = $response->json('sms_sync_key');
        $this->assertNotNull($smsSyncKey);
        $this->assertStringStartsWith('sync_', $smsSyncKey);
        $this->assertEquals($smsSyncKey, $response->json('api_key'));

        // Test syncing SMS using the retrieved sms_sync_key
        $syncResponse = $this->postJson(route('api.sync.sms'), [
            'sender' => 'bKash',
            'msg_data' => 'You have received Tk 1500.00 from 01711223344. Ref: Shop. TrxID 9J87X65Y4 at 19/06/2026 22:00.',
        ], [
            'X-API-KEY' => $smsSyncKey
        ]);
        $syncResponse->assertStatus(201);

        // 2. Test invalid credentials
        $responseFail = $this->postJson('/api/v1/auth/login', [
            'email' => $merchant->email,
            'password' => 'wrongpassword',
        ]);
        $responseFail->assertStatus(401);

        // 3. Test login under disabled merchant system
        \App\Models\Setting::set('merchant_system_enabled', '0');

        // Merchant login should be blocked
        $responseBlock = $this->postJson('/api/v1/auth/login', [
            'email' => $merchant->email,
            'password' => 'merchant123',
        ]);
        $responseBlock->assertStatus(403);

        // Admin login should be allowed
        $responseAdmin = $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'admin123',
        ]);
        $responseAdmin->assertStatus(200)
            ->assertJson([
                'success' => true,
                'role' => 'admin',
            ]);

        // Restore setting
        \App\Models\Setting::set('merchant_system_enabled', '1');
    }

    public function test_bulk_delete_invoices()
    {
        // 1. Create two invoices belonging to the merchant's store
        $invoice1 = Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-DEL-1',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token1',
            'expires_at' => now()->addMinutes(30),
        ]);

        $invoice2 = Invoice::create([
            'store_id' => $this->store->id,
            'invoice_id' => 'INV-DEL-2',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@test.com',
            'amount' => 20.00,
            'expected_amount' => 20.0002,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token2',
            'expires_at' => now()->addMinutes(30),
        ]);

        // 2. Create another merchant's invoice
        $otherMerchant = User::create([
            'name' => 'Other Merchant',
            'email' => 'other@merchant.com',
            'password' => bcrypt('password'),
            'role' => 'merchant'
        ]);

        $otherStore = Store::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Other Store',
            'domain' => 'localhost',
            'api_key' => 'store_key_other',
            'is_active' => true,
        ]);

        $otherInvoice = Invoice::create([
            'store_id' => $otherStore->id,
            'invoice_id' => 'INV-DEL-3',
            'customer_name' => 'Bob Smith',
            'customer_email' => 'bob@test.com',
            'amount' => 30.00,
            'expected_amount' => 30.0003,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token3',
            'expires_at' => now()->addMinutes(30),
        ]);

        // 3. Post bulk delete request as $this->merchant targeting all three invoices
        $response = $this->actingAs($this->merchant)
            ->post(route('dashboard.invoices.bulk-delete'), [
                'ids' => [$invoice1->id, $invoice2->id, $otherInvoice->id]
            ]);

        $response->assertRedirect();
        
        // Assert own invoices are deleted
        $this->assertDatabaseMissing('invoices', ['id' => $invoice1->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice2->id]);
        
        // Assert other merchant's invoice is NOT deleted
        $this->assertDatabaseHas('invoices', ['id' => $otherInvoice->id]);

        // 4. Test admin can delete other merchant's invoice
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_del@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        $adminResponse = $this->actingAs($admin)
            ->post(route('dashboard.invoices.bulk-delete'), [
                'ids' => [$otherInvoice->id]
            ]);

        $adminResponse->assertRedirect();
        $this->assertDatabaseMissing('invoices', ['id' => $otherInvoice->id]);
    }

    /**
     * Test cannot upload blacklisted QR code in store gateway configuration.
     */
    public function test_cannot_upload_blacklisted_qr_in_configs()
    {
        $qrData = 'https://bkash.com/pay/blacklisted_config_wallet';
        $qrHash = hash('sha256', $qrData);

        \App\Models\QrBlacklist::create([
            'qr_data_hash' => $qrHash,
            'note' => 'Stolen wallet block'
        ]);

        $file = $this->createTestImage('qr_blocked.png');

        $response = $this->actingAs($this->merchant)
            ->from(route('stores.configs.edit', ['store' => $this->store->id]))
            ->post(route('stores.configs.update', ['store' => $this->store->id]), [
                'active_methods' => [$this->bkash->id],
                'settings' => [
                    $this->bkash->id => [
                        'phone' => '01711223344',
                        'conversion_rate' => '135.00',
                        'qr_code' => $file,
                        'qr_code_data' => $qrData
                    ]
                ]
            ]);

        $response->assertRedirect(route('stores.configs.edit', ['store' => $this->store->id]));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Anti-Hijack Block', session('error'));

        // Assert that the config settings did NOT get updated to the new phone/rate
        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();
        $this->assertNotEquals('01711223344', $config->settings['phone'] ?? null);
    }

    /**
     * Test cannot hijack other merchant's QR code in store gateway configuration.
     */
    public function test_cannot_hijack_other_merchant_qr_in_configs()
    {
        $otherUser = User::create([
            'name' => 'Other Merchant',
            'email' => 'other_config@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant'
        ]);

        $qrData = 'https://bkash.com/pay/other_config_wallet';
        $qrHash = hash('sha256', $qrData);

        \App\Models\QrRegistry::create([
            'qr_data_hash' => $qrHash,
            'owner_user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        $file = $this->createTestImage('qr_hijack.png');

        $response = $this->actingAs($this->merchant)
            ->from(route('stores.configs.edit', ['store' => $this->store->id]))
            ->post(route('stores.configs.update', ['store' => $this->store->id]), [
                'active_methods' => [$this->bkash->id],
                'settings' => [
                    $this->bkash->id => [
                        'phone' => '01711223344',
                        'conversion_rate' => '135.00',
                        'qr_code' => $file,
                        'qr_code_data' => $qrData
                    ]
                ]
            ]);

        $response->assertRedirect(route('stores.configs.edit', ['store' => $this->store->id]));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Anti-Hijack Block', session('error'));

        // Assert no changes saved
        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();
        $this->assertNotEquals('01711223344', $config->settings['phone'] ?? null);
    }

    /**
     * Test can upload valid QR code in store gateway configuration.
     */
    public function test_can_upload_valid_qr_in_configs()
    {
        $qrData = 'https://bkash.com/pay/my_legit_wallet';
        $qrHash = hash('sha256', $qrData);

        $file = $this->createTestImage('qr_valid.png');

        $response = $this->actingAs($this->merchant)
            ->from(route('stores.configs.edit', ['store' => $this->store->id]))
            ->post(route('stores.configs.update', ['store' => $this->store->id]), [
                'active_methods' => [$this->bkash->id],
                'settings' => [
                    $this->bkash->id => [
                        'phone' => '01711223344',
                        'conversion_rate' => '135.00',
                        'qr_code' => $file,
                        'qr_code_data' => $qrData
                    ]
                ]
            ]);

        $response->assertRedirect(route('stores.configs.edit', ['store' => $this->store->id]));

        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();
        $this->assertEquals('01711223344', $config->settings['phone']);
        $this->assertNotNull($config->settings['qr_code'] ?? null);

        // Verify registered in QrRegistry and QrChecksum
        $this->assertDatabaseHas('qr_registry', [
            'qr_data_hash' => $qrHash,
            'owner_user_id' => $this->merchant->id
        ]);

        $this->assertDatabaseHas('qr_checksums', [
            'user_id' => $this->merchant->id,
            'qr_data' => $qrData
        ]);

        // Clean up
        if (file_exists(public_path($config->settings['qr_code']))) {
            @unlink(public_path($config->settings['qr_code']));
        }
    }

    /**
     * Test QR payload check endpoint.
     */
    public function test_qr_check_endpoint()
    {
        $qrData = 'https://bkash.com/pay/check_wallet';
        $qrHash = hash('sha256', $qrData);

        // 1. Valid and available payload
        $response = $this->actingAs($this->merchant)
            ->postJson(route('dashboard.qr.check'), [
                'qr_data' => $qrData
            ]);
        $response->assertStatus(200)
            ->assertJson([
                'allowed' => true
            ]);

        // 2. Blacklisted payload
        \App\Models\QrBlacklist::create([
            'qr_data_hash' => $qrHash,
            'note' => 'Blacklisted'
        ]);

        $responseBlacklisted = $this->actingAs($this->merchant)
            ->postJson(route('dashboard.qr.check'), [
                'qr_data' => $qrData
            ]);
        $responseBlacklisted->assertStatus(200)
            ->assertJson([
                'allowed' => false
            ]);
    }

    /**
     * Test merchant can remove custom store gateway QR code.
     */
    public function test_merchant_can_remove_custom_gateway_qr_code()
    {
        // 1. First save configuration with a custom QR code path mock
        $config = StorePaymentConfig::where('store_id', $this->store->id)
            ->where('payment_method_id', $this->bkash->id)
            ->first();
        
        $fakeQrPath = 'uploads/configs/fake_qr.png';
        @file_put_contents(public_path($fakeQrPath), 'fake image data');

        $config->update([
            'settings' => [
                'phone' => '01711223344',
                'conversion_rate' => '135.00',
                'qr_code' => $fakeQrPath
            ]
        ]);

        $this->assertFileExists(public_path($fakeQrPath));

        // 2. Submit remove request
        $response = $this->actingAs($this->merchant)
            ->post(route('stores.configs.update', ['store' => $this->store->id]), [
                'active_methods' => [$this->bkash->id],
                'settings' => [
                    $this->bkash->id => [
                        'phone' => '01711223344',
                        'conversion_rate' => '135.00',
                        'remove_qr_code' => '1'
                    ]
                ]
            ]);

        $response->assertRedirect(route('stores.configs.edit', ['store' => $this->store->id]));

        $config->refresh();
        $this->assertNull($config->settings['qr_code'] ?? null);
        $this->assertNull($config->settings['remove_qr_code'] ?? null);
        $this->assertFileDoesNotExist(public_path($fakeQrPath));
    }

    /**
     * Test login brute force rate limiting lockout.
     */
    public function test_login_rate_limiting_lockout()
    {
        $user = User::create([
            'name' => 'Throttling User',
            'email' => 'throttle@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant'
        ]);

        // Attempt login with wrong password 5 times
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'throttle@test.com',
                'password' => 'wrong-password'
            ]);
            $response->assertSessionHasErrors('email');
        }

        // The 6th attempt should result in a lock/throttle redirect error
        $responseLock = $this->post('/login', [
            'email' => 'throttle@test.com',
            'password' => 'wrong-password'
        ]);
        
        $responseLock->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    /**
     * Test math captcha validation in login form.
     */
    public function test_login_captcha_validation()
    {
        // Enable captcha globally
        \App\Models\Setting::set('captcha_enabled', '1');

        $user = User::create([
            'name' => 'Captcha User',
            'email' => 'captcha@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant'
        ]);

        // 1. Submit login without captcha input
        $response1 = $this->post('/login', [
            'email' => 'captcha@test.com',
            'password' => 'password'
        ]);
        $response1->assertSessionHasErrors('captcha_answer');

        // 2. Submit login with wrong captcha input
        session(['captcha_result' => 12]);
        $response2 = $this->post('/login', [
            'email' => 'captcha@test.com',
            'password' => 'password',
            'captcha_answer' => '13'
        ]);
        $response2->assertSessionHasErrors('email');

        // 3. Submit login with correct captcha input
        session(['captcha_result' => 12]);
        $response3 = $this->post('/login', [
            'email' => 'captcha@test.com',
            'password' => 'password',
            'captcha_answer' => '12'
        ]);
        // Since 2FA is 'none' by default, it will login successfully and redirect
        $response3->assertRedirect();
        $this->assertAuthenticatedAs($user);
        
        // Reset setting
        \App\Models\Setting::set('captcha_enabled', '0');
    }

    /**
     * Test 2FA authentication flow - Email OTP.
     */
    public function test_two_factor_email_flow()
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::create([
            'name' => '2FA Email User',
            'email' => '2fa_email@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant',
            'two_factor_method' => 'email'
        ]);

        // 1. Post valid credentials
        $response = $this->post('/login', [
            'email' => '2fa_email@test.com',
            'password' => 'password'
        ]);

        // Should redirect to 2fa verification screen
        $response->assertRedirect(route('auth.2fa'));
        $this->assertGuest(); // Not fully logged in yet
        
        // Retrieve dynamic code generated in DB
        $user->refresh();
        $this->assertNotNull($user->two_factor_code);
        $this->assertNotNull($user->two_factor_expires_at);

        // Assert code was emailed
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\DynamicNotificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // 2. Post wrong code
        $responseVerifyFail = $this->post('/login/2fa', [
            'code' => '999999'
        ]);
        $responseVerifyFail->assertSessionHasErrors('code');
        $this->assertGuest();

        // 3. Post correct code
        $responseVerifySuccess = $this->post('/login/2fa', [
            'code' => $user->two_factor_code
        ]);
        $responseVerifySuccess->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test 2FA authentication flow - TOTP Authenticator.
     */
    public function test_two_factor_totp_flow()
    {
        $secret = \App\Services\TotpService::generateSecret();
        
        $user = User::create([
            'name' => '2FA TOTP User',
            'email' => '2fa_totp@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant',
            'two_factor_method' => 'totp',
            'two_factor_secret' => $secret
        ]);

        // 1. Post valid credentials
        $response = $this->post('/login', [
            'email' => '2fa_totp@test.com',
            'password' => 'password'
        ]);

        $response->assertRedirect(route('auth.2fa'));
        $this->assertGuest();

        // 2. Generate correct current code
        $validCode = \App\Services\TotpService::getCode($secret);

        // 3. Post correct code
        $responseVerify = $this->post('/login/2fa', [
            'code' => $validCode
        ]);
        $responseVerify->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test notification emails are sent automatically based on user preferences.
     */
    public function test_email_notifications_on_invoice_events()
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Enable global SMTP settings
        \App\Models\Setting::set('mail_host', 'localhost');
        \App\Models\Setting::set('mail_port', '587');
        \App\Models\Setting::set('mail_from_address', 'noreply@test.com');
        \App\Models\Setting::set('mail_from_name', 'OmniPay');

        // Create merchant with invoice_created disabled, invoice_paid enabled
        $merchant = User::create([
            'name' => 'Notify Merchant',
            'email' => 'notify_merchant@test.com',
            'password' => bcrypt('password'),
            'role' => 'merchant',
            'notify_invoice_created' => false,
            'notify_invoice_paid' => true,
            'notify_invoice_expired' => true,
        ]);

        $store = Store::create([
            'user_id' => $merchant->id,
            'name' => 'Notify Store',
            'domain' => 'localhost',
            'api_key' => 'store_key_notify',
            'is_active' => true,
        ]);

        // 1. Create invoice (should NOT send mail as notify_invoice_created is false)
        $invoice = Invoice::create([
            'store_id' => $store->id,
            'invoice_id' => 'INV-NOT-1',
            'customer_name' => 'Notify Customer',
            'customer_email' => 'customer@test.com',
            'amount' => 10.00,
            'expected_amount' => 10.0001,
            'currency' => 'USDT',
            'status' => 'pending',
            'payment_link' => 'http://localhost/checkout/token_not',
            'expires_at' => now()->addMinutes(30),
        ]);

        \Illuminate\Support\Facades\Mail::assertNothingSent();

        // 2. Mark as paid (should send mail as notify_invoice_paid is true)
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\DynamicNotificationMail::class, function ($mail) use ($merchant) {
            return $mail->hasTo($merchant->email);
        });
    }
}


