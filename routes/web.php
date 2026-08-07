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
use Illuminate\Support\Facades\Route;

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

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
    Route::post('/credit-topup-packages/promptpay', [CreditTopupPackageController::class, 'updatePromptPayNumber'])->name('credit-topup-packages.promptpay');

    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::put('/pricing/rules/bulk-update', [PricingController::class, 'bulkUpdateRules'])->name('pricing.rules.bulkUpdate');
    Route::put('/pricing/rules/{pricingRule}', [PricingController::class, 'updateRule'])->name('pricing.rules.update');
    Route::post('/pricing/packages', [PricingController::class, 'storePackage'])->name('pricing.packages.store');
    Route::put('/pricing/packages/{promotionPackage}', [PricingController::class, 'updatePackage'])->name('pricing.packages.update');
    Route::delete('/pricing/packages/{promotionPackage}', [PricingController::class, 'destroyPackage'])->name('pricing.packages.destroy');
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
