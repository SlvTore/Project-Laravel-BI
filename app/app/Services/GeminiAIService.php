<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    private $client;
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

    public function __construct()
    {
    $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    $this->apiKey = env('GEMINI_API_KEY');
    // Allow overriding model from ENV
    $model = env('GEMINI_MODEL', 'gemini-1.5-flash-latest');
    $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    public function generateBusinessInsight($prompt, $context = [])
    {
        try {
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'error' => 'API key not configured'
                ];
            }

            $systemPrompt = $this->buildSystemPrompt($context);
            $fullPrompt = $systemPrompt . "\n\nUser Question: " . $prompt;

            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ]
                ]
            ];

            Log::info('Making Gemini API request', [
                'url' => $this->baseUrl . '?key=' . substr($this->apiKey, 0, 10) . '...',
                'prompt_length' => strlen($fullPrompt)
            ]);

            $response = $this->client->post($this->baseUrl . '?key=' . $this->apiKey, [
                'json' => $requestBody,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            Log::info('Gemini API response received', [
                'status_code' => $response->getStatusCode(),
                'has_candidates' => isset($body['candidates'])
            ]);

            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'success' => true,
                    'response' => $body['candidates'][0]['content']['parts'][0]['text']
                ];
            }

            if (isset($body['error'])) {
                Log::error('Gemini API returned error', ['error' => $body['error']]);
                return [
                    'success' => false,
                    'error' => $body['error']['message'] ?? 'API returned an error'
                ];
            }

            return [
                'success' => false,
                'error' => 'No valid response generated'
            ];

        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';

            Log::error('Gemini API Request Error', [
                'message' => $errorMessage,
                'status_code' => $statusCode,
                'response_body' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            if ($statusCode == 403) {
                return [
                    'success' => false,
                    'error' => 'API key tidak valid atau tidak memiliki akses'
                ];
            } elseif ($statusCode == 429) {
                return [
                    'success' => false,
                    'error' => 'Terlalu banyak permintaan. Silakan coba lagi nanti'
                ];
            } elseif ($statusCode == 404) {
                return [
                    'success' => false,
                    'error' => 'Model API tidak ditemukan'
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal terhubung ke layanan AI: ' . $statusCode
            ];
        } catch (\Exception $e) {
            Log::error('General AI Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan sistem'
            ];
        }
    }

    private function buildSystemPrompt($context)
    {
        $metricName = $context['metric_name'] ?? 'Unknown Metric';
        $businessName = $context['business_name'] ?? 'Business';
        $recentData = $context['recent_data'] ?? [];
        $statistics = $context['statistics'] ?? [];

        $prompt = "You are a Business Intelligence AI Assistant for '{$businessName}'.

CONTEXT:
- Current Metric: {$metricName}
- Your role: Provide data-driven insights and decision-making recommendations
- Focus: Help interpret trends, identify opportunities, and suggest actionable business strategies

RECENT DATA SUMMARY:";

        if (!empty($recentData)) {
            $prompt .= "\n" . json_encode($recentData, JSON_PRETTY_PRINT);
        }

        if (!empty($statistics)) {
            $prompt .= "\n\nSTATISTICS:\n" . json_encode($statistics, JSON_PRETTY_PRINT);
        }

        $prompt .= "\n\nGUIDELINES:
1. Always provide specific, actionable recommendations
2. Reference the actual data when making suggestions
3. Consider industry best practices for {$metricName}
4. Be concise but comprehensive
5. Focus on business decision-making impact
6. Use Indonesian language for responses
7. Format responses with clear structure (bullets, numbers when appropriate)

RESPONSE STYLE:
- Start with key insights
- Provide 2-3 specific recommendations
- Include potential risks or considerations
- End with next steps or questions to explore";

        return $prompt;
    }
}
