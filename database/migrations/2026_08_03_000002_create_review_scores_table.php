<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            // 6 หมวดตาม requirement ข้อ 1.4 — court/cafe/restroom/service/cleanliness ใช้กับรีวิวสถานที่
            // ส่วน coach ใช้กับรีวิวโค้ช Private เท่านั้น
            $table->enum('category', ['court', 'cafe', 'restroom', 'service', 'cleanliness', 'coach'])->default('court');
            // 1-5 ดาว
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            // 1 รีวิว = 1 คะแนนต่อหมวด
            $table->unique(['review_id', 'category']);

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_scores');
    }
};
