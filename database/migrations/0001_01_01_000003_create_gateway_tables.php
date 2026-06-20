<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Stores Table (for Multi-Store Support)
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('api_key', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Payment Methods Table (for Modular Plugin Entries)
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // bkash, nagad, upay, binance, bybit, web3
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Store Payment Configs Table (Store-specific gateway settings)
        Schema::create('store_payment_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable(); // holds encrypted credentials, wallet addresses, phone numbers, conversion rates
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'payment_method_id']);
        });

        // 4. Invoices Table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_id', 32)->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->decimal('amount', 18, 8);
            $table->decimal('expected_amount', 18, 8)->unique(); // unique identifier (dust amount) for transaction tracing
            $table->string('currency', 10)->default('USDT');
            $table->string('status', 20)->default('pending'); // pending, paid, expired, cancelled
            $table->text('payment_link');
            $table->string('callback_url')->nullable();
            $table->string('cancel_url')->nullable();
            $table->json('meta_data')->nullable(); // holds balance baseline, txHash, partial payment logs
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // 5. Synced Transactions (SMS verification pool)
        Schema::create('synced_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // merchant owner who owns the syncing device
            $table->string('sender'); // e.g. bKash, NAGAD, upay
            $table->decimal('amount', 18, 4);
            $table->string('trxid', 64)->unique();
            $table->text('raw_message');
            $table->boolean('is_used')->default(false);
            $table->bigInteger('timestamp');
            $table->timestamps();
        });

        // 6. QR Checksums (anti-hijack verification)
        Schema::create('qr_checksums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('checksum');
            $table->text('qr_data');
            $table->string('qr_data_hash', 64)->unique();
            $table->timestamps();
        });

        // 7. QR Registry
        Schema::create('qr_registry', function (Blueprint $table) {
            $table->id();
            $table->string('qr_data_hash', 64)->unique();
            $table->foreignId('owner_user_id')->constrained('users')->onDelete('cascade');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        // 8. QR Blacklist
        Schema::create('qr_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('qr_data_hash', 64)->unique();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_blacklist');
        Schema::dropIfExists('qr_registry');
        Schema::dropIfExists('qr_checksums');
        Schema::dropIfExists('synced_transactions');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('store_payment_configs');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('stores');
    }
};
