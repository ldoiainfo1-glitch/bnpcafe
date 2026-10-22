<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('provider', 24);
            $table->string('provider_order_id', 100)->nullable()->unique();
            $table->string('provider_payment_id', 100)->nullable()->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 20)->default('creating')->index();
            $table->string('failure_code', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['membership_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('membership_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->index(['user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
    }
};
