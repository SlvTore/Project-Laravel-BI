<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Olap\OlapLocationService;
use Illuminate\Http\Request;

class OlapLocationController extends Controller
{
    public function __construct(
        private OlapLocationService $locationService
    ) {}

    /**
     * Get location hierarchy for business
     * GET /dashboard/olap/location/hierarchy
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hierarchy(Request $request)
    {
        try {
            $businessId = $request->user()->primaryBusiness()->first()->id;

            $hierarchy = $this->locationService->getLocationHierarchy($businessId);

            return response()->json([
                'success' => true,
                'data' => $hierarchy
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load hierarchy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Drill-down sales by location
     * GET /dashboard/olap/location/drill-down
     *
     * Query params:
     * - level: 'provinsi' | 'kota' | 'kecamatan'
     * - parent: parent location name (for filtering)
     * - parent_level: 'provinsi' | 'kota'
     * - start_date, end_date (optional)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drillDown(Request $request)
    {
        try {
            $businessId = $request->user()->primaryBusiness()->first()->id;

            $level = $request->input('level', 'provinsi');
            $parentFilter = $request->input('parent');
            $parentLevel = $request->input('parent_level');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Validate level
            if (!in_array($level, ['provinsi', 'kota', 'kecamatan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid level. Must be: provinsi, kota, or kecamatan'
                ], 400);
            }

            $data = $this->locationService->drillDownSalesByLocation(
                $businessId,
                $level,
                $parentFilter,
                $parentLevel,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'level' => $level,
                'parent' => $parentFilter,
                'parent_level' => $parentLevel,
                'count' => count($data),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load drill-down data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales trend for specific location
     * GET /dashboard/olap/location/trend
     *
     * Query params:
     * - location: location name (required)
     * - level: 'provinsi' | 'kota' | 'kecamatan'
     * - granularity: 'daily' | 'monthly'
     * - start_date, end_date (optional)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trend(Request $request)
    {
        try {
            $businessId = $request->user()->primaryBusiness()->first()->id;

            $location = $request->input('location');
            $level = $request->input('level', 'kota');
            $granularity = $request->input('granularity', 'daily');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location parameter is required'
                ], 400);
            }

            // Validate level
            if (!in_array($level, ['provinsi', 'kota', 'kecamatan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid level. Must be: provinsi, kota, or kecamatan'
                ], 400);
            }

            // Validate granularity
            if (!in_array($granularity, ['daily', 'monthly'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid granularity. Must be: daily or monthly'
                ], 400);
            }

            $data = $this->locationService->getSalesTrendByLocation(
                $businessId,
                $location,
                $level,
                $granularity,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'location' => $location,
                'level' => $level,
                'granularity' => $granularity,
                'count' => count($data),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load trend data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top performing locations
     * GET /dashboard/olap/location/top
     *
     * Query params:
     * - level: 'provinsi' | 'kota' | 'kecamatan'
     * - metric: 'revenue' | 'margin' | 'transactions'
     * - limit: number of results (default 10)
     * - start_date, end_date (optional)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function top(Request $request)
    {
        try {
            $businessId = $request->user()->primaryBusiness()->first()->id;

            $level = $request->input('level', 'provinsi');
            $metric = $request->input('metric', 'revenue');
            $limit = (int) $request->input('limit', 10);
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Validate level
            if (!in_array($level, ['provinsi', 'kota', 'kecamatan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid level. Must be: provinsi, kota, or kecamatan'
                ], 400);
            }

            // Validate metric
            if (!in_array($metric, ['revenue', 'margin', 'transactions'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid metric. Must be: revenue, margin, or transactions'
                ], 400);
            }

            // Validate limit
            if ($limit < 1 || $limit > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limit must be between 1 and 100'
                ], 400);
            }

            $data = $this->locationService->getTopLocationsByLevel(
                $businessId,
                $level,
                $metric,
                $limit,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'level' => $level,
                'metric' => $metric,
                'limit' => $limit,
                'count' => count($data),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load top locations: ' . $e->getMessage()
            ], 500);
        }
    }
}
