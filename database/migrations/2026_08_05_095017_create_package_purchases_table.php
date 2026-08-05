<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('status')->default('pending_payment'); // pending_payment, paid, expired, cancelled
            $table->string('booking_source')->nullable();          // เช่น web, admin, line
            $table->string('payment_method')->nullable();          // เช่น cash, promptpay, credit_card
            $table->string('payment_status')->nullable();          // เช่น unpaid, paid, refunded
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            // ไม่ใส่ unique(['user_id','package_id']) เพราะอนุญาตให้ซื้อซ้ำได้
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_purchases');
    }
};
