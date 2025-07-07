<?php

namespace App\Services;

use App\Models\Income;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Stock;
use App\Jobs\ProcessApiDataBatch;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiDataService
{
    private string $baseUrl;
    private string $apiKey;
    private array $modelMap = [
        'stocks' => Stock::class,
        'incomes' => Income::class,
        'sales' => Sale::class,
        'orders' => Order::class,
    ];
    private Client $httpClient;
    private int $maxRetries = 3;
    private int $retryDelay = 1000;

    public function __construct()
    {
        $this->baseUrl = env('API_BASE_URL');
        $this->apiKey = env('API_KEY');

        if (! $this->baseUrl) {
            throw new \InvalidArgumentException('API_BASE_URL environment variable is required');
        }

        if (! $this->apiKey) {
            throw new \InvalidArgumentException('API_KEY environment variable is required');
        }


        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
            'verify' => false,
            'headers' => [
                'Connection' => 'keep-alive',
                'Keep-Alive' => 'timeout=30, max=100',
                'Accept-Encoding' => 'gzip, deflate',
            ],
            'curl' => [
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 600,
                CURLOPT_TCP_KEEPINTVL => 60,
                CURLOPT_MAXCONNECTS => 20,
            ],
        ]);
    }

    public function getTotalPages(string $endpoint, string $dateFrom, string $dateTo, int $limit): ?int
    {
        $cacheKey = "total_pages_{$endpoint}_{$dateFrom}_{$dateTo}_{$limit}";
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($endpoint, $dateFrom, $dateTo, $limit) {
            return $this->fetchTotalPagesWithRetry($endpoint, $dateFrom, $dateTo, $limit);
        });
    }

    private function fetchTotalPagesWithRetry(string $endpoint, string $dateFrom, string $dateTo, int $limit): ?int
    {
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = Http::timeout(30)->get("{$this->baseUrl}/{$endpoint}", [
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'page' => 1,
                    'limit' => $limit,
                    'key' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['meta']['last_page'] ?? 1;
                }

                if ($response->status() === 429) {
                    $this->handleRateLimit($attempt);
                    continue;
                }
            } catch (\Exception $e) {
                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * $attempt * 1000);
                }
            }
        }

        return null;
    }

    public function fetchPagesInPool(string $endpoint, string $dateFrom, string $dateTo, array $pages, int $limit, callable $pageCallback = null): int
    {
        if (empty($pages)) {
            return 0;
        }

        $url = "{$this->baseUrl}/{$endpoint}";
        $totalProcessed = 0;
        $pageResults = [];

        $requests = function () use ($pages, $url, $dateFrom, $dateTo, $limit) {
            foreach ($pages as $page) {
                $params = [
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'page' => $page,
                    'limit' => $limit,
                    'key' => $this->apiKey,
                ];
                yield $page => new Request('GET', $url.'?'.http_build_query($params));
            }
        };

        try {
            $pool = new Pool($this->httpClient, $requests(), [
                'concurrency' => min(count($pages), 50),
                'fulfilled' => function ($response, $index) use (&$pageResults) {
                    $laravelResponse = new HttpResponse($response);
                    
                    if ($laravelResponse->successful()) {
                        $data = $laravelResponse->json();
                        if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
                            $pageResults[$index] = $data['data'];
                        } else {
                            $pageResults[$index] = ['_error' => true];
                        }
                    } else {
                        $pageResults[$index] = ['_error' => true];
                    }
                },
                'rejected' => function ($reason, $index) use (&$pageResults) {
                    $pageResults[$index] = ['_error' => true];
                },
            ]);

            $pool->promise()->wait();
            $totalProcessed = $this->batchProcessResults($pageResults, $endpoint, $pageCallback);

            $failedPages = [];
            foreach ($pages as $pageNum) {
                if (!isset($pageResults[$pageNum]) || isset($pageResults[$pageNum]['_error'])) {
                    $failedPages[] = $pageNum;
                }
            }
            if (!empty($failedPages)) {
                $totalProcessed += $this->fetchPagesSequentialWithStreaming($endpoint, $dateFrom, $dateTo, $failedPages, $limit, $pageCallback);
            }

            return $totalProcessed;
        } catch (\Exception $e) {
            return $this->fetchPagesSequentialWithStreaming($endpoint, $dateFrom, $dateTo, $pages, $limit, $pageCallback);
        }
    }

    private function batchProcessResults(array $pageResults, string $endpoint, callable $pageCallback = null): int
    {
        $totalProcessed = 0;
        
        foreach ($pageResults as $pageNumber => $data) {
            if (isset($data['_error'])) {
                continue;
            }

            if ($pageCallback) {
                $processed = $pageCallback($data, $pageNumber);
                $totalProcessed += $processed;
            } else {
                $modelClass = $this->modelMap[$endpoint];
                ProcessApiDataBatch::dispatch($data, $endpoint, $modelClass, $pageNumber, 500)
                    ->onConnection('queuesqlite');
                $totalProcessed += count($data);
            }
        }

        return $totalProcessed;
    }

    private function fetchPagesSequentialWithStreaming(string $endpoint, string $dateFrom, string $dateTo, array $pages, int $limit, callable $pageCallback = null): int
    {
        $totalProcessed = 0;
        $url = "{$this->baseUrl}/{$endpoint}";

        foreach ($pages as $page) {
            $attempt = 1;
            $dataRetrieved = false;
            while ($attempt <= $this->maxRetries && ! $dataRetrieved) {
                try {
                    $response = Http::timeout(30)->get($url, [
                        'dateFrom' => $dateFrom,
                        'dateTo' => $dateTo,
                        'page' => $page,
                        'limit' => $limit,
                        'key' => $this->apiKey,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
                            if ($pageCallback) {
                                $processed = $pageCallback($data['data'], $page);
                                $totalProcessed += $processed;
                            } else {
                                $modelClass = $this->modelMap[$endpoint];
                                ProcessApiDataBatch::dispatch($data['data'], $endpoint, $modelClass, $page, 500)
                                    ->onConnection('queuesqlite');
                                $totalProcessed += count($data['data']);
                            }
                            $dataRetrieved = true;
                            break;
                        }
                    }

                    if ($response->status() === 429) {
                        $this->handleRateLimit($attempt);
                    } else {
                        usleep($this->retryDelay * $attempt * 1000);
                    }
                } catch (\Exception $e) {
                    usleep($this->retryDelay * $attempt * 1000);
                }

                $attempt++;
            }
        }

        return $totalProcessed;
    }

    private function handleRateLimit(int $attempt): void
    {
        $delay = min(60, pow(2, $attempt));
        sleep($delay);
    }

    public function getAvailableEndpoints(): array
    {
        return array_keys($this->modelMap);
    }

    public function isValidEndpoint(string $endpoint): bool
    {
        return array_key_exists($endpoint, $this->modelMap);
    }

    public function getModelClass(string $endpoint): string
    {
        if (!$this->isValidEndpoint($endpoint)) {
            throw new \InvalidArgumentException("Invalid endpoint: {$endpoint}");
        }
        return $this->modelMap[$endpoint];
    }

    public function clearCache(string $endpoint = null): void
    {
        if ($endpoint) {
            Cache::forget("total_pages_{$endpoint}*");
        } else {
            Cache::flush();
        }
    }
}
