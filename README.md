# CEPWebservice
CEP Webservice API REST Laravel Package

Extraia o *database/cepwebservice.sqlite.zip* na pasta do seu projeto:  
**nomedoprojeto/database/**

Acrescente em seu arquivo *.env* a variável de ambiemte abaixo:  
**SQLITE_DB_DATABASE="../database/cepwebservice.sqlite"**

Editar o trecho do arquivo *config/database.php*:  
'sqlite' => [  
            'driver' => 'sqlite',  
            'url' => env('DATABASE_URL'),  
            **'database' => env('DB_DATABASE', database_path('database.sqlite')),**  
            'prefix' => '',  
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),  
        ],  

Para:  
        'sqlite' => [  
            'driver' => 'sqlite',  
            'url' => env('SQLITE_DATABASE_URL'),  
            **'database' => env('SQLITE_DB_DATABASE'),**  
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),  
        ],  
 
