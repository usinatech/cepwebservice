<?php

namespace UsinaHUB\CEPWebservice;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CEPWebserviceController extends Controller
{
    public function cep(Request $request, $cep) {
        
        $db = \DB::connection('sqlite');

        $cep =$db->table('log')
        ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
        ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
        ->select('log.cep','log.logradouro','bairro.bairro','cidade.cidade',
        'log.estado','log.latitude','log.longitude'
        )
        ->where('log.cep',$cep)
        ->limit(1)
        ->get();

        return response($cep,200);
        return $cep;
    }
}