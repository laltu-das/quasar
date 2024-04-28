<?php

use Illuminate\Support\Facades\Route;
use Laltu\Quasar\Http\Controllers\InstallationController;

Route::group(['prefix' => 'install', 'middleware' => ['web']], function () {

    Route::get('/', [InstallationController::class, 'gettingStarted'])->name('install.getting-started');
    Route::get('server-requirements', [InstallationController::class, 'showServerRequirements'])->name('install.server-requirements');
    Route::get('folder-permissions', [InstallationController::class, 'showFolderPermissions'])->name('install.folder-permissions');
    Route::get('environment-variables', [InstallationController::class, 'showEnvironmentVariables'])->name('install.environment-variables');

    Route::get('envato-license', [InstallationController::class, 'showEnvatoLicense'])->name('install.envato-license');
    Route::post('envato-license', [InstallationController::class, 'submitEnvatoLicense'])->name('install.envato-license.submit');

    Route::post('installation-progress', [InstallationController::class, 'installationProgress'])->name('install.installation-progress');
});
