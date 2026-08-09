<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSection;
use App\Models\Notification;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrivateTrainingWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_private_notification_marks_itself_read_and_opens_private_management(): void
    {
        $admin = $this->user('admin');
        $notification = Notification::create([
            'user_id' => $admin->id,
            'title' => 'คำขอจองเทรนเนอร์ส่วนตัวใหม่',
            'message' => 'มีคำขอใหม่',
            'action_url' => route('admin.private-training.index', ['status' => 'pending']),
        ]);

        $this->actingAs($admin)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('admin.private-training.index', ['status' => 'pending']));

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_available_assistants_excludes_staff_with_a_busy_schedule(): void
    {
        $customer = $this->user('user', 'customer');
        $busyAssistant = $this->user('staff', 'court_assistant', 'Busy Assistant');
        $freeAssistant = $this->user('staff', 'court_assistant', 'Free Assistant');
        $date = now()->addDays(2)->toDateString();

        Availability::create([
            'user_id' => $busyAssistant->id,
            'date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'booked',
        ]);

        $this->actingAs($customer)
            ->getJson(route('private-training.available-assistants', [
                'date' => $date,
                'start_time' => '14:30',
                'end_time' => '15:30',
            ]))
            ->assertOk()
            ->assertJsonMissing(['id' => $busyAssistant->id])
            ->assertJsonFragment(['id' => $freeAssistant->id, 'name' => 'Free Assistant']);
    }

    public function test_available_assistants_excludes_staff_already_assigned_to_private_training(): void
    {
        $customer = $this->user('user', 'customer');
        $coach = $this->user('staff', 'coach');
        $busyAssistant = $this->user('staff', 'court_assistant', 'Assigned Assistant');
        $freeAssistant = $this->user('staff', 'court_assistant', 'Available Assistant');
        $date = now()->addDays(2)->toDateString();

        PrivateTrainingBooking::create([
            'user_id' => $customer->id,
            'coach_id' => $coach->id,
            'assistant_requested' => true,
            'court_assistant_id' => $busyAssistant->id,
            'date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->getJson(route('private-training.available-assistants', [
                'date' => $date,
                'start_time' => '15:00',
                'end_time' => '16:00',
            ]))
            ->assertOk()
            ->assertJsonMissing(['id' => $busyAssistant->id])
            ->assertJsonFragment(['id' => $freeAssistant->id]);
    }

    public function test_available_courts_excludes_a_section_used_by_a_normal_booking(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('user', 'customer');
        $coach = $this->user('staff', 'coach');
        $date = now()->addDays(3)->toDateString();
        $courtA = Court::create(['name' => 'Court A', 'court_status' => 'open']);
        $courtB = Court::create(['name' => 'Court B', 'court_status' => 'open']);
        $courtC = Court::create(['name' => 'Court C', 'court_status' => 'open']);
        $sectionA = CourtSection::create(['court_id' => $courtA->id, 'code' => 'full', 'name' => 'เต็มสนาม', 'is_active' => true]);
        $sectionB = CourtSection::create(['court_id' => $courtB->id, 'code' => 'full', 'name' => 'เต็มสนาม', 'is_active' => true]);
        $sectionC = CourtSection::create(['court_id' => $courtC->id, 'code' => 'full', 'name' => 'เต็มสนาม', 'is_active' => true]);

        Booking::create([
            'user_id' => $customer->id,
            'court_id' => $courtA->id,
            'court_section_id' => $sectionA->id,
            'booking_date' => $date,
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'status' => 'approved',
        ]);
        Booking::create([
            'user_id' => $customer->id,
            'court_id' => $courtC->id,
            'court_section_id' => $sectionC->id,
            'booking_date' => $date,
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'status' => 'pending_payment',
            'locked_until' => now()->subMinute(),
        ]);
        $private = PrivateTrainingBooking::create([
            'user_id' => $customer->id,
            'coach_id' => $coach->id,
            'date' => $date,
            'start_time' => '15:30:00',
            'end_time' => '16:30:00',
            'status' => 'awaiting_court',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.private-training.available-courts', $private))
            ->assertOk()
            ->assertJsonMissing(['id' => $sectionA->id])
            ->assertJsonFragment(['id' => $sectionB->id, 'court_name' => 'Court B'])
            ->assertJsonFragment(['id' => $sectionC->id, 'court_name' => 'Court C']);
    }

    public function test_assigning_a_court_to_a_package_booking_does_not_calculate_court_price_or_deduct_credit(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('user', 'customer');
        $customer->forceFill(['credit_balance' => 250000])->save();
        $coach = $this->user('staff', 'coach');
        $package = Package::create([
            'name' => 'Private 5 ครั้ง',
            'type' => 'private',
            'price' => 5000,
            'num_of_use' => 5,
            'is_active' => true,
        ]);
        $purchase = PackagePurchase::create([
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'price' => 500000,
            'status' => 'approved',
            'payment_status' => 'paid',
            'remaining_use' => 4,
        ]);
        $court = Court::create(['name' => 'Court A', 'court_status' => 'open']);
        $section = CourtSection::create([
            'court_id' => $court->id,
            'code' => 'full',
            'name' => 'เต็มสนาม',
            'is_active' => true,
        ]);
        $booking = PrivateTrainingBooking::create([
            'user_id' => $customer->id,
            'coach_id' => $coach->id,
            'package_purchase_id' => $purchase->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '22:00:00',
            'status' => 'awaiting_court',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.private-training.assign-court', $booking), [
                'court_section_id' => $section->id,
            ])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid_by_package', $booking->payment_status);
        $this->assertSame(0, $booking->price);
        $this->assertSame(250000, $customer->fresh()->credit_balance);
        $this->assertSame($section->id, $booking->court_section_id);
        $this->assertStringContainsString(
            'ไม่มีการหักเครดิตเพิ่ม',
            Notification::where('user_id', $customer->id)->latest()->value('message'),
        );
    }

    public function test_assigning_a_court_never_deducts_credit_even_for_a_legacy_booking_without_a_package(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('user', 'customer');
        $customer->forceFill(['credit_balance' => 125000])->save();
        $coach = $this->user('staff', 'coach');
        $court = Court::create(['name' => 'Court Legacy', 'court_status' => 'open']);
        $section = CourtSection::create([
            'court_id' => $court->id,
            'code' => 'full',
            'name' => 'เต็มสนาม',
            'is_active' => true,
        ]);
        $booking = PrivateTrainingBooking::create([
            'user_id' => $customer->id,
            'coach_id' => $coach->id,
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '20:00:00',
            'end_time' => '22:00:00',
            'status' => 'awaiting_court',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.private-training.assign-court', $booking), [
                'court_section_id' => $section->id,
            ])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('unpaid', $booking->payment_status);
        $this->assertSame(0, $booking->price);
        $this->assertSame(125000, $customer->fresh()->credit_balance);
        $this->assertSame($section->id, $booking->court_section_id);
    }

    public function test_schedule_booking_details_are_available_to_admin_coach_and_court_assistant(): void
    {
        $admin = $this->user('superadmin');
        $customer = $this->user('user', 'customer', 'Customer Detail');
        $customer->update(['phone' => '0812345678']);
        $coach = $this->user('staff', 'coach', 'Coach Detail');
        $assistant = $this->user('staff', 'court_assistant', 'Assistant Detail');
        $court = Court::create(['name' => 'Court Detail', 'court_status' => 'open']);
        $section = CourtSection::create([
            'court_id' => $court->id,
            'code' => 'full',
            'name' => 'เต็มสนาม',
            'is_active' => true,
        ]);
        $date = now()->addDays(4)->toDateString();
        $booking = PrivateTrainingBooking::create([
            'user_id' => $customer->id,
            'coach_id' => $coach->id,
            'assistant_requested' => true,
            'court_assistant_id' => $assistant->id,
            'court_id' => $court->id,
            'court_section_id' => $section->id,
            'date' => $date,
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'status' => 'confirmed',
            'note' => 'ฝึกยิงสามแต้ม',
        ]);
        $otherCustomer = $this->user('user', 'customer', 'Other Schedule Customer');
        PrivateTrainingBooking::create([
            'user_id' => $otherCustomer->id,
            'coach_id' => $coach->id,
            'date' => $date,
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'status' => 'pending',
            'note' => 'ข้อมูลส่วนตัวของลูกค้าคนอื่น',
        ]);
        $range = [
            'start' => now()->addDays(3)->startOfDay()->toIso8601String(),
            'end' => now()->addDays(5)->startOfDay()->toIso8601String(),
        ];

        $this->actingAs($admin)
            ->getJson(route('admin.private-schedule.events', ['staff_id' => 'all'] + $range))
            ->assertOk()
            ->assertJsonFragment([
                'bookingId' => $booking->id,
                'customerName' => 'Customer Detail',
                'customerPhone' => '0812345678',
                'coachName' => 'Coach Detail',
                'assistantName' => 'Assistant Detail',
                'court' => 'Court Detail — เต็มสนาม',
                'note' => 'ฝึกยิงสามแต้ม',
            ]);

        foreach ([[$coach, 'โค้ชผู้สอน'], [$assistant, 'ผู้ช่วยสนาม']] as [$staff, $roleLabel]) {
            $this->actingAs($staff)
                ->getJson(route('private-training.my-schedule.events', $range))
                ->assertOk()
                ->assertJsonFragment([
                    'bookingId' => $booking->id,
                    'roleLabel' => $roleLabel,
                    'customerName' => 'Customer Detail',
                    'customerPhone' => '0812345678',
                    'coachName' => 'Coach Detail',
                    'assistantName' => 'Assistant Detail',
                    'court' => 'Court Detail — เต็มสนาม',
                    'note' => 'ฝึกยิงสามแต้ม',
                ]);
        }

        $this->actingAs($customer)
            ->getJson(route('private-training.schedule', ['coach' => $coach->id] + $range))
            ->assertOk()
            ->assertJsonFragment([
                'bookingId' => $booking->id,
                'isMine' => true,
                'customerName' => 'Customer Detail',
                'customerPhone' => '0812345678',
                'coachName' => 'Coach Detail',
                'assistantName' => 'Assistant Detail',
                'court' => 'Court Detail — เต็มสนาม',
                'note' => 'ฝึกยิงสามแต้ม',
            ])
            ->assertJsonFragment([
                'title' => 'โค้ชไม่ว่าง',
                'backgroundColor' => '#64748b',
                'unavailableReason' => 'ช่วงเวลานี้มีรายการ Private Training แล้ว',
            ])
            ->assertJsonMissing(['customerName' => 'Other Schedule Customer'])
            ->assertJsonMissing(['note' => 'ข้อมูลส่วนตัวของลูกค้าคนอื่น']);
    }

    private function user(string $role, string $membershipType = 'customer', ?string $name = null): User
    {
        return User::forceCreate([
            'name' => $name ?? ucfirst($role).' User',
            'email' => uniqid($role, true).'@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'membership_type' => $membershipType,
            'is_verified' => true,
            'credit_balance' => 0,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('membership_type')->default('customer');
            $table->boolean('is_verified')->default(true);
            $table->unsignedBigInteger('credit_balance')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('message');
            $table->string('action_url', 2048)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('booked');
            $table->string('detail')->nullable();
            $table->unsignedBigInteger('court_id')->nullable();
            $table->timestamps();
        });
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('court_status')->default('open');
            $table->dateTime('closed_from')->nullable();
            $table->dateTime('closed_until')->nullable();
            $table->timestamps();
        });
        Schema::create('court_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('court_id');
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('court_section_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('court_section_id');
            $table->unsignedBigInteger('blocks_section_id');
        });
        Schema::create('court_closures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('court_id');
            $table->unsignedBigInteger('court_section_id')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type')->default('maintenance');
            $table->timestamps();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('court_id');
            $table->unsignedBigInteger('court_section_id');
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status');
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('num_of_use');
            $table->integer('day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();
        });
        Schema::create('package_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('price');
            $table->string('status');
            $table->string('booking_source')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->unsignedInteger('remaining_use')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
        Schema::create('private_training_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('coach_id');
            $table->boolean('assistant_requested')->default(false);
            $table->unsignedBigInteger('court_assistant_id')->nullable();
            $table->unsignedBigInteger('court_id')->nullable();
            $table->unsignedBigInteger('court_section_id')->nullable();
            $table->unsignedBigInteger('court_assigned_by')->nullable();
            $table->timestamp('court_assigned_at')->nullable();
            $table->unsignedBigInteger('package_purchase_id')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->json('price_breakdown')->nullable();
            $table->unsignedBigInteger('pricing_rule_id')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->timestamps();
        });
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('color')->default('#2563eb');
            $table->string('recurrence')->default('none');
            $table->json('recurrence_days')->nullable();
            $table->date('recurrence_until')->nullable();
            $table->unsignedBigInteger('coach_id')->nullable();
            $table->string('event_type')->default('general');
            $table->unsignedBigInteger('court_section_id')->nullable();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_name');
            $table->timestamps();
        });
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('court_section_id')->nullable();
            $table->string('day_type');
            $table->json('weekdays')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
        Schema::create('course_calendar_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_schedule_id');
            $table->date('occurrence_date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedBigInteger('court_section_id')->nullable();
            $table->timestamps();
        });
    }
}
