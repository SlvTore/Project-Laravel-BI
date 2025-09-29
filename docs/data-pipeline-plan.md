# Traction Tracker Data Pipeline Plan

## Goals
- Enable business users to ingest operational CSV data through an interactive Data Feeds workbench.
- Validate, normalize, and stage uploaded data before committing to the warehouse.
- Automate ETL into the OLAP schema, enriching fact tables with revenue and margin metrics.
- Surface aggregated KPIs via APIs that power interactive charts on the Metrics dashboard.

## End-to-End Flow Overview
1. **Ingest (UI + API)**
   - Users drag & drop CSV files on `dashboard/data-feeds`.
   - Frontend calls `POST /dashboard/data-feeds/preview` for a quick staging preview.
   - Users resolve validation issues (missing products, invalid data) inline.
   - `POST /dashboard/data-feeds/commit` starts the ETL process using an upload token.
2. **Stage**
   - Raw rows stored temporarily (`storage/app/tmp/{uuid}.csv`).
   - Preview API parses first _N_ rows; returns headers, validation flags, and fuzzy product matches.
   - Commit API re-parses entire file, populates staging tables (`staging_sales_items`, `staging_costs`).
3. **Transform & Load**
   - `DataFeedService` orchestrates validation and staging writes.
   - `ProcessDataFeedJob` (queued) calls `OlapWarehouseService::loadFactsFromStaging`.
   - ETL resolves dimensions, calculates `gross_revenue`, `cogs_amount`, `gross_margin_amount`, and `%` columns, and loads `fact_sales`.
4. **Aggregate & Serve**
   - `OlapMetricAggregator` queries fact tables/materialized views.
   - `OlapMetricsController` exposes KPI endpoints consumed by Metrics dashboard charts.

## Detailed Contracts

### 1. Preview Endpoint (`POST /dashboard/data-feeds/preview`)
- **Payload (multipart/form-data)**
  - `file`: CSV upload (required)
  - `data_type`: `sales | costs` (default `sales`)
- **Response (200)**
```json
{
  "success": true,
  "upload_token": "uuid",
  "headers": ["transaction_date", "product_name", ...],
  "rows": [
    {
      "original": {"product_name": "Latte", "quantity": "10", ...},
      "normalized": {"product_name": "Latte", "quantity": 10, ...},
      "valid": true,
      "issues": [],
      "product_match": {"status": "exact", "product_id": 12}
    }
  ],
  "summary": {
    "total_rows": 25,
    "valid_rows": 23,
    "invalid_rows": 2,
    "new_product_candidates": ["Cold Brew"]
  }
}
```
- **Validation Rules**
  - Required columns inferred from templates (sales: `product_name`, `quantity`, `price`, `transaction_date`).
  - Numeric columns parsed with locale-safe casting.
  - Dates normalized via Carbon parsing; invalid -> issue flag.
  - Product lookup uses business context + case-insensitive match; fallback to fuzzy (`similar_text >= 70`).
- **Side Effects**: Persist upload file to `storage/app/tmp/{uuid}.csv` (overwriting prior token).

### 2. Commit Endpoint (`POST /dashboard/data-feeds/commit`)
- **Payload (JSON)**
```json
{
  "upload_token": "uuid",
  "data_type": "sales",
  "auto_create_products": true,
  "business_id": 1
}
```
- **Flow**
  1. Validate token exists and belongs to authenticated business.
  2. Re-parse cached CSV with full validation.
  3. Create `data_feeds` record (`status = processing`).
  4. Insert rows into staging tables (`staging_sales_items` or `staging_costs`).
     - Auto-create products if flag set and validation suggested candidates.
  5. Dispatch `ProcessDataFeedJob` (queue) for async ETL.
  6. Return 202 Accepted with feed id + status.
- **Response**
```json
{
  "success": true,
  "feed_id": 42,
  "record_count": 120,
  "status": "queued"
}
```
- **Error Handling**
  - Token expired/missing → 410 Gone.
  - Validation failures → 422 with row-level errors.
  - Unexpected → 500 with logging.

### 3. Auto-create Missing Products (`POST /dashboard/data-feeds/auto-create-products`)
- Accepts `products: [{name, category?, unit?, selling_price?, cost_price?}]`.
- Uses `DataFeedService::createProduct` to insert bound to business and returns map `{name -> product_id}`.
- Exposed to front-end to resolve yellow-highlighted rows before commit.

### 4. ProcessDataFeedJob Enhancements
- Update job to:
  - Guard against double processing with idempotent check (`fact_sales` existing entries for feed?).
  - Call `OlapWarehouseService::loadFactsFromStaging` → returns counts + metrics.
  - Update feed record `status` (`transforming` → `transformed`) and `log_message` summarizing metrics.

