<?php

use Illuminate\Support\Facades\Route;
use Laltu\Quasar\Http\Controllers\IndexController;
use Laltu\Quasar\Http\Controllers\InstallationController;

Route::middleware('api')->prefix('api/install/')->group(function () {

    Route::get('/server-requirements', [InstallationController::class, 'showServerRequirements']);
    Route::get('/folder-permissions', [InstallationController::class, 'showFolderPermissions']);
    Route::get('/environment-variables', [InstallationController::class, 'showEnvironmentVariables']);

    Route::post('/envato-license', [InstallationController::class, 'submitEnvatoLicense']);
    Route::post('/download-project', [InstallationController::class, 'downloadProject']);
});

Route::get('install/{view?}', IndexController::class)->where('view', '(.*)');