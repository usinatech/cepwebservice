<?php

declare(strict_types=1);

namespace UsinaTech\CEPWebservice;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CEPWebserviceController extends Controller
{
    public function cep(Request $request, string $cep): JsonResponse
    {
        $normalizedCep = $this->normalizeCep($cep);

        if ($normalizedCep === null) {
            return $this->validationError('CEP inválido. Informe um CEP com 8 dígitos.');
        }

        $cacheEnabled = (bool) config('cepwebservice.cache.enabled', true);
        $cacheTtl = (int) config('cepwebservice.cache.ttl', 86400);
        $cacheKey = 'cepwebservice:cep:' . $normalizedCep;

        if ($cacheEnabled) {
            $result = Cache::remember($cacheKey, $cacheTtl, function () use ($normalizedCep) {
                return $this->baseAddressQuery()
                    ->where('log.cep', $normalizedCep)
                    ->limit(1)
                    ->get()
                    ->toArray();
            });
            $result = collect($result);
        } else {
            $result = $this->baseAddressQuery()
                ->where('log.cep', $normalizedCep)
                ->limit(1)
                ->get();
        }

        if ($result->isEmpty()) {
            return $this->notFound('CEP não encontrado.');
        }

        return response()->json($result);
    }

    public function search(string $q): JsonResponse
    {
        $term = trim($q);

        if (mb_strlen($term) < 3) {
            return $this->validationError('Informe pelo menos 3 caracteres para a busca.');
        }

        if (mb_strlen($term) > 100) {
            return $this->validationError('O termo de busca deve ter no máximo 100 caracteres.');
        }

        $cacheEnabled = (bool) config('cepwebservice.cache.enabled', true);
        $cacheTtl = (int) config('cepwebservice.cache.ttl', 86400);
        $cacheKey = 'cepwebservice:search:' . md5($term);

        if ($cacheEnabled) {
            $logradouro = Cache::remember($cacheKey, $cacheTtl, function () use ($term) {
                return $this->connection()
                    ->table('log')
                    ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
                    ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
                    ->leftJoin('log_complemento', 'log_complemento.cep', '=', 'log.cep')
                    ->select(
                        'log.cep',
                        'log.logradouro',
                        'bairro.bairro',
                        'cidade.cidade',
                        'log.estado',
                        'log_complemento.complemento',
                        'log.latitude',
                        'log.longitude'
                    )
                    ->selectRaw("'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps")
                    ->where('log.logradouro', 'LIKE', '%' . $term . '%')
                    ->limit($this->resultLimit())
                    ->get()
                    ->toArray();
            });
        } else {
            $logradouro = $this->connection()
                ->table('log')
                ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
                ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
                ->leftJoin('log_complemento', 'log_complemento.cep', '=', 'log.cep')
                ->select(
                    'log.cep',
                    'log.logradouro',
                    'bairro.bairro',
                    'cidade.cidade',
                    'log.estado',
                    'log_complemento.complemento',
                    'log.latitude',
                    'log.longitude'
                )
                ->selectRaw("'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps")
                ->where('log.logradouro', 'LIKE', '%' . $term . '%')
                ->limit($this->resultLimit())
                ->get();
        }

        return response()->json($logradouro);
    }

