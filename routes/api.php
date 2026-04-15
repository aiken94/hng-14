<?php

use App\Http\Controllers\Api\GenderizeController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['as' => 'genderize.'], function () {
    Route::get('/classify', [GenderizeController::class, 'classify'])->name('classify');
    Route::group(['prefix' => 'profiles', 'as' => 'profiles.'], function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::post('/', [ProfileController::class, 'index'])->name('store');
        Route::get('/{id}', [ProfileController::class, 'index'])->name('show');
        Route::delete('/{id}', [ProfileController::class, 'destroy'])->name('destroy');
    });
});
