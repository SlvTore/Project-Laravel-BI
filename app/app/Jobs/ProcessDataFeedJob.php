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
            $this->mergeSummary($feed, [
                'stage' => 'transforming',
                'transform_started_at' => now()->toISOString(),
                'error' => null,
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
            $this->mergeSummary($feed, [
                'stage' => 'transformed',
                'transform_finished_at' => now()->toISOString(),
                'metrics' => $summary,
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
            if (isset($feed)) {
                $this->mergeSummary($feed->fresh(), [
                    'stage' => 'failed',
                    'error' => [
                        'code' => 'etl_failed',
                        'message' => $e->getMessage(),
                    ],
                ]);
            }
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
                'gross_revenue' => isset($result['gross_revenue']) ? (float) $result['gross_revenue'] : null,
                'cogs_amount' => isset($result['cogs_amount']) ? (float) $result['cogs_amount'] : null,
                'gross_margin_amount' => isset($result['gross_margin_amount']) ? (float) $result['gross_margin_amount'] : null,
                'gross_margin_percent' => isset($result['gross_margin_percent']) ? (float) $result['gross_margin_percent'] : null,
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

        if (isset($summary['gross_revenue'])) {
            $parts[] = 'Omzet Rp'.number_format((float) $summary['gross_revenue'], 0, ',', '.');
        }

        if (isset($summary['cogs_amount'])) {
            $parts[] = 'HPP Rp'.number_format((float) $summary['cogs_amount'], 0, ',', '.');
        }

        if (isset($summary['gross_margin_amount'])) {
            $parts[] = 'Margin Rp'.number_format((float) $summary['gross_margin_amount'], 0, ',', '.');
        }

        return implode(' | ', $parts);
    }

    protected function mergeSummary(DataFeed $feed, array $changes): void
    {
        $summary = $feed->summary ?? [];
        $merged = array_replace_recursive($summary, $changes);
        $feed->summary = $merged;
        $feed->save();
    }
}
