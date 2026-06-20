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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_invoice_created')->default(true);
            $table->boolean('notify_invoice_paid')->default(true);
            $table->boolean('notify_invoice_expired')->default(true);
            $table->boolean('notify_login')->default(true);
            $table->string('two_factor_method')->default('none'); // 'none', 'email', 'totp'
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_code')->nullable();
            $table->timestamp('two_factor_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_invoice_created',
                'notify_invoice_paid',
                'notify_invoice_expired',
                'notify_login',
                'two_factor_method',
                'two_factor_secret',
                'two_factor_code',
                'two_factor_expires_at'
            ]);
        });
    }
};
