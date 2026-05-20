# CEP Webservice

Pacote Laravel para consulta de CEPs a partir de uma base SQLite local, com endpoints HTTP para busca por CEP, logradouro e coordenadas.

## Requisitos

- PHP 7.4+
- Laravel 8+
- Extensão `pdo_sqlite`

## Instalação

Dentro do projeto Laravel que vai consumir o pacote:

```bash
composer require usinatech/cepwebservice
php artisan vendor:publish --tag=cepwebservice-config
php artisan vendor:publish --tag=cepwebservice-database
unzip database/cepwebservice.sqlite.zip -d database/
```

## Configuração

O pacote passa a registrar automaticamente a conexão `sqliteCEPWebservice`.  
Se quiser customizar o caminho do banco ou timeouts, ajuste o arquivo `config/cepwebservice.php` publicado.

Variáveis opcionais de ambiente:

```env
CEPWEBSERVICE_DB_PATH=database/cepwebservice.sqlite
CEPWEBSERVICE_RADIUS_KM=20
CEPWEBSERVICE_HTTP_TIMEOUT=10
CEPWEBSERVICE_HTTP_CONNECT_TIMEOUT=5
CEPWEBSERVICE_USER_AGENT="${APP_NAME} CEPWebservice"
CEPWEBSERVICE_NOMINATIM_URL=https://nominatim.openstreetmap.org
GOOGLE_MAPS_API_KEY=
CEPWEBSERVICE_GOOGLE_SYNC_COORDINATES=false
```

`SQLITE_DB_DATABASE` continua sendo aceito por compatibilidade com instalações antigas.

## Endpoints

### Buscar CEP

```bash
curl --location --request GET 'http://localhost:8000/cepwebservice/cep/51110000'
```

Resposta:

```json
[
  {
    "cep": "51110000",
    "logradouro": "Avenida Antônio de Góes",
    "bairro": "Pina",
    "cidade": "Recife",
    "estado": "PE",
    "latitude": "-8.0851919",
    "longitude": "-34.8869746",
    "maps": "https://www.google.com/maps/search/-8.0851919,-34.8869746"
  }
]
```

### Buscar por logradouro

```bash
curl --location --request GET 'http://localhost:8000/cepwebservice/search/Antônio%20de%20Góes'
```

### Buscar CEPs próximos por latitude/longitude

```bash
curl --location --request GET 'http://localhost:8000/cepwebservice/latlng/-8.0851919,-34.8869746'
```

### Reverse geocoding com OpenStreetMap/Nominatim

```bash
curl --location --request GET 'http://localhost:8000/cepwebservice/slatlng/-8.0851919,-34.8869746'
```

### Reverse geocoding com Google Maps

```bash
curl --location --request GET 'http://localhost:8000/cepwebservice/glatlng/-8.0851919,-34.8869746'
```

Por padrão, esse endpoint **não** atualiza o banco local.  
Para habilitar a sincronização automática de latitude/longitude, configure:

```env
CEPWEBSERVICE_GOOGLE_SYNC_COORDINATES=true
```

## Observações

- Os endpoints retornam JSON e validam CEP e coordenadas antes da consulta.
- A busca por logradouro exige pelo menos 3 caracteres.
- O banco SQLite distribuído no pacote precisa ser descompactado antes do uso.
