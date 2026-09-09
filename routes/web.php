<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return redirect(Filament::getPanel('admin')->getLoginUrl());
})->name('login');
