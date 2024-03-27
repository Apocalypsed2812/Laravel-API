<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware;

//Product
Route::get('get-all-product', [AdminController::class, 'getAllProduct'])->name('getAllProduct');
Route::get('get-product-by-id', [AdminController::class, 'getProductById'])->name('getProductById');
Route::post('add-product', [AdminController::class, 'addProduct'])->name('addProduct');
Route::post('delete-product', [AdminController::class, 'deleteProduct'])->name('deleteProduct');
Route::post('update-product', [AdminController::class, 'updateProduct'])->name('updateProduct');

//User
Route::get('get-all-user', [AdminController::class, 'getAllUser'])->name('getAllUser');
Route::get('get-user-by-id', [AdminController::class, 'getUserById'])->name('getUserById');

//Google Authenticator
Route::post('show-qrcode', [AuthController::class, 'showQRCode'])->name('showQRCode');
Route::post('reset-qrcode', [AuthController::class, 'resetQRCode'])->name('resetQRCode');
Route::post('confirm-otp', [AuthController::class, 'confirmOTP'])->name('confirmOTP');

