<?php

namespace App\Jobs;

use App\Services\MisoAccomplishmentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PerformMisoExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 300];
    public $timeout = 180;

    public function __construct(
        public string $filePath,
        public string $jobId,
        public string $category,
    ) {}

    public function handle(MisoAccomplishmentSyncService $service): void
    {
        try {
            Log::info("PerformMisoExtractionJob: Started Job {$this->jobId} for category {$this->category}");

            if (!is_file($this->filePath)) {
                throw new \RuntimeException("Background Job Error: File not found at {$this->filePath}");
            }

            Cache::put("miso_extraction_{$this->jobId}_status", 'processing', 600);

            $records = $service->extractFromFile($this->filePath, $this->category);

            Cache::put("miso_extraction_{$this->jobId}_result", $records, 600);
            Cache::put("miso_extraction_{$this->jobId}_status", 'completed', 600);

            Log::info("PerformMisoExtractionJob: Completed Job {$this->jobId} with " . count($records) . ' records');
        } catch (\Throwable $e) {
            Log::error('PerformMisoExtractionJob failed', [
                'job_id' => $this->jobId,
                'category' => $this->category,
                'error' => $e->getMessage(),
            ]);

            Cache::put("miso_extraction_{$this->jobId}_status", 'failed', 600);
            Cache::put("miso_extraction_{$this->jobId}_error", 'A processing error occurred. Please contact support.', 600);
        } finally {
            if (is_file($this->filePath)) {
                @unlink($this->filePath);
                Log::info("PerformMisoExtractionJob: Cleanup complete for {$this->filePath}");
            }
        }
    }
}
