<?php

use Illuminate\Support\Facades\Route;
use Laltu\Quasar\Http\Controllers\FilepondController;

Route::group(['middleware' => config('quasar.filepond.middleware', ['web', 'auth'])], function () {
    Route::post(config('quasar.filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'process'])->name('filepond-process');
    Route::patch(config('quasar.filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'patch'])->name('filepond-patch');
    Route::get(config('quasar.filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'head'])->name('filepond-head');
    Route::delete(config('quasar.filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'revert'])->name('filepond-revert');
});