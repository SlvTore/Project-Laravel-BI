<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service untuk business rules yang configurable berdasarkan jenis industri
 * Mengganti hardcoded values dengan dynamic rules per business type
 */
class BusinessRulesService
{
    private array $industryRules = [];
    private array $defaultRules = [];

    public function __construct()
    {
        $this->initializeIndustryRules();
        $this->initializeDefaultRules();
    }

    /**
     * Ambil churn threshold berdasarkan business type dan industry
     */
    public function getChurnThreshold(int $businessId): array
    {
        $businessType = $this->getBusinessType($businessId);
        $industryRules = $this->industryRules[$businessType] ?? $this->defaultRules;

        return [
            'active_days' => $industryRules['churn']['active_days'] ?? 60,
            'at_risk_days' => $industryRules['churn']['at_risk_days'] ?? 120,
            'churned_days' => $industryRules['churn']['churned_days'] ?? 180,
            'grace_period_days' => $industryRules['churn']['grace_period_days'] ?? 30,
        ];
    }

    /**
     * Determine churn status berdasarkan dynamic rules
     */
    public function determineChurnStatus(int $daysSinceLastPurchase, int $businessId): string
    {
        $thresholds = $this->getChurnThreshold($businessId);

        if ($daysSinceLastPurchase <= $thresholds['active_days']) {
            return 'active';
        } elseif ($daysSinceLastPurchase <= $thresholds['at_risk_days']) {
            return 'at_risk';
        } elseif ($daysSinceLastPurchase <= $thresholds['churned_days']) {
            return 'churned';
        } else {
            return 'lost';
        }
    }

    /**
     * Ambil customer segmentation rules
     */
    public function getCustomerSegmentationRules(int $businessId): array
    {
        $businessType = $this->getBusinessType($businessId);
        $industryRules = $this->industryRules[$businessType] ?? $this->defaultRules;

        return [
            'high_value_threshold' => $industryRules['segmentation']['high_value_threshold'] ?? 1000,
            'loyal_customer_orders' => $industryRules['segmentation']['loyal_customer_orders'] ?? 5,
            'loyal_customer_months' => $industryRules['segmentation']['loyal_customer_months'] ?? 6,
            'new_customer_days' => $industryRules['segmentation']['new_customer_days'] ?? 30,
            'vip_ltv_multiplier' => $industryRules['segmentation']['vip_ltv_multiplier'] ?? 5,
        ];
    }

    /**
     * Determine customer segment berdasarkan dynamic rules
     */
    public function determineCustomerSegment(array $customerData, int $businessId): string
    {
        $rules = $this->getCustomerSegmentationRules($businessId);

        $totalSpent = $customerData['total_spent'] ?? 0;
        $orderCount = $customerData['order_count'] ?? 0;
        $daysSinceFirstOrder = $customerData['days_since_first_order'] ?? 0;
        $daysSinceLastOrder = $customerData['days_since_last_order'] ?? 0;
        $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;

        // VIP Customer
        if ($totalSpent >= ($rules['high_value_threshold'] * $rules['vip_ltv_multiplier'])) {
            return 'vip';
        }

        // High Value Customer
        if ($totalSpent >= $rules['high_value_threshold']) {
            return 'high_value';
        }

        // Loyal Customer
        if ($orderCount >= $rules['loyal_customer_orders'] &&
            $daysSinceFirstOrder >= ($rules['loyal_customer_months'] * 30)) {
            return 'loyal';
        }

        // New Customer
        if ($daysSinceFirstOrder <= $rules['new_customer_days']) {
            return 'new';
        }

        // Regular Customer
        if ($orderCount >= 2) {
            return 'regular';
        }

        // One-time Customer
        return 'one_time';
    }

    /**
     * Ambil margin expectations berdasarkan business type
     */
    public function getMarginExpectations(int $businessId): array
    {
        $businessType = $this->getBusinessType($businessId);
        $industryRules = $this->industryRules[$businessType] ?? $this->defaultRules;

        return [
            'min_gross_margin' => $industryRules['margins']['min_gross_margin'] ?? 10,
            'target_gross_margin' => $industryRules['margins']['target_gross_margin'] ?? 30,
            'excellent_gross_margin' => $industryRules['margins']['excellent_gross_margin'] ?? 50,
            'min_net_margin' => $industryRules['margins']['min_net_margin'] ?? 5,
            'target_net_margin' => $industryRules['margins']['target_net_margin'] ?? 15,
        ];
    }

