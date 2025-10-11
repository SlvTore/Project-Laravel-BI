<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Service untuk penanganan timezone yang konsisten di seluruh sistem OLAP
 * Memastikan data temporal akurat untuk multi-timezone businesses
 */
class TimezoneAwareService
{
    private array $businessTimezones = [];

    /**
     * Konversi timestamp ke business timezone dan return tanggal yang tepat
     */
    public function getBusinessDate($timestamp, int $businessId): Carbon
    {
        $businessTimezone = $this->getBusinessTimezone($businessId);

        if ($timestamp instanceof Carbon) {
            return $timestamp->setTimezone($businessTimezone);
        }

        return Carbon::parse($timestamp)->setTimezone($businessTimezone);
    }

    /**
     * Ambil timezone untuk business tertentu
     */
    public function getBusinessTimezone(int $businessId): string
    {
        if (isset($this->businessTimezones[$businessId])) {
            return $this->businessTimezones[$businessId];
        }

        // Cache business timezone untuk performance
        $timezone = Cache::remember("business_timezone_{$businessId}", 3600, function() use ($businessId) {
            $business = DB::table('businesses')
                ->where('id', $businessId)
                ->select('timezone')
                ->first();

            return $business?->timezone ?? config('app.timezone', 'UTC');
        });

        $this->businessTimezones[$businessId] = $timezone;
        return $timezone;
    }

