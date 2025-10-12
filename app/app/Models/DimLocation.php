<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimLocation extends Model
{
    protected $table = 'dim_location';

    protected $fillable = [
        'business_id',
        'location_nk',
        'kecamatan',
        'kota',
        'provinsi',
        'kode_pos',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Get the business that owns this location.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get all fact sales records for this location.
     */
    public function factSales(): HasMany
    {
        return $this->hasMany(FactSales::class, 'location_id');
    }

    /**
     * Get full location hierarchy as string.
     */
    public function getFullHierarchyAttribute(): string
    {
        return implode(' → ', array_filter([
            $this->provinsi,
            $this->kota,
            $this->kecamatan,
        ]));
    }

    /**
     * Scope: Filter by province.
     */
    public function scopeByProvinsi($query, string $provinsi)
    {
        return $query->where('provinsi', $provinsi);
    }

    /**
     * Scope: Filter by city.
     */
    public function scopeByKota($query, string $kota)
    {
        return $query->where('kota', $kota);
    }

    /**
     * Scope: Filter by district.
     */
    public function scopeByKecamatan($query, string $kecamatan)
    {
        return $query->where('kecamatan', $kecamatan);
    }

    /**
     * Scope: For a specific business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where(function ($q) use ($businessId) {
            $q->where('business_id', $businessId)
              ->orWhereNull('business_id'); // Include global locations
        });
    }

    /**
     * Get all unique provinces.
     */
    public static function getAllProvinces(?int $businessId = null): array
    {
        $query = static::query()
            ->whereNotNull('provinsi')
            ->where('provinsi', '!=', 'Unknown');

        if ($businessId) {
            $query->forBusiness($businessId);
        }

        return $query->distinct()
            ->pluck('provinsi')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get cities for a province.
     */
    public static function getCitiesForProvinsi(string $provinsi, ?int $businessId = null): array
    {
        $query = static::query()
            ->where('provinsi', $provinsi)
            ->whereNotNull('kota');

        if ($businessId) {
            $query->forBusiness($businessId);
        }

        return $query->distinct()
            ->pluck('kota')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get districts for a city.
     */
    public static function getKecamatanForKota(string $kota, ?int $businessId = null): array
    {
        $query = static::query()
            ->where('kota', $kota)
            ->whereNotNull('kecamatan');

        if ($businessId) {
            $query->forBusiness($businessId);
        }

        return $query->distinct()
            ->pluck('kecamatan')
            ->sort()
            ->values()
            ->toArray();
    }
}
