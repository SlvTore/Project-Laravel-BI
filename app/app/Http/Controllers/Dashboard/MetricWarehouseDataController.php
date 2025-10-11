<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MetricWarehouseDataController extends Controller
{
    /**
     * Generic endpoint: /dashboard/metrics/{metricId}/warehouse-data
     * Query params: sort, dir, page, per_page
     */
    public function index(Request $request, int $metricId)
    {
        $user = Auth::user();
        $business = null;
        if ($user) {
            // Try owned business first
            $business = \App\Models\Business::where('user_id', $user->id)->first();
            if (!$business) {
                $business = \App\Models\Business::join('business_user','business_user.business_id','=','businesses.id')
                    ->where('business_user.user_id',$user->id)
                    ->select('businesses.*')
                    ->first();
            }
        }
        if (!$business) {
            return response()->json(['success'=>false,'message'=>'Business context missing'],422);
        }

        $metric = DB::table('business_metrics')->where('id',$metricId)->where('business_id',$business->id)->first();
        if (!$metric) {
            return response()->json(['success'=>false,'message'=>'Metric not found'],404);
        }

        $name = $metric->metric_name;
        $config = $this->map($name);
        if (!$config) {
            return response()->json(['success'=>false,'message'=>'No warehouse mapping for this metric'],400);
        }

        $sort = $request->input('sort', $config['default_sort']);
        $dir = strtolower($request->input('dir','desc')) === 'asc' ? 'asc':'desc';
        $page = max(1, (int)$request->input('page',1));
        $perPage = (int)$request->input('per_page',25);
        if ($perPage < 1 || $perPage > 200) $perPage = 25;

        $query = ($config['builder'])($business->id);

        // Whitelist sorting
        if (!in_array($sort, $config['sortable'], true)) {
            $sort = $config['default_sort'];
        }
        $query->orderBy($sort, $dir);

        $total = $query->count();
        $rows = $query->forPage($page, $perPage)->get();

        return response()->json([
            'success' => true,
            'metric' => $name,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'rows' => $rows,
            'columns' => $config['columns'],
        ]);
    }

    private function map(string $name): ?array
    {
        return match($name) {
            'Total Penjualan' => [
                'builder' => function(int $businessId) {
                    return DB::table('vw_sales_daily')
                        ->where('business_id',$businessId)
                        ->select('sales_date','total_revenue','total_quantity','total_discount','total_tax','total_shipping');
                },
                'columns' => ['sales_date','total_revenue','total_quantity','total_discount','total_tax','total_shipping'],
                'sortable' => ['sales_date','total_revenue','total_quantity'],
                'default_sort' => 'sales_date'
            ],
            'Biaya Pokok Penjualan (COGS)' => [
                'builder' => fn(int $bid) => DB::table('vw_cogs_daily')->where('business_id',$bid)->select('sales_date','total_cogs'),
                'columns' => ['sales_date','total_cogs'],
                'sortable' => ['sales_date','total_cogs'],
                'default_sort' => 'sales_date'
            ],
            'Margin Keuntungan (Profit Margin)' => [
                'builder' => fn(int $bid) => DB::table('vw_margin_daily')->where('business_id',$bid)->select('sales_date','total_margin'),
                'columns' => ['sales_date','total_margin'],
                'sortable' => ['sales_date','total_margin'],
                'default_sort' => 'sales_date'
            ],
            'Penjualan Produk Terlaris' => [
                'builder' => fn(int $bid) => DB::table('vw_sales_product_daily')->where('business_id',$bid)->select('sales_date','product_name','total_quantity','total_revenue','total_margin'),
                'columns' => ['sales_date','product_name','total_quantity','total_revenue','total_margin'],
                'sortable' => ['sales_date','total_quantity','total_revenue','total_margin'],
                'default_sort' => 'sales_date'
            ],
            'Jumlah Pelanggan Baru' => [
                'builder' => fn(int $bid) => DB::table('vw_new_customers_daily')->where('business_id',$bid)->select('sales_date','new_customers'),
                'columns' => ['sales_date','new_customers'],
                'sortable' => ['sales_date','new_customers'],
                'default_sort' => 'sales_date'
            ],
            'Jumlah Pelanggan Setia' => [
                'builder' => fn(int $bid) => DB::table('vw_returning_customers_daily')->where('business_id',$bid)->select('sales_date','returning_customers'),
                'columns' => ['sales_date','returning_customers'],
                'sortable' => ['sales_date','returning_customers'],
                'default_sort' => 'sales_date'
            ],
            default => null,
        };
    }
}
