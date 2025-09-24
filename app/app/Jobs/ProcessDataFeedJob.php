<?php

namespace App\Jobs;

use App\Models\DataFeed;
use App\Services\OlapWarehouseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDataFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $feedId;

    public function __construct(int $feedId)
    {
        $this->feedId = $feedId;
        $this->onQueue('default');
    }

    public function handle(OlapWarehouseService $warehouse): void
    {
        $feed = DataFeed::find($this->feedId);
        if (!$feed) return;

        try {
            $feed->update(['status' => 'transforming', 'log_message' => 'Starting OLAP transform']);
            $count = $warehouse->loadFactsFromStaging($feed);
            $feed->update(['status' => 'transformed', 'log_message' => "Transformed {$count} rows into fact_sales"]);
        } catch (\Throwable $e) {
            Log::error('ProcessDataFeedJob error: '.$e->getMessage());
            $feed->update(['status' => 'failed', 'log_message' => $e->getMessage()]);
        }
    }
}
