<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class NewsService
{
    private Client $client;
    private ?string $apiKey;
    private string $baseUrl = 'https://newsapi.org/v2/top-headlines';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
        $this->apiKey = env('NEWS_API_KEY');
    }

    public function getBusinessNews(string $country = 'id', string $q = 'UMKM SME bisnis usaha') : array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $resp = $this->client->get($this->baseUrl, [
                'query' => [
                    'apiKey' => $this->apiKey,
                    'country' => $country,
                    'category' => 'business',
                    'q' => $q,
                    'pageSize' => 10,
                ]
            ]);

            $data = json_decode((string)$resp->getBody(), true);
            $articles = $data['articles'] ?? [];

            return array_values(array_filter(array_map(function($a){
                return [
                    'title' => $a['title'] ?? null,
                    'desc' => $a['description'] ?? null,
                    'url' => $a['url'] ?? null,
                    'image' => $a['urlToImage'] ?? null,
                    'source' => $a['source']['name'] ?? null,
                    'publishedAt' => $a['publishedAt'] ?? null,
                ];
            }, $articles), function($x){ return !empty($x['title']) && !empty($x['url']); }));
        } catch (\Throwable $e) {
            Log::warning('News fetch failed', ['err' => $e->getMessage()]);
            return [];
        }
    }
}
