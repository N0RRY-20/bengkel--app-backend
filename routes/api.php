<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\auth\ForgotPasswordController;
use App\Http\Controllers\auth\ResetPasswordController;
use App\Http\Controllers\auth\VerificationController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceMasterController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES (No Auth Required)
// ============================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Password Reset
Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'index'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'store']);

// Email Verification (signed URL)
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

// ============================================
// PROTECTED ROUTES (Auth Required)
// ============================================
Route::middleware('auth:sanctum')->group(function () {

    // --- User Info ---
    Route::get('/user', fn(Request $request) => $request->user());
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Email Verification ---
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/email/verification-status', [VerificationController::class, 'status']);

    // ============================================
    // KASIR ROUTES (kasir, admin, owner)
    // ============================================
    Route::middleware('role:kasir,admin,owner')->group(function () {
        // Work Orders
        Route::get('/work-orders', [WorkOrderController::class, 'index']);
        Route::post('/work-orders', [WorkOrderController::class, 'store']);
        Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show']);
        Route::post('/work-orders/{workOrder}/services', [WorkOrderController::class, 'addService']);
        Route::delete('/work-orders/{workOrder}/services/{service}', [WorkOrderController::class, 'removeService']);
        Route::post('/work-orders/{workOrder}/parts', [WorkOrderController::class, 'addPart']);
        Route::delete('/work-orders/{workOrder}/parts/{part}', [WorkOrderController::class, 'removePart']);
        Route::patch('/work-orders/{workOrder}/finish', [WorkOrderController::class, 'finish']);
        Route::post('/work-orders/{workOrder}/pay', [WorkOrderController::class, 'pay']);

        // Sales (Sparepart tanpa motor)
        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::post('/sales/{sale}/pay', [SaleController::class, 'pay']);

        // Products & Services (read only for kasir)
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/services', [ServiceMasterController::class, 'index']);
        Route::get('/mechanics', [MechanicController::class, 'index']);
    });

    // ============================================
    // MEKANIK ROUTES (Read Only)
    // ============================================
    Route::middleware('role:mekanik')->group(function () {
        Route::get('/my-work-orders', [WorkOrderController::class, 'index']);
        Route::get('/my-earnings', [MechanicController::class, 'myEarnings']);
    });

    // ============================================
    // ADMIN ROUTES (admin, owner)
    // ============================================
    Route::middleware('role:admin,owner')->group(function () {
        // Products CRUD
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::patch('/products/{product}/stock', [ProductController::class, 'updateStock']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Services CRUD
        Route::post('/services', [ServiceMasterController::class, 'store']);
        Route::get('/services/{serviceMaster}', [ServiceMasterController::class, 'show']);
        Route::put('/services/{serviceMaster}', [ServiceMasterController::class, 'update']);
        Route::delete('/services/{serviceMaster}', [ServiceMasterController::class, 'destroy']);

        // Mechanics CRUD
        Route::post('/mechanics', [MechanicController::class, 'store']);
        Route::get('/mechanics/{mechanic}', [MechanicController::class, 'show']);
        Route::put('/mechanics/{mechanic}', [MechanicController::class, 'update']);
        Route::get('/mechanics/{mechanic}/earnings', [MechanicController::class, 'earnings']);
    });

    // ============================================
    // USER MANAGEMENT (Owner/Admin Only)
    // ============================================
    Route::middleware('role:owner,admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/roles-summary', [UserController::class, 'rolesSummary']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // ============================================
    // OWNER ONLY ROUTES
    // ============================================
    Route::middleware('role:owner')->group(function () {
        // Corrections
        Route::get('/corrections', [CorrectionController::class, 'index']);
        Route::get('/corrections/{correction}', [CorrectionController::class, 'show']);
        Route::post('/corrections', [CorrectionController::class, 'store']);

        // Reports
        Route::get('/reports/daily', [ReportController::class, 'daily']);
        Route::get('/reports/monthly', [ReportController::class, 'monthly']);
        Route::get('/reports/mechanics', [ReportController::class, 'mechanics']);
        Route::get('/reports/activity-logs', [ReportController::class, 'activityLogs']);

        // Payments list
        Route::get('/payments', [ReportController::class, 'payments']);
    });

    // ============================================
    // DASHBOARD ROUTES (Per Role)
    // ============================================
    Route::get('/dashboard/owner', [DashboardController::class, 'owner'])
        ->middleware('role:owner');

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
        ->middleware('role:admin,owner');

    Route::get('/dashboard/kasir', [DashboardController::class, 'kasir'])
        ->middleware('role:kasir,admin,owner');

    Route::get('/dashboard/mekanik', [DashboardController::class, 'mekanik'])
        ->middleware('role:mekanik');
});
