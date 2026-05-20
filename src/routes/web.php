<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use UsinaTech\CEPWebservice\CEPWebserviceController;

Route::prefix('cepwebservice')
    ->name('cepwebservice.')
    ->group(function (): void {
        Route::get('/cep/{cep}', [CEPWebserviceController::class, 'cep'])->name('cep');
        Route::get('/search/{q}', [CEPWebserviceController::class, 'search'])->name('search');
        Route::get('/latlng/{latlng}', [CEPWebserviceController::class, 'latlng'])->name('latlng');
        Route::get('/slatlng/{latlng}', [CEPWebserviceController::class, 'slatlng'])->name('slatlng');
        Route::get('/glatlng/{latlng}', [CEPWebserviceController::class, 'glatlng'])->name('glatlng');
    });
