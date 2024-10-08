# CEP Webservice
CEP Webservice API REST Laravel Package


Execute os comandos abaixo dentro da pasta do seu projeto:

**composer require usinatech/cepwebservice**

**unzip vendor/usinatech/cepwebservice/database/cepwebservice.sqlite.zip -d database/**



Acrescente em seu arquivo *.env* a variável de ambiemte abaixo:  
**SQLITE_DB_DATABASE="../database/cepwebservice.sqlite"**

Abaixo do trecho do arquivo *config/database.php*:  
```
  'sqlite' => [  
              'driver' => 'sqlite',  
              'url' => env('DATABASE_URL'),  
              'database' => env('DB_DATABASE', database_path('database.sqlite')),  
              'prefix' => '',  
              'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),  
   ],  
```

Incluir: 

```       
  'sqliteCEPWebservice' => [  
              'driver' => 'sqlite',  
              'url' => env('SQLITE_DATABASE_URL'),  
              'database' => env('SQLITE_DB_DATABASE'),  
              'prefix' => '',
              'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),  
   ],  
``` 
##Usando a API

Exemplo de Chamada:  

```       
curl --location --request GET 'http://localhost:8000/cepwebservice/cep/51110000' 
``` 

Retorno:  
```       
[
    {
        "cep": "51110000",
        "logradouro": "Avenida Antônio de Góes",
        "bairro": "Pina",
        "cidade": "Recife",
        "estado": "PE",
        "latitude": "-8.0851919",
        "longitude": "-34.8869746"
    }
]
```  

Se desejar usar o método para busca complementar de latitude e longitude no Google Maps, incluir a chave no seu arquivo *.env*

GOOGLE_MAPS_API_KEY=
