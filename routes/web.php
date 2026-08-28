<?php

use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtController as AdminCourtController;
use App\Http\Controllers\Admin\CreditController;
use App\Http\Controllers\Admin\CreditTopupController as AdminCreditTopupController;
use App\Http\Controllers\Admin\CreditTopupPackageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LineLinkController;
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
use App\Models\Availability;
use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\Court;
use App\Models\CourtSection;
use App\Models\CourtClosure;
use App\Models\CreditTransaction;
use App\Models\CreditTopupPackage;
use App\Models\CreditTopupRequest;
use App\Models\Facility;
use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use App\Models\GroupSession;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\PrivateTrainingBooking;
use App\Models\PricingRule;
use App\Models\PromotionPackage;
use App\Models\Review;
use App\Models\StaffProfile;
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
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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

    Route::post('/__e2e/coach-assistant-management/case', function () {
        return DB::transaction(function () {
            $oldUsers = User::where('email', 'like', 'coas-%@e2e.local')->get();
            CalendarEvent::whereIn('coach_id', $oldUsers->pluck('id'))->delete();
            Availability::whereIn('user_id', $oldUsers->pluck('id'))->delete();
            User::whereIn('id', $oldUsers->pluck('id'))->delete();

            $account = function (string $email, string $name, string $role, string $membershipType, string $phone) {
                return User::create([
                    'name' => $name,
                    'us_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => '123456',
                    'role' => $role,
                    'membership_type' => $membershipType,
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]);
            };

            $admin = $account('coas-admin@e2e.local', 'ผู้ดูแล COAS E2E', 'admin', 'admin', '0898000001');
            $coach = $account('coas-coach@e2e.local', 'โค้ชชาตรี E2E', 'staff', 'coach', '0898000002');
            $assistant = $account('coas-assistant@e2e.local', 'ผู้ช่วยสนามมานะ E2E', 'staff', 'court_assistant', '0898000003');
            $removable = $account('coas-remove@e2e.local', 'ผู้ช่วยรอถอดบทบาท E2E', 'staff', 'court_assistant', '0898000004');

            StaffProfile::create([
                'user_id' => $coach->id,
                'specialty' => 'พัฒนาทักษะการยิงและการเลี้ยงบอล',
                'bio' => 'โค้ชทดสอบสำหรับระบบจัดการบุคลากร',
                'gender' => 'male',
                'experience_years' => 8,
            ]);
            StaffProfile::create([
                'user_id' => $assistant->id,
                'specialty' => 'ดูแลสนามและอุปกรณ์',
                'bio' => 'ผู้ช่วยสนามสำหรับทดสอบระบบ',
                'gender' => 'female',
                'experience_years' => 3,
            ]);

            $eventDate = now('Asia/Bangkok')->addDay()->startOfDay();
            CalendarEvent::create([
                'title' => 'ฝึกซ้อมทีมเยาวชน E2E',
                'description' => 'กิจกรรมทดสอบตารางงาน CO-AS-M',
                'starts_at' => $eventDate->copy()->setTime(10, 0),
                'ends_at' => $eventDate->copy()->setTime(12, 0),
                'coach_id' => $coach->id,
                'coach_name' => $coach->us_name,
                'event_type' => 'work',
                'recurrence' => 'none',
                'color' => '#2563eb',
            ]);

            return response()->json([
                'admin' => ['email' => $admin->email, 'password' => '123456'],
                'coach' => ['id' => $coach->id, 'name' => $coach->name, 'email' => $coach->email],
                'assistant' => ['id' => $assistant->id, 'name' => $assistant->name, 'email' => $assistant->email],
                'removable' => ['id' => $removable->id, 'name' => $removable->name, 'email' => $removable->email],
                'event' => ['title' => 'ฝึกซ้อมทีมเยาวชน E2E', 'date' => $eventDate->toDateString(), 'start' => '10:00', 'end' => '12:00'],
            ]);
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/coach-assistant-management/{staff}/state', function (User $staff) {
        return response()->json([
            'user' => $staff->only(['id', 'name', 'us_name', 'email', 'phone', 'role', 'membership_type', 'avatar']),
            'profile' => $staff->staffProfile?->only(['specialty', 'bio', 'gender', 'profile_image']),
        ]);
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

    Route::post('/__e2e/group-session-admin/case', function (\Illuminate\Http\Request $request) {
        return DB::transaction(function () use ($request) {
            $prefix = '[E2E ADMIN GROUP]';

            GroupRound::where('title', 'like', $prefix.'%')->delete();
            GroupSession::where('name', 'like', $prefix.'%')->delete();

            $court = Court::updateOrCreate(
                ['name' => 'สนาม E2E Admin กลุ่มบาส'],
                ['court_status' => 'open']
            );
            $admin = User::updateOrCreate(['email' => 'group-admin@e2e.local'], [
                'us_name' => 'แอดมินกลุ่มบาส E2E', 'name' => 'แอดมินกลุ่มบาส E2E', 'phone' => '0890000010', 'password' => '123456',
                'role' => 'admin', 'membership_type' => 'admin', 'is_verified' => true,
                'email_verified_at' => now(),
            ]);
            $member = User::updateOrCreate(['email' => 'group-member@e2e.local'], [
                'us_name' => 'สมาชิกกลุ่มบาส E2E', 'name' => 'สมาชิกกลุ่มบาส E2E', 'phone' => '0890000011', 'password' => '123456',
                'role' => 'user', 'membership_type' => 'customer', 'is_verified' => true,
                'email_verified_at' => now(), 'credit_balance' => 50000,
            ]);
            $member->forceFill(['credit_balance' => 50000])->save();

            $session = GroupSession::create([
                'name' => $prefix.' TEMPLATE', 'day_of_week' => 2,
                'start_time' => '18:00', 'end_time' => '20:00', 'court_id' => $court->id,
                'max_players' => 12, 'credit_cost' => 100, 'created_by' => $admin->id,
            ]);
            $round = GroupRound::create([
                'group_session_id' => $session->id, 'title' => $prefix.' UPCOMING',
                'play_date' => today()->addDays(7), 'start_time' => '18:00', 'end_time' => '20:00',
                'court_id' => $court->id, 'max_players' => 12, 'credit_cost' => 100,
                'cancel_deadline' => now()->addDays(6), 'status' => 'open', 'created_by' => $admin->id,
            ]);
            $past = GroupRound::create([
                'title' => $prefix.' HISTORY SEARCH', 'play_date' => today()->subDay(),
                'start_time' => '18:00', 'end_time' => '20:00', 'court_id' => $court->id,
                'max_players' => 12, 'credit_cost' => 100, 'status' => 'closed', 'created_by' => $admin->id,
            ]);

            if ($request->boolean('with_signup')) {
                GroupRoundSignup::create([
                    'group_round_id' => $round->id, 'user_id' => $member->id, 'order_number' => 1,
                    'credit_used' => 100, 'status' => 'confirmed', 'is_reserve' => false,
                    'signed_up_at' => now(), 'added_by' => $admin->id,
                ]);
                $member->decrement('credit_balance', 10000);
            }

            return response()->json([
                'admin' => ['email' => $admin->email, 'password' => '123456'],
                'member' => ['id' => $member->id, 'name' => $member->name, 'email' => $member->email],
                'court' => ['id' => $court->id, 'name' => $court->name],
                'session' => ['id' => $session->id, 'name' => $session->name],
                'round' => ['id' => $round->id, 'title' => $round->title],
                'past' => ['id' => $past->id, 'title' => $past->title],
                'play_date' => today()->addDays(7)->format('Y-m-d'),
                'session_play_date' => $session->nextOccurrence()->format('Y-m-d'),
            ]);
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/group-session-admin/{round}/state', function (GroupRound $round) {
        $member = User::where('email', 'group-member@e2e.local')->firstOrFail();

        return response()->json([
            'round_exists' => $round->exists,
            'round_status' => $round->fresh()->status,
            'round' => $round->fresh()->only(['title', 'play_date', 'start_time', 'end_time', 'court_id', 'max_players', 'credit_cost', 'cancel_deadline']),
            'member_credit' => $member->credit_balance,
            'signups' => $round->signups()->get()->map(fn ($signup) => [
                'id' => $signup->id, 'name' => $signup->displayName(), 'status' => $signup->status,
                'credit_used' => $signup->credit_used,
            ]),
        ]);
    });

    Route::post('/__e2e/private-training/case', function (\Illuminate\Http\Request $request) {
        return DB::transaction(function () use ($request) {
            $emails = [
                'ptb-user@e2e.local',
                'ptb-admin@e2e.local',
                'ptb-coach@e2e.local',
                'ptb-assistant-free@e2e.local',
                'ptb-assistant-busy@e2e.local',
            ];
            $oldUsers = User::whereIn('email', $emails)->get();
            $oldUserIds = $oldUsers->pluck('id');
            $oldPackageIds = Package::where('name', 'like', '[E2E PTB]%')->pluck('id');

            PrivateTrainingBooking::whereIn('user_id', $oldUserIds)
                ->orWhereIn('coach_id', $oldUserIds)
                ->orWhereIn('court_assistant_id', $oldUserIds)
                ->delete();
            CreditTransaction::whereIn('user_id', $oldUserIds)->delete();
            PackagePurchase::whereIn('user_id', $oldUserIds)->delete();
            Availability::whereIn('user_id', $oldUserIds)->delete();
            foreach ($oldUsers as $oldUser) {
                $oldUser->notifications()->delete();
            }
            Package::whereIn('id', $oldPackageIds)->delete();
            PromotionPackage::where('code', 'like', 'e2e-ptb-%')->delete();

            $account = function (string $email, string $name, string $role, string $membershipType) {
                $user = User::updateOrCreate(['email' => $email], [
                    'name' => $name,
                    'us_name' => $name,
                    'phone' => '0891000000',
                    'password' => '123456',
                    'role' => $role,
                    'membership_type' => $membershipType,
                    'is_verified' => true,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                return $user;
            };

            $user = $account('ptb-user@e2e.local', 'ผู้ใช้ PTB E2E', 'user', 'customer');
            $admin = $account('ptb-admin@e2e.local', 'ผู้ดูแล PTB E2E', 'admin', 'customer');
            $coach = $account('ptb-coach@e2e.local', 'โค้ช PTB E2E', 'staff', 'coach');
            $freeAssistant = $account('ptb-assistant-free@e2e.local', 'ผู้ช่วยสนาม A ว่าง', 'staff', 'court_assistant');
            $busyAssistant = $account('ptb-assistant-busy@e2e.local', 'ผู้ช่วยสนาม B ไม่ว่าง', 'staff', 'court_assistant');

            $creditBaht = (int) $request->input('credit_balance', 2000);
            $user->forceFill(['credit_balance' => $creditBaht * 100])->save();
            $admin->forceFill(['credit_balance' => 0])->save();

            $weekdayPackage = Package::create([
                'name' => '[E2E PTB] แพ็กเกจจันทร์-ศุกร์',
                'type' => 'private',
                'description' => 'แพ็กเกจทดสอบสำหรับวันทำการ',
                'price' => 1000,
                'num_of_use' => 4,
                'day' => 60,
                'usable_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
                'is_active' => true,
            ]);
            $weekendPackage = Package::create([
                'name' => '[E2E PTB] แพ็กเกจเสาร์-อาทิตย์',
                'type' => 'private',
                'description' => 'แพ็กเกจทดสอบสำหรับวันหยุด',
                'price' => 1000,
                'num_of_use' => 4,
                'day' => 60,
                'usable_days' => ['sat', 'sun'],
                'is_active' => true,
            ]);
            PromotionPackage::create([
                'code' => 'e2e-ptb-private',
                'label' => '[E2E PTB] Private Training',
                'category' => 'private',
                'duration_hours' => 1,
                'max_people' => 6,
                'base_price' => 100000,
                'session_count' => 4,
                'validity_days' => 60,
                'is_active' => true,
            ]);

            $requestedPurchases = (array) $request->input('purchases', []);
            $remainingUse = (int) $request->input('remaining_use', 4);
            $purchases = collect();
            foreach (['weekday' => $weekdayPackage, 'weekend' => $weekendPackage] as $key => $package) {
                if (! in_array($key, $requestedPurchases, true)) {
                    continue;
                }
                $purchases[$key] = PackagePurchase::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'price' => (int) round($package->price * 100),
                    'status' => 'approved',
                    'booking_source' => 'credit',
                    'payment_method' => 'credit',
                    'payment_status' => 'paid',
                    'remaining_use' => $remainingUse,
                    'paid_at' => now(),
                    'expired_at' => now()->addDays(60),
                ]);
            }

            $weekday = Carbon::today('Asia/Bangkok')->next(Carbon::WEDNESDAY);
            $weekend = Carbon::today('Asia/Bangkok')->next(Carbon::SUNDAY);
            foreach ([$weekday, $weekend] as $date) {
                Availability::create([
                    'user_id' => $busyAssistant->id,
                    'date' => $date->toDateString(),
                    'start_time' => '18:00:00',
                    'end_time' => '19:00:00',
                    'status' => 'booked',
                    'detail' => 'ผู้ช่วยไม่ว่างสำหรับ E2E',
                ]);
            }
            Availability::create([
                'user_id' => $coach->id,
                'date' => $weekday->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '15:30:00',
                'status' => 'booked',
                'detail' => 'โค้ชไม่ว่างสำหรับ E2E',
            ]);

            return response()->json([
                'user' => ['id' => $user->id, 'name' => $user->us_name, 'email' => $user->email, 'password' => '123456', 'credit_balance' => $creditBaht],
                'admin' => ['id' => $admin->id, 'email' => $admin->email, 'password' => '123456'],
                'coach' => ['id' => $coach->id, 'name' => $coach->us_name],
                'assistants' => [
                    'free' => ['id' => $freeAssistant->id, 'name' => $freeAssistant->us_name],
                    'busy' => ['id' => $busyAssistant->id, 'name' => $busyAssistant->us_name],
                ],
                'packages' => [
                    'weekday' => ['id' => $weekdayPackage->id, 'name' => $weekdayPackage->name, 'price' => (float) $weekdayPackage->price],
                    'weekend' => ['id' => $weekendPackage->id, 'name' => $weekendPackage->name, 'price' => (float) $weekendPackage->price],
                ],
                'purchases' => $purchases->map(fn ($purchase) => ['id' => $purchase->id, 'remaining_use' => $purchase->remaining_use]),
                'dates' => [
                    'today' => Carbon::today('Asia/Bangkok')->toDateString(),
                    'weekday' => $weekday->toDateString(),
                    'weekend' => $weekend->toDateString(),
                ],
                'times' => ['available_start' => '18:00', 'available_end' => '19:00', 'busy_start' => '14:00', 'busy_end' => '15:00'],
            ]);
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/private-training/state', function () {
        $user = User::where('email', 'ptb-user@e2e.local')->firstOrFail();

        return response()->json([
            'credit_balance' => $user->credit_balance / 100,
            'purchases' => PackagePurchase::with('package')
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($purchase) => [
                    'id' => $purchase->id,
                    'package' => $purchase->package?->name,
                    'status' => $purchase->status,
                    'remaining_use' => $purchase->remaining_use,
                    'price' => $purchase->price / 100,
                ]),
            'transactions' => CreditTransaction::where('user_id', $user->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($transaction) => [
                    'type' => $transaction->type,
                    'amount' => $transaction->amount / 100,
                    'balance_after' => $transaction->balance_after / 100,
                    'note' => $transaction->note,
                    'package_purchase_id' => $transaction->package_purchase_id,
                ]),
            'bookings' => PrivateTrainingBooking::where('user_id', $user->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($booking) => [
                    'id' => $booking->id,
                    'date' => $booking->date->toDateString(),
                    'start_time' => substr($booking->start_time, 0, 5),
                    'end_time' => substr($booking->end_time, 0, 5),
                    'participant_count' => $booking->participant_count,
                    'assistant_requested' => $booking->assistant_requested,
                    'court_assistant_id' => $booking->court_assistant_id,
                    'court_id' => $booking->court_id,
                    'court_section_id' => $booking->court_section_id,
                    'status' => $booking->status,
                    'reject_reason' => $booking->reject_reason,
                    'package_purchase_id' => $booking->package_purchase_id,
                    'note' => $booking->note,
                ]),
        ]);
    });

    Route::post('/__e2e/private-training-management/case', function () {
        return DB::transaction(function () {
            $fixture = json_decode(Route::dispatch(
                \Illuminate\Http\Request::create('/__e2e/private-training/case', 'POST', ['purchases' => ['weekday']])
            )->getContent(), true);

            $user = User::findOrFail($fixture['user']['id']);
            $admin = User::findOrFail($fixture['admin']['id']);
            $coach = User::findOrFail($fixture['coach']['id']);
            $assistant = User::findOrFail($fixture['assistants']['free']['id']);
            $purchaseId = $fixture['purchases']['weekday']['id'] ?? null;
            $purchase = $purchaseId
                ? PackagePurchase::findOrFail($purchaseId)
                : PackagePurchase::create([
                    'user_id' => $user->id, 'package_id' => $fixture['packages']['weekday']['id'],
                    'price' => 100000, 'status' => 'approved', 'booking_source' => 'credit',
                    'payment_method' => 'credit', 'payment_status' => 'paid', 'remaining_use' => 4,
                    'paid_at' => now(), 'expired_at' => now()->addDays(60),
                ]);
            $fixture['purchases']['weekday'] = ['id' => $purchase->id, 'remaining_use' => $purchase->remaining_use];

            $alternateCoach = User::updateOrCreate(['email' => 'ptm-coach-alt@e2e.local'], [
                'name' => 'โค้ชสำรอง PTM E2E', 'us_name' => 'โค้ชสำรอง PTM E2E', 'phone' => '0891000009',
                'password' => '123456', 'role' => 'staff', 'membership_type' => 'coach', 'is_verified' => true,
                'email_verified_at' => now(),
            ]);

            PrivateTrainingBooking::where('coach_id', $alternateCoach->id)->delete();
            $court = Court::updateOrCreate(['name' => '[E2E PTM] สนามว่าง'], ['court_status' => 'open']);
            $freeSection = CourtSection::updateOrCreate(
                ['court_id' => $court->id, 'code' => 'full'],
                ['name' => 'เต็มสนาม', 'is_active' => true]
            );
            $busyCourt = Court::updateOrCreate(['name' => '[E2E PTM] สนามไม่ว่าง'], ['court_status' => 'open']);
            $busySection = CourtSection::updateOrCreate(
                ['court_id' => $busyCourt->id, 'code' => 'full'],
                ['name' => 'เต็มสนาม', 'is_active' => true]
            );

            $future = Carbon::today('Asia/Bangkok')->addDays(7);
            $make = function (string $note, string $status, Carbon $date, string $start, string $end, array $extra = []) use ($user, $coach, $purchase) {
                return PrivateTrainingBooking::create(array_merge([
                    'user_id' => $user->id, 'coach_id' => $coach->id,
                    'package_purchase_id' => $purchase->id, 'participant_count' => 2,
                    'assistant_requested' => true, 'date' => $date->toDateString(),
                    'start_time' => $start, 'end_time' => $end, 'status' => $status,
                    'note' => $note, 'payment_status' => 'paid_by_package',
                ], $extra));
            };

            $pending = $make('[E2E PTM] รออนุมัติ', 'pending', $future, '09:00:00', '10:00:00');
            $awaiting = $make('[E2E PTM] รอจัดสนาม', 'awaiting_court', $future, '11:00:00', '12:00:00', [
                'court_assistant_id' => $assistant->id,
            ]);
            $confirmed = $make('[E2E PTM] ยืนยันแล้ว', 'confirmed', $future, '13:00:00', '14:00:00', [
                'court_id' => $court->id, 'court_section_id' => $freeSection->id,
                'court_assistant_id' => $assistant->id, 'court_assigned_by' => $admin->id,
                'court_assigned_at' => now(),
            ]);
            $rejected = $make('[E2E PTM] ปฏิเสธแล้ว', 'rejected', $future, '15:00:00', '16:00:00', [
                'reject_reason' => 'ไม่สามารถให้บริการในช่วงเวลาที่เลือก',
            ]);
            $expired = $make('[E2E PTM] เลยกำหนด', 'expired', Carbon::today('Asia/Bangkok')->subDay(), '10:00:00', '11:00:00');

            $blocker = PrivateTrainingBooking::create([
                'user_id' => $user->id, 'coach_id' => $alternateCoach->id,
                'participant_count' => 1, 'assistant_requested' => false,
                'court_id' => $busyCourt->id, 'court_section_id' => $busySection->id,
                'court_assigned_by' => $admin->id, 'court_assigned_at' => now(),
                'date' => $future->toDateString(), 'start_time' => '11:00:00', 'end_time' => '12:00:00',
                'status' => 'confirmed', 'note' => '[E2E PTM] ตัวบล็อกสนาม', 'payment_status' => 'paid',
            ]);

            collect([$pending, $awaiting, $confirmed, $rejected, $expired, $blocker])
                ->values()
                ->each(fn (PrivateTrainingBooking $booking, int $index) => $booking
                    ->forceFill(['created_at' => now()->subYears(10)->addSeconds($index)])
                    ->saveQuietly());

            return response()->json(array_merge($fixture, [
                'bookings' => [
                    'pending' => $pending->id, 'awaiting_court' => $awaiting->id,
                    'confirmed' => $confirmed->id, 'rejected' => $rejected->id,
                    'expired' => $expired->id, 'blocker' => $blocker->id,
                ],
                'courts' => [
                    'free' => ['id' => $court->id, 'section_id' => $freeSection->id, 'name' => $court->name],
                    'busy' => ['id' => $busyCourt->id, 'section_id' => $busySection->id, 'name' => $busyCourt->name],
                ],
                'schedule_date' => $future->toDateString(),
            ]));
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::post('/__e2e/private-training-package/case', function () {
        return DB::transaction(function () {
            $fixture = json_decode(Route::dispatch(
                \Illuminate\Http\Request::create('/__e2e/private-training/case', 'POST')
            )->getContent(), true);

            $oldIds = Package::where('name', 'like', '[E2E PTP]%')->pluck('id');
            PackagePurchase::whereIn('package_id', $oldIds)->delete();
            Package::whereIn('id', $oldIds)->delete();

            $active = Package::create([
                'name' => '[E2E PTP] Private Training',
                'type' => 'private', 'description' => 'แพ็กเกจสำหรับจองไพรเวทเทรนนิ่ง',
                'price' => 6900, 'num_of_use' => 4, 'day' => 120,
                'usable_days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'is_active' => true,
            ]);
            $inactive = Package::create([
                'name' => '[E2E PTP] ปิดใช้งาน',
                'type' => 'private', 'description' => 'แพ็กเกจที่ไม่ควรแสดงให้ลูกค้า',
                'price' => 7500, 'num_of_use' => 5, 'day' => 90,
                'usable_days' => [], 'is_active' => false,
            ]);

            return response()->json(array_merge($fixture, [
                'ptp_packages' => [
                    'active' => ['id' => $active->id, 'name' => $active->name],
                    'inactive' => ['id' => $inactive->id, 'name' => $inactive->name],
                ],
            ]));
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/private-training-package/state', function () {
        return response()->json(Package::where('name', 'like', '[E2E PTP]%')
            ->orderBy('id')->get()->map(fn (Package $package) => [
                'id' => $package->id, 'name' => $package->name,
                'description' => $package->description, 'price' => (float) $package->price,
                'num_of_use' => $package->num_of_use, 'day' => $package->day,
                'usable_days' => $package->usable_days ?? [], 'is_active' => $package->is_active,
                'image' => $package->image,
            ]));
    });

    Route::post('/__e2e/review-rating/case', function () {
        return DB::transaction(function () {
            $fixture = json_decode(Route::dispatch(
                \Illuminate\Http\Request::create('/__e2e/private-training/case', 'POST')
            )->getContent(), true);
            $user = User::findOrFail($fixture['user']['id']);
            $reviewer = User::updateOrCreate(['email' => 'review-display@e2e.local'], [
                'name' => 'สมาชิกรีวิว E2E', 'us_name' => 'สมาชิกรีวิว E2E', 'phone' => '0892000001',
                'password' => '123456', 'role' => 'user', 'membership_type' => 'customer',
                'is_verified' => true, 'email_verified_at' => now(),
            ]);

            Review::whereIn('user_id', [$user->id, $reviewer->id])->delete();
            Facility::where('slug', 'like', 'e2e-%')->delete();
            $facilities = collect([
                ['slug' => 'e2e-cafe', 'name' => 'คาเฟ่ & เครื่องดื่ม'],
                ['slug' => 'e2e-shop', 'name' => 'Basketball Shop'],
                ['slug' => 'e2e-restroom', 'name' => 'ห้องน้ำ & ห้องอาบน้ำ'],
            ])->mapWithKeys(function (array $item, int $index) {
                $facility = Facility::firstOrCreate(['name' => $item['name']], [
                    'slug' => $item['slug'], 'description' => 'หัวข้อรีวิวสำหรับ E2E',
                    'is_active' => true, 'sort_order' => $index + 1,
                ]);
                $facility->update(['is_active' => true]);

                return [$item['slug'] => $facility];
            });

            foreach (range(1, 4) as $index) {
                $review = Review::create([
                    'user_id' => $reviewer->id, 'overall_rating' => 5,
                    'comment' => "รีวิวตัวอย่าง E2E ลำดับ {$index} บริการดีและสนามสะอาด",
                    'status' => 'published', 'published_at' => now()->subMinutes($index),
                ]);
                $review->ratings()->create(['facility_id' => $facilities->first()->id, 'rating' => 5]);
            }

            return response()->json(array_merge($fixture, [
                'review_user' => ['id' => $user->id, 'name' => $user->us_name, 'email' => $user->email, 'password' => '123456'],
                'facilities' => $facilities->map(fn (Facility $facility) => ['id' => $facility->id, 'name' => $facility->name]),
                'baseline_comment' => 'รีวิวตัวอย่าง E2E ลำดับ 1 บริการดีและสนามสะอาด',
            ]));
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/review-rating/state', function () {
        $user = User::where('email', 'ptb-user@e2e.local')->firstOrFail();

        return response()->json(Review::with(['ratings.facility', 'images'])
            ->where('user_id', $user->id)->orderBy('id')->get()->map(fn (Review $review) => [
                'id' => $review->id, 'overall_rating' => $review->overall_rating,
                'comment' => $review->comment, 'status' => $review->status,
                'ratings' => $review->ratings->mapWithKeys(fn ($rating) => [$rating->facility->name => $rating->rating]),
                'images' => $review->images->pluck('image_path')->values(),
            ]));
    });

    Route::post('/__e2e/court-booking/case', function (\Illuminate\Http\Request $request) {
        return DB::transaction(function () use ($request) {
            $fixture = json_decode(Route::dispatch(\Illuminate\Http\Request::create('/__e2e/private-training/case', 'POST'))->getContent(), true);
            $user = User::findOrFail($fixture['user']['id']);
            Booking::where('user_id', $user->id)->delete();
            $user->forceFill(['credit_balance' => (int) $request->input('credit_balance', 5000) * 100])->save();

            $court = Court::updateOrCreate(['name' => '[E2E COUR-BOO] สนาม 1'], [
                'court_status' => 'open', 'slot_interval_minutes' => 30, 'min_booking_minutes' => 30,
            ]);
            $sections = collect(['full' => 'เต็มสนาม', 'a' => 'ครึ่ง A', 'b' => 'ครึ่ง B'])
                ->mapWithKeys(fn ($name, $code) => [$code => CourtSection::updateOrCreate(
                    ['court_id' => $court->id, 'code' => $code], ['name' => $name, 'is_active' => true]
                )]);
            DB::table('court_section_blocks')->whereIn('court_section_id', $sections->pluck('id'))->delete();
            foreach (['a', 'b'] as $half) {
                DB::table('court_section_blocks')->insertOrIgnore([
                    ['court_section_id' => $sections['full']->id, 'blocks_section_id' => $sections[$half]->id],
                    ['court_section_id' => $sections[$half]->id, 'blocks_section_id' => $sections['full']->id],
                ]);
            }
            $date = Carbon::today('Asia/Bangkok')->addDay();
            CourtClosure::where('court_id', $court->id)->delete();
            CourtClosure::create(['court_id' => $court->id, 'date' => $date, 'start_time' => '20:00', 'end_time' => '21:00', 'type' => 'unavailable']);
            PricingRule::updateOrCreate(['code' => 'e2e-court-full'], [
                'label' => 'ราคาปกติเต็มสนาม', 'day_type' => 'everyday', 'start_time' => '06:00', 'end_time' => '22:00',
                'court_type' => 'full', 'price_per_hour' => 100000, 'priority' => 100, 'is_active' => true,
            ]);
            PricingRule::updateOrCreate(['code' => 'e2e-court-half'], [
                'label' => 'ราคาปกติครึ่งสนาม', 'day_type' => 'everyday', 'start_time' => '06:00', 'end_time' => '22:00',
                'court_type' => 'half', 'price_per_hour' => 50000, 'priority' => 100, 'is_active' => true,
            ]);
            PromotionPackage::updateOrCreate(['code' => 'e2e-court-promo'], [
                'label' => 'COUR-BOO Promotion', 'category' => 'court', 'court_type' => 'full',
                'available_days' => ['weekday', 'weekend', 'holiday'], 'available_start_time' => '18:00',
                'available_end_time' => '20:00', 'duration_hours' => 1, 'base_price' => 80000, 'is_active' => true,
            ]);

            $block = Booking::create([
                'user_id' => $user->id, 'court_id' => $court->id, 'court_section_id' => $sections['a']->id,
                'booking_date' => $date, 'start_time' => '18:00', 'end_time' => '19:00',
                'status' => 'approved', 'payment_status' => 'paid', 'price' => 50000,
            ]);
            $checkout = null;
            if ($request->boolean('with_checkout')) {
                $checkout = Booking::create([
                    'user_id' => $user->id, 'court_id' => $court->id, 'court_section_id' => $sections['b']->id,
                    'booking_date' => $date, 'start_time' => '16:00', 'end_time' => '17:00',
                    'status' => 'pending_payment', 'payment_status' => 'unpaid', 'price' => 50000,
                    'price_breakdown' => [['label' => 'ราคาปกติครึ่งสนาม', 'price' => 50000]],
                    'locked_until' => now()->addSeconds((int) $request->input('lock_seconds', 900)),
                ]);
            }
            return response()->json(array_merge($fixture, [
                'user' => array_merge($fixture['user'], ['credit_balance' => (int) $request->input('credit_balance', 5000)]),
                'court' => ['id' => $court->id, 'name' => $court->name],
                'sections' => $sections->map(fn ($section) => ['id' => $section->id, 'name' => $section->name]),
                'date' => $date->toDateString(), 'block_booking_id' => $block->id,
                'checkout_booking_id' => $checkout?->id,
            ]));
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/court-booking/state', function () {
        $user = User::where('email', 'ptb-user@e2e.local')->firstOrFail();
        return response()->json([
            'credit_balance' => $user->credit_balance / 100,
            'bookings' => Booking::where('user_id', $user->id)->orderBy('id')->get()->map(fn (Booking $booking) => [
                'id' => $booking->id, 'status' => $booking->status, 'payment_status' => $booking->payment_status,
                'price' => $booking->price / 100, 'locked_until' => $booking->locked_until?->toIso8601String(),
            ]),
            'transactions' => CreditTransaction::where('user_id', $user->id)->whereNotNull('booking_id')->count(),
        ]);
    });

    Route::post('/__e2e/credit-management/case', function () {
        return DB::transaction(function () {
            $admin = User::updateOrCreate(['email' => 'credit-admin@e2e.local'], [
                'name' => 'ผู้ดูแลเครดิต E2E', 'us_name' => 'ผู้ดูแลเครดิต E2E', 'phone' => '0890000091',
                'password' => '123456', 'role' => 'admin', 'membership_type' => 'admin',
                'is_verified' => true, 'email_verified_at' => now(),
            ]);
            $customer = User::updateOrCreate(['email' => 'credit-customer@e2e.local'], [
                'name' => 'ลูกค้าทดสอบเครดิต', 'us_name' => 'ลูกค้าทดสอบเครดิต', 'phone' => '0890000092',
                'password' => '123456', 'role' => 'user', 'membership_type' => 'customer',
                'is_verified' => true, 'email_verified_at' => now(), 'credit_balance' => 100000,
                'credit_expires_at' => now()->addDays(365),
            ]);

            CreditTransaction::where('user_id', $customer->id)->delete();
            CreditTopupRequest::where('user_id', $customer->id)->delete();
            $customer->forceFill(['credit_balance' => 100000, 'credit_expires_at' => now()->addDays(365)])->save();

            $package = CreditTopupPackage::updateOrCreate(['label' => '[E2E CREDIT-M] แพ็กเกจ 500'], [
                'price_satang' => 50000, 'credit_satang' => 55000, 'expiry_days' => 180,
                'is_active' => true, 'sort_order' => 999,
            ]);
            $make = fn (string $status, ?int $packageId, ?int $expiryDays, int $amount) => CreditTopupRequest::create([
                'user_id' => $customer->id, 'credit_topup_package_id' => $packageId,
                'price_satang' => $amount, 'credit_satang' => $packageId ? 55000 : $amount,
                'expiry_days' => $expiryDays, 'payment_method' => 'promptpay',
                'slip_path' => 'credit-topup-slips/Ei1m0sHADNz8MsPpO67Y7gx7WQ4KNtmLBNbWNzPn.png',
                'status' => $status, 'approved_by' => $status === 'pending' ? null : $admin->id,
                'approved_at' => $status === 'pending' ? null : now(),
                'rejected_reason' => $status === 'rejected' ? 'ข้อมูลสลิปไม่ถูกต้อง' : null,
            ]);
            $requests = [
                'package' => $make('pending', $package->id, 180, 50000),
                'custom' => $make('pending', null, null, 70000),
                'approved' => $make('approved', $package->id, 180, 50000),
                'rejected' => $make('rejected', null, null, 70000),
            ];
            CreditTransaction::create([
                'user_id' => $customer->id, 'type' => 'topup', 'amount' => 100000,
                'balance_after' => 100000, 'admin_id' => $admin->id,
                'note' => '[E2E CREDIT-M] ยอดตั้งต้น', 'payment_method' => 'cash_counter',
                'processed_by_name' => $admin->name,
            ]);

            return response()->json([
                'admin' => ['id' => $admin->id, 'email' => $admin->email, 'password' => '123456'],
                'customer' => ['id' => $customer->id, 'name' => $customer->us_name, 'email' => $customer->email],
                'requests' => collect($requests)->map(fn ($item) => $item->id),
            ]);
        });
    })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::get('/__e2e/credit-management/state', function () {
        $customer = User::where('email', 'credit-customer@e2e.local')->firstOrFail();
        return response()->json([
            'credit_balance' => $customer->credit_balance / 100,
            'credit_expires_at' => $customer->credit_expires_at?->toDateString(),
            'requests' => CreditTopupRequest::where('user_id', $customer->id)->get(['id', 'status', 'rejected_reason']),
            'transactions' => CreditTransaction::where('user_id', $customer->id)->get(['type', 'amount', 'balance_after', 'note']),
        ]);
    });
}

// 5. Admin Routes — ต้องเป็น Admin เท่านั้น
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/view-date', [DashboardController::class, 'viewDateData'])->name('dashboard.view-date');
    Route::get('/dashboard/live-data', [DashboardController::class, 'liveData'])->name('dashboard.live-data');
    Route::get('/dashboard/stats-data', [DashboardController::class, 'statsData'])->name('dashboard.stats-data');

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
    Route::put('/users/{user}/profile', [UserController::class, 'updateProfile'])->name('users.profile.update');
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

    // จัดการลิงก์ทั้งหมดของเว็บ (LINE แยกตามจุดใช้งาน + โซเชียล + เบอร์โทร/อีเมลติดต่อ)
    Route::get('/line-links', [LineLinkController::class, 'index'])->name('line-links.index');
    Route::post('/line-links/footer', [LineLinkController::class, 'updateFooter'])->name('line-links.footer');
    Route::post('/line-links/topup', [LineLinkController::class, 'updateTopup'])->name('line-links.topup');
    Route::post('/line-links/course', [LineLinkController::class, 'updateCourse'])->name('line-links.course');
    Route::post('/line-links/official', [LineLinkController::class, 'updateOfficial'])->name('line-links.official');
    Route::post('/line-links/facebook', [LineLinkController::class, 'updateFacebook'])->name('line-links.facebook');
    Route::post('/line-links/youtube', [LineLinkController::class, 'updateYoutube'])->name('line-links.youtube');
    Route::post('/line-links/instagram', [LineLinkController::class, 'updateInstagram'])->name('line-links.instagram');
    Route::post('/line-links/phone', [LineLinkController::class, 'updatePhone'])->name('line-links.phone');
    Route::post('/line-links/email', [LineLinkController::class, 'updateEmail'])->name('line-links.email');
    Route::post('/website/facilities', [WebsiteReviewController::class, 'storeFacility'])->name('website.facilities.store');
    Route::put('/website/facilities/{facility}', [WebsiteReviewController::class, 'updateFacility'])->name('website.facilities.update');
    Route::delete('/website/facilities/{facility}', [WebsiteReviewController::class, 'destroyFacility'])->name('website.facilities.destroy');
    Route::patch('/website/reviews/{review}/status', [WebsiteReviewController::class, 'updateReviewStatus'])->name('website.reviews.status');
    Route::delete('/website/reviews/{review}', [WebsiteReviewController::class, 'destroyReview'])->name('website.reviews.destroy');
    Route::post('/courts/images', [CourtController::class, 'updateImages'])
        ->name('courts.images.update');

    Route::get('/users/{user}/credit', [CreditController::class, 'show'])->name('credits.show');
    Route::post('/users/{user}/credit/topup', [CreditController::class, 'topup'])->name('credits.topup');
    Route::post('/users/{user}/credit/deduct', [CreditController::class, 'deduct'])->name('credits.deduct');

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

    // จัดการให้จอง Private Training ได้สูงสุดกี่วัน
    Route::put('private-training/advance-booking-days', [PrivateTrainingController::class, 'updateAdvanceBookingDays'])
    ->name('private-training.advance-booking-days.update');
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
    // จัดการการจอง
    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
    Route::post('/bookings/bulk-approve', [BookingController::class, 'bulkApprove'])->name('bookings.bulkApprove');
    Route::post('/bookings/bulk-reject', [BookingController::class, 'bulkReject'])->name('bookings.bulkReject');
});

// จัดการคำขอจองเทรนเนอร์ส่วนตัว — เฉพาะ admin และ staff ประเภทพนักงานประจำเท่านั้น
// (พนักงานชั่วคราว และนักศึกษาฝึกงาน เข้าไม่ได้)
Route::middleware(['auth', 'permanent_staff_or_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/private-training', [PrivateTrainingController::class, 'adminIndex'])->name('private-training.index');
    Route::get('/private-training/{privateTrainingBooking}/available-courts', [PrivateTrainingController::class, 'availableCourts'])->name('private-training.available-courts');
    Route::post('/private-training/{privateTrainingBooking}/approve', [PrivateTrainingController::class, 'approve'])->name('private-training.approve');
    Route::post('/private-training/{privateTrainingBooking}/assign-court', [PrivateTrainingController::class, 'assignCourt'])->name('private-training.assign-court');
    Route::post('/private-training/{privateTrainingBooking}/reject', [PrivateTrainingController::class, 'reject'])->name('private-training.reject');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
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
