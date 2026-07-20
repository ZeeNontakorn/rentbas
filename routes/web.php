<?php

use App\Http\Controllers\Admin\CourtController as AdminCourtController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ManageCourseController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Admin\CalendarController;

use Illuminate\Support\Facades\Route;



// 1. Landing Page — ใครๆ ก็เข้าได้
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public schedule API (used by home calendar to show real booking status)
Route::get('/schedule', [HomeController::class, 'schedule'])->name('schedule');

Route::get('/media/{path}', function (string $path) {
    $base = realpath(storage_path('app/public'));
    $full = realpath(storage_path('app/public/' . $path));

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
Route::middleware(['auth','verified_otp'])->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/request-otp-email', [ProfileController::class, 'requestOtpForEmailChange'])->name('profile.request-otp-email');

    // Booking System
    Route::prefix('booking')->name('booking.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/court/{court}', [BookingController::class, 'show'])->name('show');
        Route::post('/', [BookingController::class, 'store'])->name('store');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
    });

    // History
    Route::get('/history', [BookingController::class, 'history'])->name('history');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');


 // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



// 5. Admin Routes — ต้องเป็น Admin เท่านั้น
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::delete('/courts/{court}', [AdminCourtController::class, 'destroy'])->name('destroy');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
    Route::post('/bookings/bulk-approve', [BookingController::class, 'bulkApprove'])->name('bookings.bulkApprove');
    Route::post('/bookings/bulk-reject', [BookingController::class, 'bulkReject'])->name('bookings.bulkReject');
    Route::get('/courts', [AdminCourtController::class, 'index'])->name('courts');
    Route::post('/courts', [AdminCourtController::class, 'store'])->name('court.create');
    Route::put('/courts/{court}', [AdminCourtController::class, 'update'])->name('court.update');
    Route::post('/courts/{court}/status', [AdminCourtController::class, 'updateStatus'])->name('courts.status');
    Route::post('/courts/slot', [AdminCourtController::class, 'updateSlot'])->name('courts.slot');
    Route::post('/courts/slot', [AdminCourtController::class, 'updateSlot'])->name('courts.slot');

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

    // ระบบจัดการผู้ใช้ (User Management)
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');

    // ตั้งค่าเว็บไซต์ (Site Settings)
    Route::get('/edit-text', [\App\Http\Controllers\Admin\SettingController::class, 'edit'])->name('edit.text');
    Route::post('/edit-text', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('edit.text.update');
});




// 6. Password Reset via OTP
Route::controller(AuthController::class)->group(function () {
    Route::get('/forgot-password',  'showForgotPasswordForm')->name('password.request');
    Route::post('/forgot-password', 'sendResetLinkEmail')->name('password.email');

    Route::get('/reset-otp',        'showResetOtpForm')->name('password.otp.form');
    Route::post('/reset-otp',       'verifyResetOtp')->name('password.otp.verify');

    Route::get('/reset-password',   'showResetPasswordForm')->name('password.reset.form');
    Route::post('/reset-password',  'resetPassword')->name('password.reset');
});


Route::post('/admin/courts/images', [App\Http\Controllers\Admin\CourtController::class, 'updateImages'])
    ->name('admin.courts.images.update');

