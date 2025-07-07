<?php

namespace App\Console\Commands;

use App\Services\ApiDataService;
use App\Jobs\ProcessApiDataBatch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;

class FetchApiDataCommand extends Command
{
    protected $signature = 'fetch:api-data 
                           {--endpoint=all : Endpoint to fetch (stocks, incomes, sales, orders, or all)}
                           {--date-from=2025-07-07 : Start date (YYYY-MM-DD)}
                           {--date-to= : End date (YYYY-MM-DD)}
                           {--concurrent=30 : Number of concurrent requests}
                           {--limit=500 : Items per page (max 500)}';

    protected $description = 'Fetch data from API endpoints';

    private ApiDataService $apiDataService;
    private array $workerProcesses = [];
    private int $totalJobsDispatched = 0;

    public function __construct(ApiDataService $apiDataService)
    {
        parent::__construct();
        $this->apiDataService = $apiDataService;
    }

    public function handle()
    {
        $this->info('Starting API data fetch');

        $endpoint = $this->option('endpoint');
        $dateFrom = $this->option('date-from') ?? Carbon::yesterday()->format('Y-m-d');
        $dateTo = $this->option('date-to') ?? Carbon::today()->format('Y-m-d');
        $concurrent = (int) $this->option('concurrent');
        $limit = min((int) $this->option('limit'), 500);

        $this->info("Date range: {$dateFrom} to {$dateTo}");
        $this->info("Concurrent requests: {$concurrent}");
        $this->info("Limit per page: {$limit}");

        $endpointsToProcess = $endpoint === 'all'
            ? $this->apiDataService->getAvailableEndpoints()
            : [$endpoint];

        $this->startQueueWorkers(8);

        foreach ($endpointsToProcess as $endpointName) {
            if (! $this->apiDataService->isValidEndpoint($endpointName)) {
                $this->error("Invalid endpoint: {$endpointName}");
                continue;
            }

            $effectiveDateFrom = $dateFrom;
            if ($endpointName === 'stocks' && Carbon::parse($dateFrom) != (Carbon::today())) {
                $effectiveDateFrom = Carbon::today()->format('Y-m-d');
                $this->line("'stocks' endpoint uses today's date - overriding to {$effectiveDateFrom}");
            }

            $this->info("\n=== Processing {$endpointName} ===");
            $this->processEndpoint($endpointName, $effectiveDateFrom, $dateTo, $concurrent, $limit);
        }

        $this->info("\nTotal jobs dispatched: {$this->totalJobsDispatched}");
        $this->waitForQueueWorkers();
        $this->info("\nAll endpoints processed successfully!");
    }

    private function processEndpoint(string $endpoint, string $dateFrom, string $dateTo, int $concurrent, int $limit): void
    {
        $totalPages = $this->apiDataService->getTotalPages($endpoint, $dateFrom, $dateTo, $limit);

        if (! $totalPages) {
            $this->error("Failed to get total pages for {$endpoint}");
            return;
        }

        $this->info("Total pages for {$endpoint}: {$totalPages}");

        $pageRange = range(1, $totalPages);
        $pageGroups = array_chunk($pageRange, $concurrent);

        foreach ($pageGroups as $groupIndex => $pageGroup) {
            $this->info("Processing page group " . ($groupIndex + 1) . "/" . count($pageGroups) . 
                       " (Pages: " . min($pageGroup) . "-" . max($pageGroup) . ")");

            $pageCallback = function (array $pageData, int $pageNumber) use ($endpoint): int {
                if (!empty($pageData)) {
                    $modelClass = $this->apiDataService->getModelClass($endpoint);
                    ProcessApiDataBatch::dispatch($pageData, $endpoint, $modelClass, $pageNumber, 500);
                    $this->line("Page {$pageNumber}: " . count($pageData) . " items → queued for database insert");
                    $this->totalJobsDispatched++;
                    return count($pageData);
                }
                return 0;
            };

            $this->apiDataService->fetchPagesInPool($endpoint, $dateFrom, $dateTo, $pageGroup, $limit, $pageCallback);

            if ($groupIndex < count($pageGroups) - 1) {
                usleep(100000);
            }
        }

        $this->info("Completed {$endpoint}");
    }

    private function startQueueWorkers(int $workers = 8): void
    {
        $this->info("Starting {$workers} queue workers...");
        for ($i = 0; $i < $workers; $i++) {
            $process = Process::fromShellCommandline(
                'php artisan queue:work queuesqlite --sleep=3 --timeout=300 --tries=6',
                base_path()
            );
            $process->setTimeout(null);
            $process->start();
            $this->workerProcesses[] = $process;
        }
        sleep(2);
    }

    private function waitForQueueWorkers(): void
    {
        $this->info("Waiting for all jobs to complete...");
        
        $maxWaitTime = 1800;
        $startTime = time();
        $lastJobCount = -1;
        $stableCount = 0;
        
        while (time() - $startTime < $maxWaitTime) {
            try {
                $pendingJobs = DB::connection('queuesqlite')->table('jobs')->count();
                $processingJobs = DB::connection('queuesqlite')->table('jobs')->whereNotNull('reserved_at')->count();
            } catch (\Exception $e) {
                $this->warn("Could not check queue status: " . $e->getMessage());
                sleep(10);
                continue;
            }
            
            $this->line("→ Pending jobs: {$pendingJobs}, Processing: {$processingJobs}");
            
            if ($pendingJobs === 0) {
                if ($lastJobCount === 0) {
                    $stableCount++;
                } else {
                    $stableCount = 1;
                }
                
                if ($stableCount >= 3) {
                    $this->info("All jobs completed successfully!");
                    break;
                }
            } else {
                $stableCount = 0;
            }
            
            $lastJobCount = $pendingJobs;
            sleep(10);
        }
        
        $this->info("Stopping queue workers...");
        foreach ($this->workerProcesses as $index => $process) {
            if ($process->isRunning()) {
                $process->stop(3);
                $this->line("→ Worker {$index} stopped");
            }
        }
    }
}
