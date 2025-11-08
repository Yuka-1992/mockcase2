<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserRegisterController;
use App\Http\Controllers\UserIndexController;
use App\Http\Controllers\UserShowController;
use App\Http\Controllers\UserClockController;
use App\Http\Controllers\AdminIndexController;
use App\Http\Controllers\AdminShowController;
use App\Http\Controllers\AdminStaffsIndexController;
use App\Http\Controllers\AdminAttendancesIndexController;
use App\Http\Controllers\AdminStaffAttendancesController;
use App\Http\Controllers\AdminStaffAttendanceController;
use App\Http\Controllers\AdminAttendanceRequestController;
use App\Http\Controllers\AdminAttendanceCorrectionRequestController;
use App\Http\Controllers\UserAttendanceShowController;
use App\Http\Controllers\UserAttendanceIndexController;
use App\Http\Controllers\UserAttendanceRequestController;
use App\Http\Controllers\UserAttendanceController;
use App\Http\Controllers\UserAttendanceUpdateController;
use App\Http\Controllers\UserAttendanceCorrectionRequestController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [PageController::class, 'index']);
Route::get('/test', [PageController::class, 'test']);
Route::get('/register', [UserRegisterController::class, 'create'])->name('register.create');
Route::post('/register', [UserRegisterController::class, 'store'])->name('register.store');
Route::get('/login', [UserAuthController::class, 'create'])->name('login');
Route::post('/login', [UserAuthController::class, 'store'])->name('login.store');
Route::post('/logout', [UserAuthController::class, 'destroy'])->name('logout');
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index')->middleware('auth');
Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store')->middleware('auth');

// User routes (views: user/*)
Route::middleware(['auth'])->name('user.')->group(function () {
    Route::get('/attendance/list', [UserAttendanceIndexController::class, 'index'])->name('index');
    Route::get('/attendance/detail/{id}', [UserAttendanceShowController::class, 'detail'])->name('show');
    Route::get('/stamp_correction_request/list', [UserAttendanceRequestController::class, 'index'])->name('request.index');
    Route::post('/stamp_correction_request/list', [UserAttendanceCorrectionRequestController::class, 'store'])->name('request.store');
});

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
Route::middleware(['auth','role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/staff/list', [AdminStaffsIndexController::class, 'index'])
            ->name('staff.list');
        Route::get('/attendance/list', [AdminAttendancesIndexController::class, 'index'])
            ->name('attendance.index');
        Route::get('/attendance/staff/{id}', [AdminStaffAttendancesController::class, 'index'])
            ->name('staff.attendances');
        Route::get('/attendance/{id}', [AdminStaffAttendanceController::class, 'show'])
            ->name('staff.show');
        Route::post('/attendance/{id}', [AdminStaffAttendanceController::class, 'update'])
            ->name('staff.update');
        Route::get('/stamp_correction_request/list', [AdminAttendanceRequestController::class, 'index'])
            ->name('attendance.request.index');
        Route::get('/stamp_correction_request/approve/{id}', [AdminAttendanceRequestController::class, 'approve'])
            ->name('attendance.request.approve');
        Route::post('/stamp_correction_request/approve/{id}', [AdminAttendanceRequestController::class, 'processApproval'])
            ->name('attendance.request.process');
    });

// Admin approval routes without prefix (to match the URL in the blade file)
Route::middleware(['auth','role:admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{id}', [AdminAttendanceRequestController::class, 'approve'])
        ->name('admin.attendance.request.approve.alt');
    Route::post('/stamp_correction_request/approve/{id}', [AdminAttendanceRequestController::class, 'processApproval'])
        ->name('admin.attendance.request.process.alt');
});