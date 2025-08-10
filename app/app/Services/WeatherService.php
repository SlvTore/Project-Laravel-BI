<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private Client $client;
    private ?string $apiKey;
    private string $baseUrl = 'https://api.weatherapi.com/v1/current.json';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
        $this->apiKey = env('WEATHER_API_KEY');
    }

    public function getCurrent(?string $city = null): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $queryCity = $city ?: env('WEATHER_DEFAULT_CITY', 'Jakarta');

        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'key' => $this->apiKey,
                    'q' => $queryCity,
                    'aqi' => 'no'
                ]
            ]);

            $data = json_decode((string)$response->getBody(), true);
            if (!is_array($data)) return null;

            return [
                'city' => $data['location']['name'] ?? $queryCity,
                'temp' => round($data['current']['temp_c'] ?? 0),
                'desc' => $data['current']['condition']['text'] ?? 'Unknown',
                'icon' => isset($data['current']['condition']['icon'])
                    ? 'https:' . $data['current']['condition']['icon']
                    : null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Weather fetch failed', ['err' => $e->getMessage()]);
            return null;
        }
    }
}
