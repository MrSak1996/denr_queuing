<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ClientController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render(component: 'Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/client/index', [ClientController::class, 'index'])->name('client.index');




require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
