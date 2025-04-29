<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('clients.counter.{counterId}', function ($user, $counterId) {
    return true; // Add your authorization logic here
});