    public function latlng(string $latlng): JsonResponse
    {
        $coordinates = $this->parseLatLng($latlng);

        if ($coordinates === null) {
            return $this->validationError('Latitude/longitude inválida. Use o formato "lat,lng".');
        }

        $latitude = $coordinates['latitude'];
        $longitude = $coordinates['longitude'];
        $radiusKm = (float) config('cepwebservice.search.radius_km', 20);

        $cacheEnabled = (bool) config('cepwebservice.cache.enabled', true);
        $cacheTtl = (int) config('cepwebservice.cache.ttl', 86400);
        $cacheKey = 'cepwebservice:latlng:' . md5($latitude . ',' . $longitude . ',' . $radiusKm);

        if ($cacheEnabled) {
            $results = Cache::remember($cacheKey, $cacheTtl, function () use ($latitude, $longitude, $radiusKm) {
                $connection = $this->connection();
                $this->registerSqliteMathFunctions($connection);

                $distanceExpression = '(6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(log.latitude)) * COS(RADIANS(log.longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(log.latitude))))';

                return $connection
                    ->table('log')
                    ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
                    ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
                    ->select(
                        'log.cep',
                        'log.logradouro',
                        'bairro.bairro',
                        'cidade.cidade',
                        'log.estado',
                        'log.latitude',
                        'log.longitude'
                    )
                    ->selectRaw("'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps")
                    ->selectRaw($distanceExpression . ' AS distancia', [$latitude, $longitude, $latitude])
                    ->havingRaw('distancia < ?', [$radiusKm])
                    ->orderBy('distancia')
                    ->limit($this->resultLimit())
                    ->get()
                    ->toArray();
            });
        } else {
            $connection = $this->connection();
            $this->registerSqliteMathFunctions($connection);

            $distanceExpression = '(6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(log.latitude)) * COS(RADIANS(log.longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(log.latitude))))';

            $results = $connection
                ->table('log')
                ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
                ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
                ->select(
                    'log.cep',
                    'log.logradouro',
                    'bairro.bairro',
                    'cidade.cidade',
                    'log.estado',
                    'log.latitude',
                    'log.longitude'
                )
                ->selectRaw("'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps")
                ->selectRaw($distanceExpression . ' AS distancia', [$latitude, $longitude, $latitude])
                ->havingRaw('distancia < ?', [$radiusKm])
                ->orderBy('distancia')
                ->limit($this->resultLimit())
                ->get();
        }

        return response()->json($results);
    }

    public function slatlng(string $latlng): JsonResponse
    {
        $coordinates = $this->parseLatLng($latlng);

        if ($coordinates === null) {
            return $this->validationError('Latitude/longitude inválida. Use o formato "lat,lng".');
        }

        $cacheEnabled = (bool) config('cepwebservice.cache.enabled', true);
        $cacheTtl = (int) config('cepwebservice.cache.ttl', 86400);
        $cacheKey = 'cepwebservice:slatlng:' . md5($coordinates['latitude'] . ',' . $coordinates['longitude']);

        if ($cacheEnabled && Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            if ($cachedData !== null) {
                return response()->json($cachedData);
            }
        }

        $response = Http::acceptJson()
            ->withUserAgent((string) config('cepwebservice.http.user_agent'))
            ->withHeaders([
                'Referer' => (string) config('app.url', ''),
            ])
            ->timeout((int) config('cepwebservice.http.timeout', 10))
            ->connectTimeout((int) config('cepwebservice.http.connect_timeout', 5))
            ->get(rtrim((string) config('cepwebservice.nominatim.base_url'), '/') . '/reverse', [
                'format' => 'json',
                'lat' => $coordinates['latitude'],
                'lon' => $coordinates['longitude'],
                'zoom' => 18,
                'addressdetails' => 1,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Falha ao consultar o serviço de geolocalização.',
            ], 502);
        }

        $data = $response->json();
        if ($cacheEnabled) {
            Cache::put($cacheKey, $data, $cacheTtl);
        }

