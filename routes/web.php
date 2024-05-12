<?php

use Illuminate\Support\Facades\Route;
use Laltu\Quasar\Http\Controllers\FilepondController;

Route::group(['middleware' => config('quasar.filepond.middleware', ['web', 'auth'])], function () {
    Route::post('/filepond/process', [FilepondController::class, 'process']);
    Route::get('/filepond/fetch', [FilepondController::class, 'fetch']);
    Route::get('/filepond/restore/{id}', [FilepondController::class, 'restore']);
    Route::delete('/filepond/revert', [FilepondController::class, 'revert']);
    Route::delete('/filepond/remove', [FilepondController::class, 'remove']);
    Route::get('/filepond/load/{id}', [FilepondController::class, 'load']);
});