    /**
     * Konversi range tanggal dengan timezone business
     */
    public function getBusinessDateRange(int $businessId, $startDate = null, $endDate = null): array
    {
        $timezone = $this->getBusinessTimezone($businessId);

        $start = $startDate
            ? $this->getBusinessDate($startDate, $businessId)->startOfDay()
            : Carbon::now($timezone)->startOfMonth();

        $end = $endDate
            ? $this->getBusinessDate($endDate, $businessId)->endOfDay()
            : Carbon::now($timezone)->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'start_utc' => $start->utc(),
            'end_utc' => $end->utc(),
            'timezone' => $timezone,
        ];
    }

    /**
     * Generate date dimension yang timezone-aware
     */
    public function ensureDateDimWithTimezone(string $date, int $businessId): int
    {
        $businessDate = $this->getBusinessDate($date, $businessId);
        $dateKey = $businessDate->toDateString();

        $row = DB::table('dim_date')->where('date', $dateKey)->first();
        if ($row) return (int)$row->id;

        return (int) DB::table('dim_date')->insertGetId([
            'date' => $dateKey,
            'day' => (int)$businessDate->day,
            'month' => (int)$businessDate->month,
            'year' => (int)$businessDate->year,
            'quarter' => (int)$businessDate->quarter,
            'month_name' => $businessDate->format('F'),
            'day_name' => $businessDate->format('l'),
            'week_of_year' => (int)$businessDate->weekOfYear,
            'is_weekend' => $businessDate->isWeekend(),
            'fiscal_year' => $this->getFiscalYear($businessDate, $businessId),
            'fiscal_quarter' => $this->getFiscalQuarter($businessDate, $businessId),
            'timezone' => $this->getBusinessTimezone($businessId),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Ambil fiscal year berdasarkan business settings
     */
    private function getFiscalYear(Carbon $date, int $businessId): int
    {
        // Default fiscal year sama dengan calendar year
        // Bisa dikustomisasi per business
        $fiscalYearStart = Cache::remember("fiscal_year_start_{$businessId}", 3600, function() use ($businessId) {
            $business = DB::table('businesses')
                ->where('id', $businessId)
                ->select('fiscal_year_start_month')
                ->first();

            return $business?->fiscal_year_start_month ?? 1; // Default January
        });

        if ($fiscalYearStart == 1) {
            return $date->year;
        }

        // Jika fiscal year tidak dimulai di January
        if ($date->month >= $fiscalYearStart) {
            return $date->year + 1;
        } else {
            return $date->year;
        }
    }

    /**
     * Ambil fiscal quarter berdasarkan business settings
     */
    private function getFiscalQuarter(Carbon $date, int $businessId): int
    {
        $fiscalYearStart = Cache::remember("fiscal_year_start_{$businessId}", 3600, function() use ($businessId) {
            $business = DB::table('businesses')
                ->where('id', $businessId)
                ->select('fiscal_year_start_month')
                ->first();

            return $business?->fiscal_year_start_month ?? 1;
        });

        // Adjust month untuk fiscal year
        $adjustedMonth = $date->month - $fiscalYearStart + 1;
        if ($adjustedMonth <= 0) {
            $adjustedMonth += 12;
        }

        return (int)ceil($adjustedMonth / 3);
    }

    /**
     * Konversi periode agregasi dengan timezone awareness
     */
    public function getAggregationPeriods(int $businessId, string $period = 'daily'): array
    {
        $timezone = $this->getBusinessTimezone($businessId);
        $now = Carbon::now($timezone);

        switch ($period) {
            case 'hourly':
                return [
                    'current' => $now->hour,
                    'start' => $now->startOfHour(),
                    'end' => $now->endOfHour(),
                ];

            case 'daily':
                return [
                    'current' => $now->toDateString(),
                    'start' => $now->startOfDay(),
                    'end' => $now->endOfDay(),
                ];

            case 'weekly':
                return [
                    'current' => $now->weekOfYear,
                    'start' => $now->startOfWeek(),
                    'end' => $now->endOfWeek(),
                ];

            case 'monthly':
                return [
                    'current' => $now->month,
                    'start' => $now->startOfMonth(),
                    'end' => $now->endOfMonth(),
                ];

            case 'quarterly':
                return [
                    'current' => $now->quarter,
                    'start' => $now->startOfQuarter(),
                    'end' => $now->endOfQuarter(),
                ];

            case 'yearly':
                return [
                    'current' => $now->year,
                    'start' => $now->startOfYear(),
                    'end' => $now->endOfYear(),
                ];

            default:
                return $this->getAggregationPeriods($businessId, 'daily');
        }
    }

    /**
     * Query builder dengan timezone awareness
     */
    public function buildTimezoneAwareQuery($query, string $dateColumn, int $businessId, $startDate = null, $endDate = null)
    {
        $dateRange = $this->getBusinessDateRange($businessId, $startDate, $endDate);

        return $query->whereBetween($dateColumn, [
            $dateRange['start_utc']->toDateTimeString(),
            $dateRange['end_utc']->toDateTimeString(),
        ]);
    }

    /**
     * Format tanggal untuk display dengan business timezone
     */
    public function formatForDisplay($timestamp, int $businessId, string $format = 'Y-m-d H:i:s'): string
    {
        $businessDate = $this->getBusinessDate($timestamp, $businessId);
        return $businessDate->format($format);
    }

    /**
     * Ambil periode comparison (previous period) dengan timezone awareness
     */
    public function getComparisonPeriod(int $businessId, $startDate, $endDate, string $comparisonType = 'previous'): array
    {
        $start = $this->getBusinessDate($startDate, $businessId);
        $end = $this->getBusinessDate($endDate, $businessId);
        $diffInDays = $start->diffInDays($end);

        switch ($comparisonType) {
            case 'previous':
                return [
                    'start' => $start->copy()->subDays($diffInDays + 1),
                    'end' => $start->copy()->subDay(),
                ];

            case 'previous_month':
                return [
                    'start' => $start->copy()->subMonth()->startOfMonth(),
                    'end' => $start->copy()->subMonth()->endOfMonth(),
                ];

            case 'previous_quarter':
                return [
                    'start' => $start->copy()->subQuarter()->startOfQuarter(),
                    'end' => $start->copy()->subQuarter()->endOfQuarter(),
                ];

            case 'previous_year':
                return [
                    'start' => $start->copy()->subYear(),
                    'end' => $end->copy()->subYear(),
                ];

            case 'year_over_year':
                return [
                    'start' => $start->copy()->subYear(),
                    'end' => $end->copy()->subYear(),
                ];

            default:
                return $this->getComparisonPeriod($businessId, $startDate, $endDate, 'previous');
        }
    }

    /**
     * Validate timezone untuk business
     */
    public function validateBusinessTimezone(int $businessId, string $timezone): bool
    {
        try {
            Carbon::now($timezone);
            return in_array($timezone, timezone_identifiers_list());
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update business timezone
     */
    public function updateBusinessTimezone(int $businessId, string $timezone): bool
    {
        if (!$this->validateBusinessTimezone($businessId, $timezone)) {
            return false;
        }

        DB::table('businesses')
            ->where('id', $businessId)
            ->update(['timezone' => $timezone]);

        // Clear cache
        Cache::forget("business_timezone_{$businessId}");
        Cache::forget("fiscal_year_start_{$businessId}");

        return true;
    }

    /**
     * Ambil timezone offset untuk frontend
     */
    public function getTimezoneOffset(int $businessId): array
    {
        $timezone = $this->getBusinessTimezone($businessId);
        $now = Carbon::now($timezone);

        return [
            'timezone' => $timezone,
            'offset_hours' => $now->offsetHours,
            'offset_minutes' => $now->offset / 60,
            'dst' => $now->dst,
            'abbreviation' => $now->tzName,
        ];
    }
}
