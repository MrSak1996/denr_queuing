<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CounterController;

use App\http\Middleware\EnsureTokenIsValid;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::middleware('auth:sanctum')->get('/clients', [ClientController::class, 'get_client']);
Route::get('/clients', [ClientController::class, 'get_client']); // 
Route::get('/counter-opts', [ClientController::class, 'get_counter']); // 
Route::post('/update-client-status', [ClientController::class, 'updateStatus']); // 
Route::post('/set_client_priority', [ClientController::class, 'set_client_priority']); // 
Route::post('/save_queue_logs', [ClientController::class, 'save_queue_logs']);
Route::post('/transfer-client', [ClientController::class, 'transfer_client']);
Route::post('/recall', [ClientController::class, 'recall']);
Route::post('/update-client-transaction', [CounterController::class, 'update_client_transaction']);


Route::get('/current-clients', [CounterController::class, 'current_clients']);
Route::get('/queue_list', [CounterController::class, 'queue_list']);
