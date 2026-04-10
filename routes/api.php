<?php

use App\Http\Controllers\Api\GenderizeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['as' => 'genderize.'], function () {
    Route::get('/classify', [GenderizeController::class, 'classify'])->name('classify');
});
