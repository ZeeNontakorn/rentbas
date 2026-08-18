<?php

use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtController as AdminCourtController;
use App\Http\Controllers\Admin\CreditController;
use App\Http\Controllers\Admin\CreditTopupController as AdminCreditTopupController;
use App\Http\Controllers\Admin\CreditTopupPackageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ManageCourseController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\PrivateScheduleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebsiteReviewController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CoachScheduleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CreditTopupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageCheckoutController;
use App\Http\Controllers\PrivateTrainingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Models\OtpToken;
use App\Models\Court;
use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GroupSessionController;


// 1. Landing Page — ใครๆ ก็เข้าได้
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public schedule API (used by home calendar to show real booking status)
Route::get('/schedule', [HomeController::class, 'schedule'])->name('schedule');

Route::get('/media/{path}', function (string $path) {
    $base = realpath(storage_path('app/public'));
    $full = realpath(storage_path('app/public/'.$path));

    if (! $full || ! str_starts_with($full, $base)) {
        abort(404);
    }

    return response()->file($full, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*')->name('storage.local');

// Per-day availability for the calendar dots (whole month)
Route::get('/month-availability', [HomeController::class, 'monthAvailability'])->name('month.availability');

// 2. Guest Routes — เฉพาะคนที่ยังไม่ Login (หน้า Login/Register)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 3. OTP Verification Routes — หน้าสำหรับยืนยันรหัส
Route::controller(AuthController::class)->group(function () {
    Route::get('/verify-otp', 'showVerifyOtp')->name('verify-otp');
    Route::post('/verify-otp', 'verifyOtp')->name('verify-otp.post');
    Route::post('/resend-otp', 'resendOtp')->name('otp.resend');
});

// 4. Authenticated User Routes — ต้อง Login (auth) และยืนยันรหัส (verified_otp) แล้วเท่านั้น
Route::middleware(['auth', 'verified_otp'])->group(function () {

    Route::get('/reviews/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('reviews.store');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/request-otp-email', [ProfileController::class, 'requestOtpForEmailChange'])->name('profile.request-otp-email');

    // Booking System
    Route::prefix('booking')->name('booking.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/courts', [BookingController::class, 'courts'])->name('courts');
        Route::get('/sections', [BookingController::class, 'sections'])->name('sections');
        Route::get('/calendar', [BookingController::class, 'calendar'])->name('calendar');
        Route::get('/court/{court}', [BookingController::class, 'show'])->name('show');
        Route::post('/', [BookingController::class, 'store'])->name('store');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
    });

    // History
    Route::get('/history', [BookingController::class, 'history'])->name('history');

    // Private Training (จองเทรนเนอร์ส่วนตัว) — ฝั่ง user
    Route::prefix('private-training')->name('private-training.')->group(function () {
        Route::get('/', [PrivateTrainingController::class, 'index'])->name('index');
        Route::get('/my-schedule', [PrivateTrainingController::class, 'mySchedule'])->name('my-schedule');
        Route::get('/my-schedule/events', [CoachScheduleController::class, 'events'])->name('my-schedule.events');
        Route::post('/my-schedule/events', [CoachScheduleController::class, 'store'])->name('my-schedule.store');
        Route::put('/my-schedule/events/{availability}', [CoachScheduleController::class, 'update'])->name('my-schedule.update');
        Route::delete('/my-schedule/events/{availability}', [CoachScheduleController::class, 'destroy'])->name('my-schedule.destroy');
        Route::post('/my-schedule/calendar-events', [CoachScheduleController::class, 'storeEvent'])->name('my-schedule.calendar-events.store');
        Route::put('/my-schedule/calendar-events/{calendarEvent}', [CoachScheduleController::class, 'updateEvent'])->name('my-schedule.calendar-events.update');
        Route::delete('/my-schedule/calendar-events/{calendarEvent}', [CoachScheduleController::class, 'destroyEvent'])->name('my-schedule.calendar-events.destroy');
        Route::get('/available-assistants', [PrivateTrainingController::class, 'availableAssistants'])->name('available-assistants');
        Route::get('/{coach}/schedule', [PrivateTrainingController::class, 'scheduleEvents'])->name('schedule');
        Route::get('/{coach}', [PrivateTrainingController::class, 'show'])->name('show');
        Route::post('/', [PrivateTrainingController::class, 'store'])->name('store');
        Route::post('/{privateTrainingBooking}/cancel', [PrivateTrainingController::class, 'cancel'])->name('cancel');
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::post('/quote', [CheckoutController::class, 'quote'])->name('quote');
        Route::post('/reserve', [CheckoutController::class, 'reserve'])->name('reserve');
        Route::get('/{booking}', [CheckoutController::class, 'show'])->name('show');
        Route::post('/{booking}/pay/credit', [CheckoutController::class, 'payWithCredit'])->name('pay.credit');
        Route::post('/{booking}/pay/promptpay', [CheckoutController::class, 'payWithPromptpay'])->name('pay.promptpay');
    });
    Route::prefix('package-checkout')->name('package-checkout.')->group(function () {
        Route::post('/{package}', [PackageCheckoutController::class, 'purchase'])->name('purchase');
        Route::get('/purchase/{purchase}', [PackageCheckoutController::class, 'show'])->name('show');
        Route::post('/purchase/{purchase}/pay/credit', [PackageCheckoutController::class, 'payWithCredit'])->name('pay.credit');
    });
    // เติมเครดิต (ฝั่งผู้ใช้) — เลือกแพ็กเกจ/กรอกจำนวนเงินเอง -> QR mock + แจ้งช่องทางชำระเงิน -> ส่งคำขอรออนุมัติ
    Route::prefix('credits/topup')->name('credits.topup.')->group(function () {
        Route::get('/', [CreditTopupController::class, 'index'])->name('index');
        Route::get('/checkout', [CreditTopupController::class, 'checkout'])->name('checkout');
        Route::post('/', [CreditTopupController::class, 'store'])->name('store');
    });
});

Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

// Playwright reads OTPs through the same browser session. This route is never
// registered in development or production environments.
if (app()->environment('e2e') || env('E2E_TESTING', false)) {
    Route::get('/__e2e/otp', function () {
        if (session()->has('reset_otp.code')) {
            return response()->json(['otp' => session('reset_otp.code')]);
        }

        $userId = session('pending_otp_user_id');
        $otp = $userId
            ? OtpToken::where('user_id', $userId)->latest()->value('otp_code')
            : null;

        abort_unless($otp, 404);

        return response()->json(['otp' => $otp]);
    });

    Route::post('/__e2e/group-rounds/seed', function () {
        return DB::transaction(function () {
            $prefix = '[E2E GROUP]';

            GroupRound::where('title', 'like', $prefix.'%')->delete();

            $court = Court::updateOrCreate(
                ['name' => 'สนาม E2E กลุ่มบาส'],
                ['court_status' => 'open']
            );
            $user = User::updateOrCreate(
                ['email' => 'group-player@e2e.local'],
                [
                    'name' => 'ชาตรี E2E',
                    'phone' => '0890000001',
                    'password' => '123456',
                    'role' => 'user',
                    'membership_type' => 'customer',
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'credit_balance' => 100000,
                ]
            );

            $today = Carbon::today('Asia/Bangkok');
            $makeRound = function (string $key, array $attributes) use ($prefix, $court) {
                return GroupRound::create(array_merge([
                    'title' => "{$prefix} {$key}",
                    'play_date' => Carbon::today('Asia/Bangkok')->addDays(7),
                    'start_time' => '18:00:00',
                    'end_time' => '20:00:00',
                    'court_id' => $court->id,
                    'max_players' => 6,
                    'credit_cost' => 120,
                    'cancel_deadline' => null,
                    'status' => 'open',
                ], $attributes));
            };

            $rounds = [
                'OPEN' => $makeRound('OPEN', [
                    'play_date' => $today->copy()->addDays(7),
                    'cancel_deadline' => $today->copy()->addDays(6)->setTime(17, 30),
                ]),
                'CLOSED' => $makeRound('CLOSED', ['play_date' => $today->copy()->addDays(8), 'status' => 'closed']),
                'CANCELLED' => $makeRound('CANCELLED', ['play_date' => $today->copy()->addDays(9), 'status' => 'cancelled']),
                'PAST' => $makeRound('PAST', ['play_date' => $today->copy()->subDay()]),
                'NO-DEADLINE' => $makeRound('NO-DEADLINE', ['play_date' => $today->copy()->addDays(10)]),
                'FOUR-OF-SIX' => $makeRound('FOUR-OF-SIX', ['play_date' => $today->copy()->addDays(11)]),
                'FULL' => $makeRound('FULL', ['play_date' => $today->copy()->addDays(12)]),
            ];

            foreach (['FOUR-OF-SIX' => 4, 'FULL' => 6] as $key => $count) {
                foreach (range(1, $count) as $order) {
                    GroupRoundSignup::create([
                        'group_round_id' => $rounds[$key]->id,
                        'user_id' => null,
                        'guest_name' => "ผู้เล่นทดสอบ {$order}",
                        'order_number' => $order,
                        'credit_used' => 120,
                        'status' => 'confirmed',
                        'is_reserve' => false,
                        'signed_up_at' => now()->addSeconds($order),
                    ]);
                }
            }

            return response()->json([
                'user' => ['email' => $user->email, 'password' => '123456'],
                'credit_balance' => $user->credit_balance,
                'rounds' => collect($rounds)->map(fn ($round) => [
                    'id' => $round->id,
                    'title' => $round->title,
                    'play_date' => $round->play_date->format('d/m/Y'),
                    'credit_cost' => $round->credit_cost,
                    'max_players' => $round->max_players,
                ]),
                'deadline' => $rounds['OPEN']->cancel_deadline->format('d/m/Y H:i'),
            ]);
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/group-rounds/state', function () {
        $user = User::where('email', 'group-player@e2e.local')->firstOrFail();

        return response()->json([
            'credit_balance' => $user->credit_balance,
            'signups_count' => GroupRoundSignup::whereHas(
                'round',
                fn ($query) => $query->where('title', 'like', '[E2E GROUP]%')
            )->count(),
        ]);
    });

    Route::post('/__e2e/group-rounds/case', function (\Illuminate\Http\Request $request) {
        return DB::transaction(function () use ($request) {
            $prefix = '[E2E CASE]';
            if (! $request->boolean('preserve')) {
                GroupRound::where('title', 'like', $prefix.'%')->delete();
            }

            $court = Court::updateOrCreate(['name' => 'สนาม E2E กลุ่มบาส'], ['court_status' => 'open']);
            $users = collect([
                ['email' => 'group-case-a@e2e.local', 'name' => 'ผู้เล่น A'],
                ['email' => 'group-case-b@e2e.local', 'name' => 'ผู้เล่น B'],
            ])->map(function ($account) use ($request) {
                $user = User::updateOrCreate(['email' => $account['email']], [
                    'name' => $account['name'], 'phone' => '0890000002', 'password' => '123456',
                    'role' => 'user', 'membership_type' => 'customer', 'is_verified' => true,
                    'email_verified_at' => now(),
                ]);
                $user->forceFill(['credit_balance' => (int) $request->input('credit_balance', 1000) * 100])->save();
                $user->notifications()->delete();
                return $user;
            });

            $deadline = match ($request->input('deadline', 'future')) {
                'past' => now()->subHour(),
                'none' => null,
                default => now()->addDay(),
            };
            $round = GroupRound::create([
                'title' => $prefix.' '.($request->input('title', 'ROUND')).' '.now()->format('Hisv'),
                'play_date' => today()->addDays(7), 'start_time' => '18:00:00', 'end_time' => '20:00:00',
                'court_id' => $court->id, 'max_players' => (int) $request->input('max_players', 6),
                'credit_cost' => (int) $request->input('credit_cost', 100),
                'cancel_deadline' => $deadline, 'status' => $request->input('status', 'open'),
            ]);

            $order = 1;
            foreach (range(1, (int) $request->input('other_main', 0)) as $i) {
                if ((int) $request->input('other_main', 0) === 0) break;
                GroupRoundSignup::create(['group_round_id' => $round->id, 'guest_name' => "บุคคลอื่น {$i}",
                    'order_number' => $order++, 'credit_used' => $round->credit_cost, 'status' => 'confirmed',
                    'is_reserve' => false, 'signed_up_at' => now()->addSeconds($order)]);
            }
            foreach ((array) $request->input('booked_names', []) as $name) {
                GroupRoundSignup::create(['group_round_id' => $round->id, 'guest_name' => $name,
                    'order_number' => $order++, 'credit_used' => $round->credit_cost, 'status' => 'confirmed',
                    'is_reserve' => ($order - 1) > $round->max_players, 'signed_up_at' => now()->addSeconds($order),
                    'booked_by' => $users[0]->id]);
            }
            foreach ((array) $request->input('reserve_names', []) as $name) {
                GroupRoundSignup::create(['group_round_id' => $round->id, 'guest_name' => $name,
                    'order_number' => $order++, 'credit_used' => $round->credit_cost, 'status' => 'confirmed',
                    'is_reserve' => true, 'signed_up_at' => now()->addSeconds($order), 'booked_by' => $users[1]->id]);
            }

            return response()->json([
                'round' => [
                    'id' => $round->id, 'title' => $round->title,
                    'credit_cost' => $round->credit_cost, 'max_players' => $round->max_players,
                ],
                'users' => $users->map(fn ($u) => [
                    'id' => $u->id, 'email' => $u->email, 'password' => '123456',
                    'credit_balance' => $u->credit_balance / 100,
                ])->values(),
            ]);
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::post('/__e2e/group-rounds/{round}/mutate', function (GroupRound $round, \Illuminate\Http\Request $request) {
        if ($request->has('status')) $round->update(['status' => $request->input('status')]);
        if ($request->has('deadline')) $round->update(['cancel_deadline' => $request->input('deadline') === 'past' ? now()->subHour() : now()->addDay()]);
        if ($request->has('credit_balance')) User::where('email', 'group-case-a@e2e.local')->update(['credit_balance' => (int) $request->input('credit_balance') * 100]);
        if ($request->boolean('cancel_round')) app(\App\Http\Controllers\Admin\GroupSessionController::class)->cancelRound($round);
        return response()->json(['ok' => true]);
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/group-rounds/{round}/case-state', function (GroupRound $round) {
        $users = User::whereIn('email', ['group-case-a@e2e.local', 'group-case-b@e2e.local'])->get()->keyBy('email');
        return response()->json([
            'round_status' => $round->fresh()->status,
            'credits' => $users->map(fn ($u) => $u->credit_balance / 100),
            'notifications' => $users->map(fn ($u) => $u->notifications()->count()),
            'signups' => $round->signups()->get()->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->displayName(), 'order' => $s->order_number,
                'status' => $s->status, 'reserve' => $s->is_reserve, 'booked_by' => $s->booked_by,
            ]),
        ]);
    });
}

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 5. Admin Routes — ต้องเป็น Admin เท่านั้น
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // จัดการสนาม
    Route::get('/courts', [AdminCourtController::class, 'index'])->name('courts');
    Route::post('/courts', [AdminCourtController::class, 'store'])->name('court.create');
    Route::put('/courts/{court}', [AdminCourtController::class, 'update'])->name('court.update');
    Route::post('/courts/{court}/status', [AdminCourtController::class, 'updateStatus'])->name('courts.status');
    Route::post('/courts/slot', [AdminCourtController::class, 'updateSlot'])->name('courts.slot');
    Route::delete('/courts/{court}', [AdminCourtController::class, 'destroy'])->name('destroy');

    Route::post('/courts/{court}/sections/split', [AdminCourtController::class, 'splitSection'])->name('courts.sections.split');
    Route::post('/courts/{court}/sections/merge', [AdminCourtController::class, 'mergeSections'])->name('courts.sections.merge');
    Route::put('/court-sections/{courtSection}', [AdminCourtController::class, 'updateSection'])->name('court-sections.update');
    Route::post('/courts/{court}/slot-settings', [AdminCourtController::class, 'updateSlotSettings'])->name('courts.slot-settings');

    // Manage Courses
    Route::get('/courses', [ManageCourseController::class, 'index'])->name('courses');
    Route::get('/courses/create', [ManageCourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [ManageCourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}/edit', [ManageCourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [ManageCourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [ManageCourseController::class, 'destroy'])->name('courses.destroy');

    Route::patch('/courses/{course}/toggle-status', [ManageCourseController::class, 'toggleStatus'])
        ->name('courses.toggleStatus');

    Route::get('/courses/calendar', [CalendarController::class, 'calendar'])
        ->name('courses.calendar');
    Route::get('/courses/calendar/events', [CalendarController::class, 'events'])->name('courses.calendar.events');
    Route::post('/courses/calendar/events', [CalendarController::class, 'store'])->name('courses.calendar.events.store');
    Route::put('/courses/calendar/events/{calendarEvent}', [CalendarController::class, 'update'])->name('courses.calendar.events.update');
    Route::delete('/courses/calendar/events/{calendarEvent}', [CalendarController::class, 'destroy'])->name('courses.calendar.events.destroy');
    Route::put('/courses/calendar/course-events/{schedule}/{date}', [CalendarController::class, 'updateCourseEvent'])->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('courses.calendar.course-events.update');

    // ระบบจัดการผู้ใช้ (User Management)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/membership-type', [UserController::class, 'updateMembershipType'])->name('users.updateMembershipType');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // จัดการโค้ช และผู้ช่วย
    Route::get('/staffs', [StaffController::class, 'index'])->name('staffs.index');
    Route::post('/staffs', [StaffController::class, 'store'])->name('staffs.store');
    Route::get('/staffs/{staff}', [StaffController::class, 'show'])->name('staffs.show');
    Route::get('/staffs/{staff}/schedule-events', [StaffController::class, 'scheduleEvents'])->name('staffs.schedule-events');
    Route::put('/staffs/{staff}/profile', [StaffController::class, 'updateProfile'])->name('staffs.profile.update');
    Route::post('/staffs/{staff}/availabilities', [StaffController::class, 'storeAvailability'])->name('staffs.availabilities.store');
    Route::patch('/staffs/{staff}/remove-role', [StaffController::class, 'removeRole'])->name('staffs.remove-role');

    // จัดการช่วงเวลาสำหรับ Private Training (ไม่รวมคลาสโรงเรียน)
    Route::get('/private-schedule', [PrivateScheduleController::class, 'index'])->name('private-schedule.index');
    Route::get('/private-schedule/events', [PrivateScheduleController::class, 'events'])->name('private-schedule.events');
    Route::post('/private-schedule/calendar-events', [PrivateScheduleController::class, 'storeEvent'])->name('private-schedule.calendar-events.store');
    Route::put('/private-schedule/calendar-events/{calendarEvent}', [PrivateScheduleController::class, 'updateEvent'])->name('private-schedule.calendar-events.update');
    Route::delete('/private-schedule/calendar-events/{calendarEvent}', [PrivateScheduleController::class, 'destroyEvent'])->name('private-schedule.calendar-events.destroy');
    Route::put('/private-schedule/availabilities/{availability}', [PrivateScheduleController::class, 'updateAvailability'])->name('private-schedule.availabilities.update');
    Route::delete('/private-schedule/availabilities/{availability}', [PrivateScheduleController::class, 'destroyAvailability'])->name('private-schedule.availabilities.destroy');

    // ตั้งค่าเว็บไซต์ (Site Settings)
    Route::get('/edit-text', [SettingController::class, 'edit'])->name('edit.text');
    Route::post('/edit-text', [SettingController::class, 'update'])->name('edit.text.update');
    Route::post('/website/facilities', [WebsiteReviewController::class, 'storeFacility'])->name('website.facilities.store');
    Route::put('/website/facilities/{facility}', [WebsiteReviewController::class, 'updateFacility'])->name('website.facilities.update');
    Route::delete('/website/facilities/{facility}', [WebsiteReviewController::class, 'destroyFacility'])->name('website.facilities.destroy');
    Route::patch('/website/reviews/{review}/status', [WebsiteReviewController::class, 'updateReviewStatus'])->name('website.reviews.status');
    Route::delete('/website/reviews/{review}', [WebsiteReviewController::class, 'destroyReview'])->name('website.reviews.destroy');
    Route::post('/courts/images', [CourtController::class, 'updateImages'])
        ->name('courts.images.update');

    Route::get('/users/{user}/credit', [CreditController::class, 'show'])->name('credits.show');
    Route::post('/users/{user}/credit/topup', [CreditController::class, 'topup'])->name('credits.topup');

    // คำขอเติมเครดิตที่ผู้ใช้ยื่นเอง (แนบสลิป/แจ้งช่องทางชำระเงิน) — แอดมินตรวจสอบและอนุมัติ/ปฏิเสธ
    Route::get('/credit-topups', [AdminCreditTopupController::class, 'index'])->name('credit-topups.index');
    Route::get('/credit-topups/{creditTopupRequest}', [AdminCreditTopupController::class, 'show'])->name('credit-topups.show');
    Route::post('/credit-topups/{creditTopupRequest}/approve', [AdminCreditTopupController::class, 'approve'])->name('credit-topups.approve');
    Route::post('/credit-topups/{creditTopupRequest}/reject', [AdminCreditTopupController::class, 'reject'])->name('credit-topups.reject');

    // แพ็กเกจเติมเครดิต (ราคา/โปรโมชั่นโบนัส) + ลิงก์ LINE สำหรับปุ่ม "เติมผ่าน LINE ไวกว่า"
    Route::get('/credit-topup-packages', [CreditTopupPackageController::class, 'index'])->name('credit-topup-packages.index');
    Route::post('/credit-topup-packages', [CreditTopupPackageController::class, 'store'])->name('credit-topup-packages.store');
    Route::put('/credit-topup-packages/{creditTopupPackage}', [CreditTopupPackageController::class, 'update'])->name('credit-topup-packages.update');
    Route::delete('/credit-topup-packages/{creditTopupPackage}', [CreditTopupPackageController::class, 'destroy'])->name('credit-topup-packages.destroy');
    Route::post('/credit-topup-packages/line-url', [CreditTopupPackageController::class, 'updateLineUrl'])->name('credit-topup-packages.line-url');
    Route::post('/credit-topup-packages/promptpay', [CreditTopupPackageController::class, 'updatePromptPayInfo'])->name('credit-topup-packages.promptpay');
    Route::post('/credit-topup-packages/reorder', [CreditTopupPackageController::class, 'reorder'])->name('credit-topup-packages.reorder');

    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::put('/pricing/rules/bulk-update', [PricingController::class, 'bulkUpdateRules'])->name('pricing.rules.bulkUpdate');
    Route::put('/pricing/rules/{pricingRule}', [PricingController::class, 'updateRule'])->name('pricing.rules.update');
    Route::post('/pricing/packages', [PricingController::class, 'storePackage'])->name('pricing.packages.store');
    Route::put('/pricing/packages/{promotionPackage}', [PricingController::class, 'updatePackage'])->name('pricing.packages.update');
    Route::delete('/pricing/packages/{promotionPackage}', [PricingController::class, 'destroyPackage'])->name('pricing.packages.destroy');

    // จัดการ package
    Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('packages/create', [PackageController::class, 'create'])->name('packages.create');
    Route::post('packages', [PackageController::class, 'store'])->name('packages.store');
    Route::get('packages/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
    Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('packages/{package}', [PackageController::class, 'delete'])->name('packages.delete');
    Route::patch('packages/{package}/toggle-status', [PackageController::class, 'toggleStatus'])->name('packages.toggleStatus');
});

// 6. Password Reset via OTP
Route::controller(AuthController::class)->group(function () {
    Route::get('/forgot-password', 'showForgotPasswordForm')->name('password.request');
    Route::post('/forgot-password', 'sendResetLinkEmail')->name('password.email');

    Route::get('/reset-otp', 'showResetOtpForm')->name('password.otp.form');
    Route::post('/reset-otp', 'verifyResetOtp')->name('password.otp.verify');

    Route::get('/reset-password', 'showResetPasswordForm')->name('password.reset.form');
    Route::post('/reset-password', 'resetPassword')->name('password.reset');
});

// จัดการการจองได้ต้องเป็น admin และ staff ที่เป็น พนักงานประจำ นักศึกษาฝึกงาน พนักงานชั่วคราว
Route::middleware(['auth', 'staff_or_admin'])->prefix('admin')->name('admin.')->group(function () {
    // จัดการคำขอจองเทรนเนอร์ส่วนตัว
    Route::get('/private-training', [PrivateTrainingController::class, 'adminIndex'])->name('private-training.index');
    Route::get('/private-training/{privateTrainingBooking}/available-courts', [PrivateTrainingController::class, 'availableCourts'])->name('private-training.available-courts');
    Route::post('/private-training/{privateTrainingBooking}/approve', [PrivateTrainingController::class, 'approve'])->name('private-training.approve');
    Route::post('/private-training/{privateTrainingBooking}/assign-court', [PrivateTrainingController::class, 'assignCourt'])->name('private-training.assign-court');
    Route::post('/private-training/{privateTrainingBooking}/reject', [PrivateTrainingController::class, 'reject'])->name('private-training.reject');

    // จัดการการจอง
    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
    Route::post('/bookings/bulk-approve', [BookingController::class, 'bulkApprove'])->name('bookings.bulkApprove');
    Route::post('/bookings/bulk-reject', [BookingController::class, 'bulkReject'])->name('bookings.bulkReject');
});

// จัดการ Group Sessions (ระบบ Round/Group) — ต้องเป็น admin เท่านั้น
Route::middleware(['auth', 'admin'])->prefix('admin/group-sessions')->name('admin.group-sessions.')->group(function () {
    Route::get('/', [GroupSessionController::class, 'index'])->name('index');
    Route::get('/history', [GroupSessionController::class, 'history'])->name('history');
    Route::get('/history', [GroupSessionController::class, 'history'])->name('history');
    Route::get('/history/{round}', [GroupSessionController::class, 'showRoundHistory'])->name('history.show');  
    Route::post('/', [GroupSessionController::class, 'storeSession'])->name('store');
    Route::put('/{session}', [GroupSessionController::class, 'updateSession'])->name('update');
    Route::delete('/{session}', [GroupSessionController::class, 'destroySession'])->name('destroy');
    Route::post('/rounds', [GroupSessionController::class, 'openRound'])->name('rounds.open');
    Route::get('/rounds/{round}', [GroupSessionController::class, 'showRound'])->name('rounds.show');
    Route::post('/rounds/{round}/players', [GroupSessionController::class, 'addPlayer'])->name('rounds.addPlayer');
    Route::delete('/rounds/{round}/players/{signup}', [GroupSessionController::class, 'removePlayer'])->name('rounds.removePlayer');
    Route::patch('/rounds/{round}/close', [GroupSessionController::class, 'closeRound'])->name('rounds.close');
    Route::patch('/rounds/{round}/reopen', [GroupSessionController::class, 'reopenRound'])->name('rounds.reopen');
    Route::delete('/rounds/{round}/cancel', [GroupSessionController::class, 'cancelRound'])->name('rounds.cancel');
});

Route::middleware('auth')->group(function () {
    Route::get('/group-rounds/my-bookings', [App\Http\Controllers\GroupRoundSignupController::class, 'myBookings'])
        ->name('group-rounds.my-bookings');
    Route::get('/group-rounds/{round}/checkout', [App\Http\Controllers\GroupRoundSignupController::class, 'checkout'])
        ->name('group-rounds.checkout');
    Route::post('/group-rounds/{round}/signup', [App\Http\Controllers\GroupRoundSignupController::class, 'store'])
        ->name('group-rounds.signup');
});

Route::post('/group-rounds/{round}/signups/{signup}/cancel', [\App\Http\Controllers\GroupRoundSignupController::class, 'cancel'])
    ->middleware('auth')
    ->name('group-rounds.cancel');
