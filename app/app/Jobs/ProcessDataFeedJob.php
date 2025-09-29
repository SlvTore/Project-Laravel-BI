<?php

namespace App\Jobs;

use App\Models\DataFeed;
use App\Services\OlapWarehouseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $lock = Cache::lock("process-data-feed-{$this->feedId}", 300);

        if (!$lock->get()) {
            Log::warning('ProcessDataFeedJob already running for feed '.$this->feedId);
            return;
        }

        try {
            /** @var \App\Models\DataFeed|null $feed */
            $feed = DataFeed::find($this->feedId);
            if (!$feed) {
                Log::warning('ProcessDataFeedJob skipped; data feed not found', ['feed_id' => $this->feedId]);
                return;
            }

            if ($feed->status === 'transformed') {
                Log::info('ProcessDataFeedJob skipped; feed already transformed', ['feed_id' => $feed->id]);
                return;
            }

            $feed->update([
                'status' => 'transforming',
                'log_message' => 'Memulai proses ETL ke warehouse',
            ]);

            DB::beginTransaction();
            $result = $warehouse->loadFactsFromStaging($feed);
            DB::commit();

            $summary = $this->normalizeResultSummary($result);
            $feed->update([
                'status' => 'transformed',
                'record_count' => $summary['records'] ?? $feed->record_count,
                'log_message' => $this->buildLogMessage($summary),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ProcessDataFeedJob error: '.$e->getMessage(), [
                'feed_id' => $this->feedId,
                'trace' => $e->getTraceAsString(),
            ]);

            DataFeed::where('id', $this->feedId)->update([
                'status' => 'failed',
                'log_message' => $e->getMessage(),
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @param mixed $result
     * @return array{records:int, revenue?:float, cogs?:float, margin?:float}
     */
    protected function normalizeResultSummary($result): array
    {
        if (is_array($result)) {
            return [
                'records' => (int) ($result['records'] ?? $result['inserted'] ?? 0),
                'revenue' => isset($result['gross_revenue']) ? (float) $result['gross_revenue'] : null,
                'cogs' => isset($result['cogs_amount']) ? (float) $result['cogs_amount'] : null,
                'margin' => isset($result['gross_margin_amount']) ? (float) $result['gross_margin_amount'] : null,
            ];
        }

        return [
            'records' => (int) $result,
        ];
    }

    protected function buildLogMessage(array $summary): string
    {
        $parts = [];
        $parts[] = ($summary['records'] ?? 0).' baris diproses ke fact_sales';

        if (isset($summary['revenue'])) {
            $parts[] = 'Omzet Rp'.number_format((float) $summary['revenue'], 0, ',', '.');
        }

        if (isset($summary['cogs'])) {
            $parts[] = 'HPP Rp'.number_format((float) $summary['cogs'], 0, ',', '.');
        }

        if (isset($summary['margin'])) {
            $parts[] = 'Margin Rp'.number_format((float) $summary['margin'], 0, ',', '.');
        }

        return implode(' | ', $parts);
    }
}
