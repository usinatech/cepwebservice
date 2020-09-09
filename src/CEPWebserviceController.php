<?php

namespace UsinaTech\CEPWebservice;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CEPWebserviceController extends Controller
{
    public function cep(Request $request, $cep) {
        
        $db = DB::connection('sqlite');

        $cep =$db->table('log')
        ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
        ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
        ->select('log.cep','log.logradouro','bairro.bairro','cidade.cidade',
        'log.estado','log.latitude','log.longitude',
        DB::raw(" 'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps")
        )
        ->where('log.cep',$cep)
        ->limit(1)
        ->get();

        return response($cep,200);

    }

    public function search($q) 
    {
        $db = DB::connection('sqlite');

        $logradouro = $db->table('log')
        ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
        ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
        ->join('log_complemento', 'log_complemento.cep', '=', 'log.cep')
        ->select('log.cep','log.logradouro','bairro.bairro','cidade.cidade',
        'log.estado', 'log_complemento.complemento', 'log.latitude','log.longitude',
        DB::raw(" 'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps")
        )
        ->where('log.logradouro','LIKE','%'.$q.'%')
        ->limit(20)
        ->get();

        return response($logradouro,200);
    }

    public function latlng($latlng) 
    {
        $radius=20;

        $latlngArray=explode(',',$latlng);

        $latitude=$latlngArray[0];
        $longitude=$latlngArray[1];

        $db = DB::connection('sqlite');
        
        DB::connection('sqlite')->getPdo()->sqliteCreateFunction('ACOS', 'acos', 1);
        DB::connection('sqlite')->getPdo()->sqliteCreateFunction('COS', 'cos', 1);
        DB::connection('sqlite')->getPdo()->sqliteCreateFunction('RADIANS', 'deg2rad', 1);
        DB::connection('sqlite')->getPdo()->sqliteCreateFunction('SIN', 'sin', 1);

        $cep = $db->table('log')
        ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
        ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
        ->select('log.cep','log.logradouro','bairro.bairro','cidade.cidade','log.estado','log.latitude','log.longitude',
         DB::raw(" 'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps"),
         DB::raw("( 637100 * ACOS( COS( radians($latitude) ) *
         COS( RADIANS( log.latitude ) )
         * COS( RADIANS( log.longitude ) - RADIANS($longitude)
         ) + SIN( RADIANS($latitude) ) *
         SIN( RADIANS( log.latitude ) ) )
         ) AS distancia")
         )
        ->where("distancia", "<", $radius)
        ->orderBy('distancia','ASC')
        ->limit(20)
        ->get();


        return response($cep,200);
    }

    public function slatlng($latlng) 
    {
        $latlngArray=explode(',',$latlng);

        $latitude=$latlngArray[0];
        $longitude=$latlngArray[1];

        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=".$latitude."&lon=".$longitude."&zoom=18";
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_REFERER, env('APP_URL'));

        curl_setopt($ch, CURLOPT_URL,$url);
        $json=curl_exec($ch);
        
        curl_close($ch);
        
        $result = json_decode($json,true);
        
        return response($result,200);
    }    
    
    public function glatlng($latlng) 
    {
        $latlngArray=explode(',',$latlng);

        $latitude=$latlngArray[0];
        $longitude=$latlngArray[1];

        $db = DB::connection('sqlite');

        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$latitude.",".$longitude."&location_type=ROOFTOP&result_type=street_address&key=".env('GOOGLE_MAPS_API_KEY');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_URL,$url);
        $json=curl_exec($ch);
        
        curl_close($ch);
        
        $result = json_decode($json,true);

        if ($result['status']=="OK") {

            $response['cep']=str_replace("-","",$result['results'][0]['address_components'][6]['long_name']);
            $response['logradouro']=$result['results'][0]['address_components'][1]['long_name'];
            $response['bairro']=$result['results'][0]['address_components'][2]['long_name'];
            $response['cidade']=$result['results'][0]['address_components'][3]['long_name'];
            $response['estado']=$result['results'][0]['address_components'][4]['short_name'];
            $response['latitude']=sprintf("%.7f",$result['results'][0]['geometry']['location']['lat']);
            $response['longitude']=sprintf("%.7f",$result['results'][0]['geometry']['location']['lng']);

            $response['maps']="https://www.google.com/maps/search/".$response['latitude'].",".$response['longitude'];
            
            
            $response['updated'] = $db->table('log')
              ->where('cep', $response['cep'])
              ->where('logradouro', $response['logradouro'])
              ->update(['latitude' => $response['latitude'], 'longitude' => $response['longitude'] ]);
            
            
            $response['return']=$result;

            return response($response,200);

        } else {

            $error['message']='Not Found';
            return response($error, 404);

        }
        
    }      
}