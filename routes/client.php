<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

    Route::middleware('auth')->group(function () {

Route::get('/client/index', [ClientController::class, 'index'])->name('client.index');
})
?>