<?php

use Illuminate\Support\Facades\Route;
use Railroad\Soundslice\Controllers\SoundsliceController;

Route::prefix('soundslice')->group(function () {

    Route::put(
        'create',
        SoundsliceController::class . '@create'
    )->name('soundslice.create');

    Route::get(
        'get/{slug}',
        SoundsliceController::class . '@get'
    )->name('soundslice.get');

    Route::get(
        'list',
        SoundsliceController::class . '@list'
    )->name('soundslice.list');

    Route::delete(
        'delete',
        SoundsliceController::class . '@delete'
    )->name('soundslice.delete');

    Route::post(
        'move',
        SoundsliceController::class . '@move'
    )->name('soundslice.move');

    Route::prefix('folder')->group(function(){
        Route::put(
            'create',
            SoundsliceController::class . '@createFolder'
        )->name('soundslice.folder.create');

        Route::delete(
            'delete',
            SoundsliceController::class . '@deleteFolder'
        )->name('soundslice.folder.delete');
    });
    Route::put(
        'notation',
        SoundsliceController::class . '@createNotation'
    )->name('soundslice.notation.upload');
});