    /**
     * Evaluate margin performance terhadap industry standards
     */
    public function evaluateMarginPerformance(float $margin, int $businessId, string $marginType = 'gross'): array
    {
        $expectations = $this->getMarginExpectations($businessId);
        $key = $marginType . '_margin';

        $minKey = 'min_' . $key;
        $targetKey = 'target_' . $key;
        $excellentKey = 'excellent_' . $key;

        if ($margin >= ($expectations[$excellentKey] ?? 50)) {
            $performance = 'excellent';
            $color = 'green';
        } elseif ($margin >= ($expectations[$targetKey] ?? 30)) {
            $performance = 'good';
            $color = 'blue';
        } elseif ($margin >= ($expectations[$minKey] ?? 10)) {
            $performance = 'acceptable';
            $color = 'yellow';
        } else {
            $performance = 'poor';
            $color = 'red';
        }

        return [
            'performance' => $performance,
            'color' => $color,
            'margin' => $margin,
            'industry_min' => $expectations[$minKey] ?? 10,
            'industry_target' => $expectations[$targetKey] ?? 30,
            'recommendations' => $this->getMarginRecommendations($performance, $businessId),
        ];
    }

    /**
     * Ambil sales performance thresholds
     */
    public function getSalesPerformanceThresholds(int $businessId): array
    {
        $businessType = $this->getBusinessType($businessId);
        $industryRules = $this->industryRules[$businessType] ?? $this->defaultRules;

        return [
            'growth_rate_targets' => [
                'monthly' => $industryRules['growth']['monthly_target'] ?? 5,
                'quarterly' => $industryRules['growth']['quarterly_target'] ?? 15,
                'yearly' => $industryRules['growth']['yearly_target'] ?? 25,
            ],
            'retention_rate_targets' => [
                'monthly' => $industryRules['retention']['monthly_target'] ?? 80,
                'quarterly' => $industryRules['retention']['quarterly_target'] ?? 75,
                'yearly' => $industryRules['retention']['yearly_target'] ?? 70,
            ],
            'conversion_rate_targets' => [
                'lead_to_customer' => $industryRules['conversion']['lead_to_customer'] ?? 2,
                'visitor_to_lead' => $industryRules['conversion']['visitor_to_lead'] ?? 5,
                'repeat_purchase' => $industryRules['conversion']['repeat_purchase'] ?? 25,
            ],
        ];
    }

    /**
     * Ambil business type dari database
     */
    private function getBusinessType(int $businessId): string
    {
        return Cache::remember("business_type_{$businessId}", 3600, function() use ($businessId) {
            $business = DB::table('businesses')
                ->where('id', $businessId)
                ->select('industry', 'business_type')
                ->first();

            return $business?->industry ?? $business?->business_type ?? 'general';
        });
    }

    /**
     * Initialize industry-specific rules
     */
    private function initializeIndustryRules(): void
    {
        $this->industryRules = [
            'ecommerce' => [
                'churn' => [
                    'active_days' => 90,
                    'at_risk_days' => 180,
                    'churned_days' => 365,
                    'grace_period_days' => 30,
                ],
                'segmentation' => [
                    'high_value_threshold' => 500,
                    'loyal_customer_orders' => 3,
                    'loyal_customer_months' => 3,
                    'new_customer_days' => 30,
                    'vip_ltv_multiplier' => 10,
                ],
                'margins' => [
                    'min_gross_margin' => 20,
                    'target_gross_margin' => 40,
                    'excellent_gross_margin' => 60,
                    'min_net_margin' => 5,
                    'target_net_margin' => 15,
                ],
                'growth' => [
                    'monthly_target' => 8,
                    'quarterly_target' => 25,
                    'yearly_target' => 50,
                ],
                'retention' => [
                    'monthly_target' => 75,
                    'quarterly_target' => 70,
                    'yearly_target' => 60,
                ],
                'conversion' => [
                    'lead_to_customer' => 3,
                    'visitor_to_lead' => 8,
                    'repeat_purchase' => 30,
                ],
            ],
            'saas' => [
                'churn' => [
                    'active_days' => 60,
                    'at_risk_days' => 90,
                    'churned_days' => 120,
                    'grace_period_days' => 15,
                ],
                'segmentation' => [
                    'high_value_threshold' => 2000,
                    'loyal_customer_orders' => 12,
                    'loyal_customer_months' => 12,
                    'new_customer_days' => 30,
                    'vip_ltv_multiplier' => 5,
                ],
                'margins' => [
                    'min_gross_margin' => 70,
                    'target_gross_margin' => 85,
                    'excellent_gross_margin' => 95,
                    'min_net_margin' => 15,
                    'target_net_margin' => 25,
                ],
                'growth' => [
                    'monthly_target' => 10,
                    'quarterly_target' => 30,
                    'yearly_target' => 100,
                ],
                'retention' => [
                    'monthly_target' => 95,
                    'quarterly_target' => 90,
                    'yearly_target' => 85,
                ],
                'conversion' => [
                    'lead_to_customer' => 5,
                    'visitor_to_lead' => 15,
                    'repeat_purchase' => 90,
                ],
            ],
            'retail' => [
                'churn' => [
                    'active_days' => 120,
                    'at_risk_days' => 240,
                    'churned_days' => 365,
                    'grace_period_days' => 60,
                ],
                'segmentation' => [
                    'high_value_threshold' => 300,
                    'loyal_customer_orders' => 4,
                    'loyal_customer_months' => 6,
                    'new_customer_days' => 30,
                    'vip_ltv_multiplier' => 8,
                ],
                'margins' => [
                    'min_gross_margin' => 25,
                    'target_gross_margin' => 45,
                    'excellent_gross_margin' => 65,
                    'min_net_margin' => 8,
                    'target_net_margin' => 18,
                ],
                'growth' => [
                    'monthly_target' => 3,
                    'quarterly_target' => 10,
                    'yearly_target' => 15,
                ],
                'retention' => [
                    'monthly_target' => 70,
                    'quarterly_target' => 65,
                    'yearly_target' => 55,
                ],
                'conversion' => [
                    'lead_to_customer' => 8,
                    'visitor_to_lead' => 12,
                    'repeat_purchase' => 40,
                ],
            ],
            'manufacturing' => [
                'churn' => [
                    'active_days' => 180,
                    'at_risk_days' => 365,
                    'churned_days' => 730,
                    'grace_period_days' => 90,
                ],
                'segmentation' => [
                    'high_value_threshold' => 5000,
                    'loyal_customer_orders' => 6,
                    'loyal_customer_months' => 12,
                    'new_customer_days' => 60,
                    'vip_ltv_multiplier' => 3,
                ],
                'margins' => [
                    'min_gross_margin' => 15,
                    'target_gross_margin' => 30,
                    'excellent_gross_margin' => 45,
                    'min_net_margin' => 5,
                    'target_net_margin' => 12,
                ],
                'growth' => [
                    'monthly_target' => 2,
                    'quarterly_target' => 8,
                    'yearly_target' => 12,
                ],
                'retention' => [
                    'monthly_target' => 85,
                    'quarterly_target' => 80,
                    'yearly_target' => 75,
                ],
                'conversion' => [
                    'lead_to_customer' => 15,
                    'visitor_to_lead' => 25,
                    'repeat_purchase' => 60,
                ],
            ],
        ];
    }