### 5. OLAP Schema Updates
- **Migration `2025_09_24_000000_extend_olap_schema_for_all_metrics`**
  - Add indexes for new aggregations (e.g., `idx_fs_biz_date_product`).
  - Create/refresh supporting views:
    - `vw_sales_unified` (joins fact + dim for product & date info).
    - `vw_sales_daily` (daily sums of gross revenue, margin, quantity).
    - `vw_sales_product_daily` (daily metrics per product).
    - `vw_margin_daily`, `vw_cogs_daily`, `vw_new_customers_daily`, `vw_returning_customers_daily`.
  - Seed `dim_channel` defaults if empty (`Online`, `Offline`).
- **Migration `2025_09_28_000500_add_margin_columns_to_fact_sales_table`**
  - Add columns:
    - `gross_revenue` decimal(18,2)
    - `cogs_amount` decimal(18,2)
    - `gross_margin_amount` decimal(18,2)
    - `gross_margin_percent` decimal(5,2)
  - Backfill existing rows using `unit_price`, `quantity`, and product cost data.
  - Down migration drops the columns.

### 6. Warehouse Loader (`OlapWarehouseService`)
- Extend `loadFactsFromStaging` to:
  - Resolve product cost from `products.cost_price + productionCosts sum`.
  - Calculate metrics per row:
    - `gross_revenue = quantity * unit_price`
    - `cogs_amount = quantity * unit_cost`
    - `gross_margin_amount = gross_revenue - cogs_amount - discount`
    - `gross_margin_percent = gross_margin_amount / NULLIF(gross_revenue, 0)`
  - Support both sales and cost feeds (future extension: cost feeds update dim tables or allocate to COGS).
  - Soft-delete staging rows after success (or mark processed).

### 7. Metrics Aggregator Contracts
- **Service Methods**
  - `summary(int $businessId, DateRange $range)` → totals (sales, cogs, margin %, avg order value).
  - `topProducts(int $businessId, DateRange $range, int $limit)` → list for charts.
  - `trend(int $businessId, DateRange $range, string $interval)` → timeseries for line chart.
- **Controller Endpoints**
  - `GET /dashboard/metrics/kpi` → summary cards.
  - `GET /dashboard/metrics/top-products` → bar chart data.
  - `GET /dashboard/metrics/trend` → line chart.
- **Filters**
  - Query params: `range=last_7_days|last_30_days|this_quarter|custom`, `group_by=day|week|month`, optional `product_id`.

### 8. Frontend Enhancements
- **Data Feeds View (`resources/views/dashboard-data-feeds/index.blade.php`)**
  - Drag/drop zone with progress feedback.
  - Preview table with badges (valid/invalid) and highlight for missing products.
  - Side-by-side Product Management panel with “Create Missing” button.
  - Post-commit auto-refresh for product cards and recent transactions using existing AJAX loaders.
- **Metrics View (`resources/views/dashboard-metrics/index.blade.php`)**
  - Fetch KPI JSON via `fetch` on filter changes.
  - Render charts using ApexCharts (line, bar, donut/gauge).
  - Provide drill-down interactions (click top product → load product-specific trend).

### 9. Testing Strategy
- **Feature Tests**
  - `DataFeedPreviewTest` (happy path, missing columns, invalid data).
  - `DataFeedCommitTest` (valid commit, token expiry, auto-create products).
  - `MetricsApiTest` (summary + trend endpoints with seeded data).
- **Unit Tests**
  - `OlapWarehouseServiceTest` for metric calculations.
  - `OlapMetricAggregatorTest` for query ranges.
- **Browser/JS Smoke Tests**
  - Use Pest/Laravel Dusk (optional future) to ensure preview UI toggles correctly.

### 10. Ops & Observability
- Queue: ensure `ProcessDataFeedJob` uses `default` queue; document artisan commands (`php artisan queue:work`).
- Logging: centralize ETL logs to `storage/logs/etl.log` (channel) for troubleshooting.
- Config: expose CSV preview row limit (`config/data_feeds.php`), token TTL, and queue behavior.

## Implementation Sequencing
1. Implement preview + temp storage service utilities.
2. Hook preview endpoint and front-end interactions.
3. Implement commit flow and job dispatch.
4. Extend migrations/schema, update warehouse loader.
5. Add aggregator endpoints and front-end charts.
6. Write tests and update documentation/README.

## Risks & Mitigations
- **Large Files**: enforce max file size (configurable) and stream parsing for commit.
- **Duplicate Loads**: mark staging rows processed and guard ETL idempotency.
- **Product Cost Availability**: fall back to `products.cost_price` or `0` with alert if cost missing.
- **Async Failures**: expose transform status endpoint (already available) + UI polling.

## Deliverables Summary
- Backend endpoints: preview, commit, auto-create products, KPI JSON.
- Frontend updates: Data Feeds workbench, Metrics charts.
- Warehouse schema migrations with revenue/margin measures.
- Services updated for ETL + aggregation.
- Comprehensive tests + docs.
