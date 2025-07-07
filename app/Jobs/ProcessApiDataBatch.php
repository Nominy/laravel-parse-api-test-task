<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessApiDataBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 5;
    public $backoff = [15, 30, 60, 120, 240];

    private array $data;
    private string $endpoint;
    private string $modelClass;
    private int $pageNumber;
    private int $batchSize;

    public function __construct(array $data, string $endpoint, string $modelClass, int $pageNumber, int $batchSize = 500)
    {
        $this->data = $data;
        $this->endpoint = $endpoint;
        $this->modelClass = $modelClass;
        $this->pageNumber = $pageNumber;
        $this->batchSize = min($batchSize, 500);
        
        $this->connection = 'queuesqlite';
    }

    public function handle(): void
    {
        if (empty($this->data)) {
            return;
        }

        $totalInserted = 0;
        $tableName = $this->modelClass::make()->getTable();

        $preparedData = $this->prepareDataForInsert();

        if (empty($preparedData)) {
            return;
        }

        $chunks = array_chunk($preparedData, min($this->batchSize, 1000));

        foreach ($chunks as $chunkIndex => $chunk) {
            if (empty($chunk)) {
                continue;
            }

            $attempts = 0;
            $inserted = false;
            while (!$inserted && $attempts < 3) {
                try {
                    $insertCount = DB::table($tableName)->insertOrIgnore($chunk);
                    $skipped = count($chunk) - $insertCount;
                    $totalInserted += $insertCount;
                    if ($skipped > 0) {
                        Log::warning("{$skipped} duplicates skipped — {$this->endpoint} page {$this->pageNumber}, chunk {$chunkIndex}");
                    }
                    $inserted = true;
                } catch (\Throwable $e) {
                    $attempts++;
                    usleep(50000 * $attempts);
                    if ($attempts >= 3) {
                        Log::warning("Chunk #{$chunkIndex} insert failed after {$attempts} attempts — {$this->endpoint} page {$this->pageNumber}: " . $e->getMessage());
                    }
                }
            }
        }

        if ($totalInserted > 10) {
            Log::info("Batch insert completed: {$this->endpoint} page {$this->pageNumber}, {$totalInserted} items");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job permanently failed: {$this->endpoint} page {$this->pageNumber} - {$exception->getMessage()}");
    }

    private function prepareDataForInsert(): array
    {
        $prepared = [];
        foreach ($this->data as $item) {
            if (!empty($item)) {
                $prepared[] = $this->modelClass::prepareApiDataForInsert($item);
            }
        }
        return $prepared;
    }
}
