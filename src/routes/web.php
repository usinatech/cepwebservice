<?php
    Route::get('/cepwebservice/cep/{cep}', '\UsinaTech\CEPWebservice\CEPWebserviceController@cep');
    Route::get('/cepwebservice/search/{q}', '\UsinaTech\CEPWebservice\CEPWebserviceController@search');
    Route::get('/cepwebservice/latlng/{latlng}', '\UsinaTech\CEPWebservice\CEPWebserviceController@latlng');
    Route::get('/cepwebservice/slatlng/{latlng}', '\UsinaTech\CEPWebservice\CEPWebserviceController@slatlng');
    Route::get('/cepwebservice/glatlng/{latlng}', '\UsinaTech\CEPWebservice\CEPWebserviceController@glatlng');
?>