<?php

use Illuminate\Support\Facades\Route;
use UsinaTech\CEPWebservice\CEPWebserviceController;

Route::get('/cepwebservice/cep/{cep}', [CEPWebserviceController::class, 'cep']);
Route::get('/cepwebservice/search/{q}', [CEPWebserviceController::class, 'search']);
Route::get('/cepwebservice/latlng/{latlng}', [CEPWebserviceController::class, 'latlng']);
Route::get('/cepwebservice/slatlng/{latlng}', [CEPWebserviceController::class, 'slatlng']);
Route::get('/cepwebservice/glatlng/{latlng}', [CEPWebserviceController::class, 'glatlng']);
