<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TransactionController;
use Illuminate\Auth\Events\Verified;
use Illuminate\Types\Relations\Role;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])
->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('item', ItemController::class);
    Route::resource('transaction', TransactionController::class);

    Route::prefix('cart')->group(function () {
        Route::post('{item}/add', [TransactionController::class, 'addCart'])->name('cart.add');
        Route::post('{item}/reduce', [TransactionController::class, 'reduceCart'])->name('cart.reduce');
    });
});

require __DIR__ . '/auth.php';