    /**
     * Initialize default rules untuk fallback
     */
    private function initializeDefaultRules(): void
    {
        $this->defaultRules = [
            'churn' => [
                'active_days' => 60,
                'at_risk_days' => 120,
                'churned_days' => 180,
                'grace_period_days' => 30,
            ],
            'segmentation' => [
                'high_value_threshold' => 1000,
                'loyal_customer_orders' => 5,
                'loyal_customer_months' => 6,
                'new_customer_days' => 30,
                'vip_ltv_multiplier' => 5,
            ],
            'margins' => [
                'min_gross_margin' => 20,
                'target_gross_margin' => 35,
                'excellent_gross_margin' => 50,
                'min_net_margin' => 8,
                'target_net_margin' => 15,
            ],
            'growth' => [
                'monthly_target' => 5,
                'quarterly_target' => 15,
                'yearly_target' => 25,
            ],
            'retention' => [
                'monthly_target' => 80,
                'quarterly_target' => 75,
                'yearly_target' => 70,
            ],
            'conversion' => [
                'lead_to_customer' => 5,
                'visitor_to_lead' => 10,
                'repeat_purchase' => 30,
            ],
        ];
    }

    /**
     * Generate recommendations berdasarkan margin performance
     */
    private function getMarginRecommendations(string $performance, int $businessId): array
    {
        $businessType = $this->getBusinessType($businessId);

        $recommendations = [
            'poor' => [
                'Review pricing strategy',
                'Optimize cost structure',
                'Negotiate better supplier terms',
                'Consider value-added services',
                'Analyze competitor pricing',
            ],
            'acceptable' => [
                'Explore premium product lines',
                'Implement cost reduction initiatives',
                'Review operational efficiency',
                'Consider bundling strategies',
            ],
            'good' => [
                'Maintain current strategies',
                'Explore market expansion',
                'Invest in marketing',
                'Consider new product development',
            ],
            'excellent' => [
                'Scale successful strategies',
                'Invest in innovation',
                'Consider market leadership position',
                'Explore acquisition opportunities',
            ],
        ];

        return $recommendations[$performance] ?? [];
    }

    /**
     * Update business rules untuk specific business
     */
    public function updateBusinessRules(int $businessId, array $customRules): bool
    {
        try {
            DB::table('business_rules')->updateOrInsert(
                ['business_id' => $businessId],
                [
                    'business_id' => $businessId,
                    'rules' => json_encode($customRules),
                    'updated_at' => now(),
                ]
            );

            // Clear cache
            Cache::forget("business_type_{$businessId}");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all available business types dan rules mereka
     */
    public function getAvailableBusinessTypes(): array
    {
        return array_keys($this->industryRules);
    }

    /**
     * Get summary of rules untuk business type tertentu
     */
    public function getBusinessTypeRulesSummary(string $businessType): array
    {
        return $this->industryRules[$businessType] ?? $this->defaultRules;
    }
}
