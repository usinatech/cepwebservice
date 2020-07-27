<?php
    Route::get('/cepwebservice/cep/{cep}', '\UsinaHUB\CEPWebservice\CEPWebserviceController@cep');
    Route::get('/cepwebservice/search/{q}', '\UsinaHUB\CEPWebservice\CEPWebserviceController@search');
    Route::get('/cepwebservice/latlng/{latlng}', '\UsinaHUB\CEPWebservice\CEPWebserviceController@latlng');
    Route::get('/cepwebservice/slatlng/{latlng}', '\UsinaHUB\CEPWebservice\CEPWebserviceController@slatlng');
    Route::get('/cepwebservice/glatlng/{latlng}', '\UsinaHUB\CEPWebservice\CEPWebserviceController@glatlng');
?>