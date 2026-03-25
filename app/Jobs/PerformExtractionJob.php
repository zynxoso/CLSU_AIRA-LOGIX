<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\IctExtractionService;
use App\Services\IctImageExtractionService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PerformExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 300];
    public $timeout = 180;

    public $filePath;
    public $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $jobId)
    {
        $this->filePath = $filePath;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("PerformExtractionJob: Started Job: {$this->jobId} Path: {$this->filePath}");
            
            if (!file_exists($this->filePath)) {
                throw new \Exception("Background Job Error: File not found at {$this->filePath}");
            }

            Cache::put("extraction_{$this->jobId}_status", 'processing', 600);
            
            $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $service = app(IctImageExtractionService::class);
                $extractedData = $service->extract($this->filePath);
            } else {
                $service = app(IctExtractionService::class);
                $extractedData = $service->extractFromFile($this->filePath);
            }

            Cache::put("extraction_{$this->jobId}_result", $extractedData, 600);
            Cache::put("extraction_{$this->jobId}_status", 'completed', 600);
            
        } catch (\Throwable $e) {
            Log::error("PerformExtractionJob Failed: " . $e->getMessage());
            Cache::put("extraction_{$this->jobId}_status", 'failed', 600);
            Cache::put("extraction_{$this->jobId}_error", $e->getMessage(), 600);
        } finally {
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
                Log::info("PerformExtractionJob: Cleanup complete for {$this->filePath}");
            }
        }
    }
}
