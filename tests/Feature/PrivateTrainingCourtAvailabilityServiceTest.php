<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSection;
use App\Models\PrivateTrainingBooking;
use App\Services\PrivateTrainingCourtAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('returns only sections that are free for the requested private training time', function () {
    Schema::create('courts', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('court_status')->default('open');
        $table->timestamp('closed_from')->nullable();
        $table->timestamp('closed_until')->nullable();
        $table->integer('slot_interval_minutes')->default(30);
        $table->integer('min_booking_minutes')->default(60);
        $table->timestamps();
    });

    Schema::create('court_sections', function ($table) {
        $table->id();
        $table->foreignId('court_id')->constrained()->cascadeOnDelete();
        $table->string('code');
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('court_section_blocks', function ($table) {
        $table->id();
        $table->foreignId('court_section_id')->constrained()->cascadeOnDelete();
        $table->foreignId('blocks_section_id')->constrained('court_sections')->cascadeOnDelete();
        $table->timestamps();
    });

    Schema::create('court_closures', function ($table) {
        $table->id();
        $table->foreignId('court_id')->constrained()->cascadeOnDelete();
        $table->foreignId('court_section_id')->nullable()->constrained()->nullOnDelete();
        $table->date('date');
        $table->time('start_time');
        $table->time('end_time');
        $table->enum('type', ['closed', 'maintenance'])->default('closed');
        $table->timestamps();
    });

    Schema::create('bookings', function ($table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('court_section_id')->nullable()->constrained()->nullOnDelete();
        $table->date('booking_date');
        $table->time('start_time');
        $table->time('end_time');
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    Schema::create('private_training_bookings', function ($table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('coach_id')->nullable();
        $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('court_section_id')->nullable()->constrained()->nullOnDelete();
        $table->date('date');
        $table->time('start_time');
        $table->time('end_time');
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    $court = Court::create(['name' => 'Court 1', 'court_status' => 'open']);
    $busySection = CourtSection::create(['court_id' => $court->id, 'code' => 'full', 'name' => 'เต็มสนาม', 'is_active' => true]);
    $freeSection = CourtSection::create(['court_id' => $court->id, 'code' => 'a', 'name' => 'ครึ่ง A', 'is_active' => true]);

    Booking::create([
        'court_id' => $court->id,
        'court_section_id' => $busySection->id,
        'booking_date' => '2026-08-10',
        'start_time' => '10:00:00',
        'end_time' => '12:00:00',
        'status' => 'approved',
    ]);

    PrivateTrainingBooking::create([
        'court_id' => $court->id,
        'court_section_id' => $busySection->id,
        'date' => '2026-08-10',
        'start_time' => '10:00:00',
        'end_time' => '12:00:00',
        'status' => 'confirmed',
    ]);

    $booking = new PrivateTrainingBooking([
        'date' => '2026-08-10',
        'start_time' => '10:30:00',
        'end_time' => '11:30:00',
        'status' => 'awaiting_court',
    ]);

    $service = app(PrivateTrainingCourtAvailabilityService::class);
    $sections = $service->getAvailableSections($booking);

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['id'])->toBe($freeSection->id);
});
