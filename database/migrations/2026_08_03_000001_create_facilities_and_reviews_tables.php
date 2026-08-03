<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facilities')) {
            Schema::create('facilities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('image_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('overall_rating')->default(5);
                $table->text('comment')->nullable();
                $table->enum('status', ['pending', 'published', 'hidden'])->default('pending');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'published_at']);
            });
        } else {
            Schema::table('reviews', function (Blueprint $table) {
                if (! Schema::hasColumn('reviews', 'overall_rating')) {
                    // ค่าเริ่มต้นช่วยให้รีวิวรูปแบบเก่าที่ยังผูกกับการจองทำงานต่อได้
                    $table->unsignedTinyInteger('overall_rating')->default(5)->after('user_id');
                }
                if (! Schema::hasColumn('reviews', 'status')) {
                    $table->enum('status', ['pending', 'published', 'hidden'])->default('pending')->after('comment');
                }
                if (! Schema::hasColumn('reviews', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status');
                }
            });
        }

        if (! Schema::hasTable('review_ratings')) {
            Schema::create('review_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained()->cascadeOnDelete();
                $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->timestamps();

                $table->unique(['review_id', 'facility_id']);
            });
        }

        if (! Schema::hasTable('review_images')) {
            Schema::create('review_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained()->cascadeOnDelete();
                $table->string('image_path');
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();
        $facilities = [
            [
                'name' => 'คาเฟ่ & เครื่องดื่ม',
                'slug' => 'cafe',
                'description' => 'กาแฟ เครื่องดื่มเย็น ของว่าง และพื้นที่นั่งพักระหว่างเกม',
                'image_path' => 'images/facilities/cafe.webp',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Basketball Shop',
                'slug' => 'basketball-shop',
                'description' => 'รองเท้า ลูกบาส เสื้อผ้า และอุปกรณ์สำหรับนักบาส',
                'image_path' => 'images/facilities/basketball-shop.webp',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'ห้องน้ำ & ห้องอาบน้ำ',
                'slug' => 'restroom',
                'description' => 'ห้องน้ำสะอาด พร้อมห้องอาบน้ำสำหรับผู้ใช้สนามและนักกีฬา',
                'image_path' => 'images/facilities/restroom.webp',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'พื้นที่นั่งพัก',
                'slug' => 'lounge',
                'description' => 'นั่งทำงาน จิบกาแฟ ชมเกม หรือรอเพื่อนและครอบครัว',
                'image_path' => 'images/facilities/lounge.webp',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($facilities as $facility) {
            DB::table('facilities')->updateOrInsert(
                ['slug' => $facility['slug']],
                $facility,
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('review_images');
        Schema::dropIfExists('review_ratings');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('facilities');
    }
};
