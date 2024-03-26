<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware;

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

Route::get('/logout',  [AuthController::class, 'logout'])->name('logout');

Route::get('/logout',  [AuthController::class, 'logout'])->name('logout');

Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/otp', [AuthController::class, 'checkOTP']);

Route::post('/unactived-otp', [AuthController::class, 'unactivedOTP']);

// Admin
Route::get('/home', [AdminController::class, 'renderHome'])->middleware('check.login');

Route::prefix('admin')->group(function(){
    //Product
    Route::get('get-all-product', [AdminController::class, 'getAllProduct'])->name('getAllProduct');
    Route::get('get-product-by-id', [AdminController::class, 'getProductById'])->name('getProductById');
    Route::post('add-product', [AdminController::class, 'addProduct'])->name('addProduct');
    Route::post('delete-product', [AdminController::class, 'deleteProduct'])->name('deleteProduct');
    Route::post('update-product', [AdminController::class, 'updateProduct'])->name('updateProduct');
    //User
    Route::get('get-all-user', [AdminController::class, 'getAllUser'])->name('getAllUser');
    Route::get('get-user-by-id', [AdminController::class, 'getUserById'])->name('getUserById');
});

// Test API
Route::post('/api', [AuthController::class, 'api']);