        return response()->json($data);
    }

    public function glatlng(string $latlng): JsonResponse
    {
        $coordinates = $this->parseLatLng($latlng);

        if ($coordinates === null) {
            return $this->validationError('Latitude/longitude inválida. Use o formato "lat,lng".');
        }

        $cacheEnabled = (bool) config('cepwebservice.cache.enabled', true);
        $cacheTtl = (int) config('cepwebservice.cache.ttl', 86400);
        $cacheKey = 'cepwebservice:glatlng:' . md5($coordinates['latitude'] . ',' . $coordinates['longitude']);

        if ($cacheEnabled && Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            if ($cachedData !== null) {
                return response()->json($cachedData);
            }
        }

        $apiKey = (string) config('cepwebservice.google.api_key');

        if ($apiKey === '') {
            return response()->json([
                'message' => 'GOOGLE_MAPS_API_KEY não configurada.',
            ], 503);
        }

        $response = Http::acceptJson()
            ->timeout((int) config('cepwebservice.http.timeout', 10))
            ->connectTimeout((int) config('cepwebservice.http.connect_timeout', 5))
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $coordinates['latitude'] . ',' . $coordinates['longitude'],
                'location_type' => 'ROOFTOP',
                'result_type' => 'street_address',
                'key' => $apiKey,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Falha ao consultar o Google Maps.',
            ], 502);
        }

        $result = $response->json();
        $status = $result['status'] ?? null;

        if ($status !== 'OK' || empty($result['results'][0])) {
            return $this->notFound('Endereço não encontrado.');
        }

        $googleAddress = $result['results'][0];
        $components = $googleAddress['address_components'] ?? [];

        $cep = $this->normalizeCep((string) $this->addressComponent($components, 'postal_code'));
        $logradouro = $this->addressComponent($components, 'route');
        $bairro = $this->addressComponent($components, 'sublocality_level_1')
            ?: $this->addressComponent($components, 'sublocality')
            ?: $this->addressComponent($components, 'neighborhood');
        $cidade = $this->addressComponent($components, 'administrative_area_level_2')
            ?: $this->addressComponent($components, 'locality');
        $estado = $this->addressComponent($components, 'administrative_area_level_1', 'short_name');
        $latitude = sprintf('%.7f', (float) ($googleAddress['geometry']['location']['lat'] ?? 0));
        $longitude = sprintf('%.7f', (float) ($googleAddress['geometry']['location']['lng'] ?? 0));

        $payload = [
            'cep' => $cep,
            'logradouro' => $logradouro,
            'bairro' => $bairro,
            'cidade' => $cidade,
            'estado' => $estado,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'maps' => 'https://www.google.com/maps/search/' . $latitude . ',' . $longitude,
            'updated' => false,
        ];

        if ((bool) config('cepwebservice.google.sync_coordinates', false) && $cep !== null) {
            $updated = $this->connection()
                ->table('log')
                ->where('cep', $cep)
                ->when($logradouro !== null && $logradouro !== '', function ($query) use ($logradouro) {
                    $query->where('logradouro', $logradouro);
                })
                ->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

            $payload['updated'] = $updated > 0;
        }

        if ($cacheEnabled) {
            Cache::put($cacheKey, $payload, $cacheTtl);
        }

        return response()->json($payload);
    }

    private function connection(): ConnectionInterface
    {
        return DB::connection('sqliteCEPWebservice');
    }

    private function baseAddressQuery()
    {
        return $this->connection()
            ->table('log')
            ->join('bairro', 'bairro.id', '=', 'log.bairro_id')
            ->join('cidade', 'cidade.id', '=', 'log.cidade_id')
            ->select(
                'log.cep',
                'log.logradouro',
                'bairro.bairro',
                'cidade.cidade',
                'log.estado',
                'log.latitude',
                'log.longitude'
            )
            ->selectRaw("'https://www.google.com/maps/search/' || log.latitude || ',' || log.longitude AS maps");
    }

    private function normalizeCep(string $cep): ?string
    {
        $normalizedCep = preg_replace('/\D+/', '', $cep);

        if (! is_string($normalizedCep) || strlen($normalizedCep) !== 8) {
            return null;
        }

        return $normalizedCep;
    }

    private function parseLatLng(string $latlng): ?array
    {
        if (strlen($latlng) > 50) {
            return null;
        }

        $parts = array_map('trim', explode(',', $latlng));

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        $latitude = (float) $parts[0];
        $longitude = (float) $parts[1];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    private function addressComponent(array $components, string $type, string $field = 'long_name'): ?string
    {
        foreach ($components as $component) {
            $types = $component['types'] ?? [];

            if (in_array($type, $types, true)) {
                $value = $component[$field] ?? null;

                return is_string($value) && $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function registerSqliteMathFunctions(ConnectionInterface $connection): void
    {
        $pdo = $connection->getPdo();

        if (! method_exists($pdo, 'sqliteCreateFunction')) {
            return;
        }

        $pdo->sqliteCreateFunction('ACOS', 'acos', 1);
        $pdo->sqliteCreateFunction('COS', 'cos', 1);
        $pdo->sqliteCreateFunction('RADIANS', 'deg2rad', 1);
        $pdo->sqliteCreateFunction('SIN', 'sin', 1);
    }

    private function resultLimit(): int
    {
        return max(1, (int) config('cepwebservice.search.limit', 20));
    }

    private function validationError(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 422);
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 404);
    }
}
