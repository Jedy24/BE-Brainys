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
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_transaction');
            $table->string('pay_id')->unique();
            $table->string('unique_code')->unique();
            $table->string('service');
            $table->string('service_name');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2)->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->string('type_fee')->nullable();
            $table->string('status');
            $table->timestamp('expired')->nullable();
            $table->string('qrcode_url')->nullable();
            $table->string('virtual_account')->nullable();
            $table->string('checkout_url')->nullable();
            $table->string('checkout_url_v2')->nullable();
            $table->string('checkout_url_v3')->nullable();
            $table->string('checkout_url_beta')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};
