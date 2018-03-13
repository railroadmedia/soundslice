<?php

use Illuminate\Support\Facades\Route;
use Railroad\Soundslice\Controllers\SoundsliceJsonController;

Route::prefix('soundslice')->group(function () {

    Route::put(
        'create',
        SoundsliceJsonController::class . '@createScore'
    )->name('soundslice.create');

    Route::get(
        'get/{slug}',
        SoundsliceJsonController::class . '@get'
    )->name('soundslice.get');

    Route::get(
        'list',
        SoundsliceJsonController::class . '@list'
    )->name('soundslice.list');

    Route::delete(
        'delete',
        SoundsliceJsonController::class . '@delete'
    )->name('soundslice.delete');

    Route::post(
        'move',
        SoundsliceJsonController::class . '@move'
    )->name('soundslice.move');

    Route::prefix('folder')->group(function(){
        Route::put(
            'create',
            SoundsliceJsonController::class . '@createFolder'
        )->name('soundslice.folder.create');

        Route::delete(
            'delete',
            SoundsliceJsonController::class . '@deleteFolder'
        )->name('soundslice.folder.delete');
    });
    Route::put(
        'notation',
        SoundsliceJsonController::class . '@createNotation'
    )->name('soundslice.notation.upload');
});