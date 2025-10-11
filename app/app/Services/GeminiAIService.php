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
                Log::warning('Gemini API key not configured');
                return [
                    'success' => false,
                    'error' => 'API key tidak dikonfigurasi. Silakan tambahkan GEMINI_API_KEY di file .env'
                ];
            }

            $systemPrompt = $this->buildSystemPrompt($context);
            $fullPrompt = $systemPrompt . "\n\nPertanyaan User: " . $prompt;

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
                'url' => $this->baseUrl,
                'prompt_length' => strlen($fullPrompt),
                'has_api_key' => !empty($this->apiKey),
                'context_keys' => array_keys($context)
            ]);

            $response = $this->client->post($this->baseUrl . '?key=' . $this->apiKey, [
                'json' => $requestBody,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 60
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
                    'error' => $body['error']['message'] ?? 'API mengembalikan error'
                ];
            }

            return [
                'success' => false,
                'error' => 'Tidak ada respons valid yang dihasilkan'
            ];

        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $responseBody = null;

            if ($e->getResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }

            Log::error('Gemini API Request Error', [
                'message' => $errorMessage,
                'status_code' => $statusCode,
                'response_body' => $responseBody
            ]);

            if ($statusCode == 403) {
                return [
                    'success' => false,
                    'error' => 'API key tidak valid atau tidak memiliki akses. Periksa GEMINI_API_KEY di .env'
                ];
            } elseif ($statusCode == 429) {
                return [
                    'success' => false,
                    'error' => 'Terlalu banyak permintaan. Silakan coba lagi nanti'
                ];
            } elseif ($statusCode == 404) {
                return [
                    'success' => false,
                    'error' => 'Model API tidak ditemukan. Periksa GEMINI_MODEL di .env'
                ];
            } elseif ($statusCode == 400) {
                return [
                    'success' => false,
                    'error' => 'Permintaan tidak valid. API key mungkin salah atau sudah kedaluwarsa'
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal terhubung ke layanan AI (Status: ' . $statusCode . ')'
            ];
        } catch (\Exception $e) {
            Log::error('General AI Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ];
        }
    }

    private function buildSystemPrompt($context)
    {
        $metricName = $context['metric_name'] ?? 'Unknown Metric';
        $businessName = $context['business_name'] ?? 'Business';
        $currentValue = $context['current_value'] ?? 0;
        $previousValue = $context['previous_value'] ?? 0;
        $changePercentage = $context['change_percentage'] ?? 0;
        $statistics = $context['statistics'] ?? [];
        $recentRecords = $context['recent_records'] ?? [];
        $dailyTrend = $context['daily_trend'] ?? [];

        $prompt = "Anda adalah AI Business Intelligence Assistant profesional untuk '{$businessName}'.

KONTEKS BISNIS:
- Metric yang dianalisis: {$metricName}
- Kategori: " . ($context['metric_category'] ?? 'General') . "
- Unit: " . ($context['metric_unit'] ?? '') . "
- Sumber Data: " . ($context['data_source'] ?? 'Data Warehouse') . "
- Periode Analisis: " . ($context['analysis_period'] ?? '30 hari') . "

DATA TERKINI:
- Nilai Sekarang: " . number_format($currentValue, 0, ',', '.') . "
- Nilai Sebelumnya: " . number_format($previousValue, 0, ',', '.') . "
- Perubahan: " . ($changePercentage > 0 ? '+' : '') . number_format($changePercentage, 2) . "%
- Status: " . ($changePercentage > 0 ? 'Meningkat' : ($changePercentage < 0 ? 'Menurun' : 'Stabil')) . "

";

        // Add statistics if available
        if (!empty($statistics)) {
            $prompt .= "STATISTIK:\n";
            foreach ($statistics as $key => $value) {
                if (is_numeric($value)) {
                    $prompt .= "- " . ucwords(str_replace('_', ' ', $key)) . ": " . number_format($value, 2, ',', '.') . "\n";
                } elseif (is_array($value)) {
                    $prompt .= "- " . ucwords(str_replace('_', ' ', $key)) . ": " . json_encode($value) . "\n";
                } else {
                    $prompt .= "- " . ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
                }
            }
            $prompt .= "\n";
        }

        // Add trend data if available
        if (!empty($dailyTrend)) {
            $trendCount = count($dailyTrend);
            $firstValue = $dailyTrend[0]['value'] ?? 0;
            $lastValue = $dailyTrend[$trendCount - 1]['value'] ?? 0;
            $avgValue = array_sum(array_column($dailyTrend, 'value')) / $trendCount;

            $prompt .= "TREN HARIAN (30 hari terakhir):\n";
            $prompt .= "- Total Data Points: {$trendCount} hari\n";
            $prompt .= "- Nilai Awal: " . number_format($firstValue, 0, ',', '.') . "\n";
            $prompt .= "- Nilai Akhir: " . number_format($lastValue, 0, ',', '.') . "\n";
            $prompt .= "- Rata-rata: " . number_format($avgValue, 0, ',', '.') . "\n";
            $prompt .= "- Pertumbuhan: " . ($firstValue > 0 ? number_format((($lastValue - $firstValue) / $firstValue) * 100, 2) : 0) . "%\n\n";
        }

        // Add recent manual records if available
        if (!empty($recentRecords)) {
            $prompt .= "RECORDS TERBARU:\n";
            foreach (array_slice($recentRecords, 0, 5) as $record) {
                $prompt .= "- " . $record['date'] . ": " . $record['value'];
                if (!empty($record['notes'])) {
                    $prompt .= " (Catatan: " . $record['notes'] . ")";
                }
                $prompt .= "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "TUGAS ANDA:
- Berikan analisis data-driven yang spesifik dan actionable
- Referensikan data aktual saat memberikan insights
- Pertimbangkan best practices industri untuk {$metricName}
- Fokus pada pengambilan keputusan bisnis yang praktis
- Identifikasi pola, tren, dan anomali penting
- Berikan rekomendasi konkret dengan justifikasi

GAYA RESPONS:
1. Gunakan Bahasa Indonesia yang profesional dan mudah dipahami
2. Mulai dengan key insight utama (1-2 kalimat) dengan emoji yang relevan
3. Berikan analisis mendalam dengan poin-poin terstruktur menggunakan markdown
4. Sertakan 2-3 rekomendasi spesifik dan actionable
5. Sebutkan potensi risiko atau pertimbangan penting
6. Akhiri dengan langkah-langkah konkret atau pertanyaan untuk eksplorasi lebih lanjut
7. WAJIB gunakan format markdown untuk readability:
   - **Bold** untuk poin penting
   - *Italic* untuk penekanan
   - Numbered lists (1. 2. 3.) untuk urutan langkah
   - Bullet points (- atau *) untuk daftar item
   - Emoji untuk visual appeal (📈📊💡⚠️✅❌🎯)
8. Berikan contoh numerik spesifik jika relevan
9. Pisahkan paragraf dengan line breaks untuk readability

CONTOH FORMAT RESPONS:
📊 **Analisis Tren:**
Metric menunjukkan peningkatan **15%** dalam 30 hari terakhir.

**Insight Utama:**
1. Pertumbuhan konsisten sejak minggu ke-2
2. Nilai tertinggi tercatat pada tanggal X
3. Volatilitas rendah menunjukkan stabilitas

💡 **Rekomendasi:**
- Pertahankan strategi current
- Tingkatkan investasi pada channel yang berkinerja baik
- Monitor kompetitor untuk mempertahankan momentum

⚠️ **Perhatian:**
Pastikan sustainability dengan diversifikasi.

HINDARI:
- Informasi yang terlalu generik atau tidak spesifik
- Asumsi tanpa dasar data
- Jargon yang terlalu teknis tanpa penjelasan
- Rekomendasi yang tidak praktis atau sulit diimplementasikan
- Respons tanpa formatting markdown

Jawab pertanyaan user dengan mengacu pada konteks data di atas.";

        return $prompt;
    }
}
