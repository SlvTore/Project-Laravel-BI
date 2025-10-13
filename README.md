# Traction Tracker — Laravel Business Intelligence Platform

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
  <img src="https://img.shields.io/badge/AI%20Powered-Gemini-purple.svg" alt="AI Powered">
</p>

Traction Tracker adalah platform Business Intelligence (BI) berbasis Laravel untuk melacak, menganalisis, dan mengoptimalkan performa bisnis. Sistem ini menggabungkan OLAP (dimensional modeling) dan AI Assistant (Gemini) untuk menghadirkan insights yang cepat, terukur, dan dapat ditindaklanjuti.

---

## Daftar Isi

1. Ringkasan & Visi Produk
2. Arsitektur Sistem (OLTP → ETL → OLAP → Aggregator → UI/AI)
3. Alur Pengguna end‑to‑end (Landing → Wizard → Dashboard → Metric → AI)
4. RBAC & Multi‑Tenant Security
5. Dimensional OLAP Cubes & Materialized Views
6. Data Pipeline & ETL (Staging, Jobs, Backfill)
7. Aggregation Layer & API Kontrak
8. Frontend Analytics (ApexCharts) & Data Warehouse Integration
9. AI Business Assistant (Gemini)
10. Best Practices (Data, Performansi, SCD, Waktu)
11. Instalasi & Setup
12. Troubleshooting
13. Roadmap
14. Lisensi & Dukungan

---

## 1) Ringkasan & Visi Produk

- Multi-metric tracking dengan 6 metrik utama (Penjualan, COGS, Margin, Pelanggan Baru, Pelanggan Setia, Produk Terlaris).
- OLAP star schema untuk analitik cepat tanpa mengganggu OLTP.
- Visualisasi interaktif via ApexCharts dengan prioritas data dari warehouse (views).
- AI Assistant untuk analisis tren, strategi, dan rekomendasi berbasis konteks bisnis.

---

## 2) Arsitektur Sistem

Lapisan utama:
- OLTP (aplikasi Laravel + basis data transaksi ringan/metrics manual)
- ETL/Jobs (transformasi → dimension/fact)
- OLAP (star schema + materialized views)
- Aggregation Service (query siap pakai per metric)
- UI (Blade + ApexCharts) & AI (Gemini)

```mermaid
flowchart LR
  A[OLTP Inputs\nForms / Import] --> B[ETL Jobs\nQueues / Scripts]
  B --> C[(Star Schema\nFacts & Dimensions)]
  C --> D[Materialized Views\n(vw_* daily/monthly)]
  D --> E[OlapMetricAggregator\nService Layer]
  E --> F[Controllers\nMetricRecordsController]
  F --> G[UI: Blade + ApexCharts]
  F --> H[AI Assistant (Gemini)]
```

Referensi:
- Services: `app/app/Services/OlapMetricAggregator.php`
- Controllers: `app/app/Http/Controllers/Dashboard/MetricRecordsController.php`
- Views (Edit Metric + AI): `app/resources/views/dashboard-metrics/edit.blade.php`
- AI Frontend: `app/public/js/ai-chat.js`
- Catatan data pipeline: `docs/data-pipeline-plan.md`, `docs/transaction-table-fix.md`, `docs/table-structure-fixes.md`

---

## 3) Alur Pengguna end‑to‑end

1. Landing Page → Registrasi
   - File: `resources/views/landing/welcome.blade.php`
   - CTA “Mulai Gratis” memulai onboarding.

2. Wizard Setup
   - File: `resources/views/wizard.blade.php`
   - Step 1: Pilih Role (Owner/Manager/Mentor/Investigator)
   - Step 2: Profil Bisnis (industri, deskripsi, baseline)
   - Step 3: Target & Goals (revenue, customer, growth)

3. Dashboard Utama
   - Route: `/dashboard` (`routes/web.php`)
   - Menampilkan ringkasan metrik dan quick actions.

4. Kelola Metric
   - Buat: `/dashboard/metrics/create`
   - Edit/Records: `/dashboard/metrics/{metric}/records/edit`
   - 6 metric utama sudah memiliki form dan logika kalkulasi.

5. Input Data Harian
   - Tambah baris data sesuai periode (harian/mingguan/bulanan).
   - Validasi dan kalkulasi real-time di UI.

6. ETL → Warehouse
   - Data mentah diproses menjadi dimensi/fakta.
   - Views OLAP menyediakan ringkasan siap pakai untuk chart/AI.

7. Analytics & AI
   - Trend chart, summary donut, kartu statistik → prioritas dari OLAP views.
   - AI chat untuk insight, strategi, dan analisis mendalam per metric.

---

## 4) RBAC & Multi‑Tenant Security

- Role contoh: Business Owner, Manager, Mentor/Advisor, Data Investigator.
- Setiap user hanya mengakses bisnis/metrik yang berizin.
- Enforcement points:
  - Controller-level guard & policy checks (lihat `MetricRecordsController` – otorisasi berbasis relasi `business_id`).
  - Middleware auth & role checks.
- Audit & logging: `app/app/Models/ActivityLog.php`, `Listeners/AuthActivityListener.php`.
- Optimasi & praktik RBAC: lihat `RBAC_OPTIMIZATION_IMPLEMENTATION.md`.

Prinsip keamanan:
- Scoping by `business_id` pada semua query list/records.
- Validasi server-side pada update/delete.
- Hindari kebocoran data antar tenant.

---

## 5) Dimensional OLAP Cubes & Views

Star schema inti:
- `dim_date` (grain: 1 row per tanggal)
- `dim_product` (natural key: product_nk)
- `dim_customer` (natural key: customer_nk)
- `dim_channel` (opsional/future)
- `fact_sales` (grain: per item transaksi)

Materialized/MySQL Views utama:
- `vw_sales_daily` → sales_date, total_revenue, total_quantity, transaction_count
- `vw_sales_product_daily` → sales_date, product_name, total_revenue, total_quantity
- `vw_cogs_daily` → sales_date, total_cogs
- `vw_margin_daily` → sales_date, total_margin
- `vw_new_customers_daily` → sales_date, new_customers
- `vw_returning_customers_daily` → sales_date, returning_customers

Catatan implementasi:
- Jika `fact_sales.cogs_amount` belum tersedia, gunakan estimasi (harga pokok dari dim_product) sebagai fallback (lihat dokumen di folder `docs/`).
- Indeks disarankan: `(business_id, sales_date)` pada views/tabel aggregator untuk mempercepat range scan.

---

## 6) Data Pipeline & ETL

Sumber data:
- Input manual metric (`metric_records`) dan/atau transaksi mentah.

Transformasi:
- Jobs/queues untuk memuat dimensi dan fakta: contoh `app/app/Jobs/ProcessDataFeedJob.php`.
- Skrip verifikasi/diagnostik: lihat root scripts `check_*.php`, `verify_*.php`, `manual_etl_trigger.php` untuk membantu during dev.

Backfill & Enrichment (opsional):
- Backfill OLAP pertama kali atau recovery.
- Estimasi COGS bila kolom belum lengkap.

Ops (opsional):
```bash
# Opsional: contoh perintah util selama pengembangan
php artisan queue:work        # Memproses jobs ETL
php artisan migrate --seed    # Skema & data awal
```

Lebih lanjut: `docs/data-pipeline-plan.md`, `docs/transaction-table-fix.md`, `docs/table-structure-fixes.md`.

---

## 7) Aggregation Layer & API Kontrak

Service: `App\Services\OlapMetricAggregator`
- `dailyRevenue(businessId, days)`
- `dailyCogs(businessId, days)`
- `dailyMargin(businessId, days)`
- `dailyNewCustomers(businessId, days)`
- `dailyReturningCustomers(businessId, days)`
- `topProducts(businessId, days, limit)`

Controller: `MetricRecordsController`
- Memilih sumber dari OLAP terlebih dahulu; fallback ke `metric_records`.
- Endpoint penting (lihat `routes/web.php`):
  - `GET /dashboard/metrics/{metric}/chart-data` → data tren
  - `GET /dashboard/metrics/{metric}/statistics` → statistik ringkas
  - `POST /dashboard/metrics/{metric}/ai-chat` → integrasi AI

---

## 8) Frontend Analytics (ApexCharts)

- Loader ApexCharts dengan multi-CDN fallback & timeout untuk reliabilitas.
- Trend chart memprioritaskan `warehouseData.daily` dari OLAP views; label tanggal konsisten (moment.js).
- Summary donut memprioritaskan `warehouseData.monthly` untuk komposisi metrik per bulan.
- Implementasi utama: `resources/views/dashboard-metrics/edit.blade.php`.

---

## 9) AI Business Assistant (Gemini)

UI/UX:
- Chat bubble modern (AI kiri, user kanan), tails, animasi, dan typography putih untuk keterbacaan.
- File: `public/js/ai-chat.js` dan markup di `dashboard-metrics/edit.blade.php`.

Back-end:
- Endpoint: `POST /dashboard/metrics/{id}/ai-chat` (lihat `MetricRecordsController@askAI`).
- Konteks AI: data tren, statistik, dan metadata bisnis terbaru.

Kemampuan respons:
- Markdown-like formatting, list, code blocks, links.
- Typing indicator, status badge, export chat.

Konfigurasi:
- `GEMINI_API_KEY` di `.env`.

---

## 10) Best Practices

Data & ETL:
- Idempotent ETL: aman untuk rerun.
- Satu sumber kebenaran (facts), gunakan views untuk konsumsi analitik.
- Hindari kalkulasi bisnis kompleks di UI — letakkan di pipeline/aggregator.

Waktu & Zona:
- Simpan tanggal/ waktu dalam UTC; format di UI sesuai lokal.
- Gunakan `dim_date` untuk kalender dan interval.

Performansi:
- Indeks `(business_id, date)` pada fakta & views.
- Hindari N+1 query; gunakan aggregator/service layer.

RBAC & Keamanan:
- Selalu filter data dengan `business_id` user.
- Validasi permintaan tulis/hapus di server-side.

Penamaan & Skema:
- Konsisten: snake_case untuk kolom, prefiks `vw_` untuk views.
- Dokumentasikan perubahan skema di folder `docs/`.

---

## 11) Instalasi & Setup

Persyaratan
- PHP 8.2+, Laravel 11.x, MySQL 8.0+, Node.js 18+, Composer

Langkah:
1) Clone & masuk folder `app`
2) `composer install` dan `npm install`
3) Salin env + generate key
4) Konfigurasi database & `GEMINI_API_KEY`
5) `php artisan migrate --seed`
6) `npm run dev` (atau `npm run build`)
7) `php artisan serve`

Catatan: perintah di atas opsional sebagai panduan dev lokal.

---

## 12) Troubleshooting

Charts tidak tampil:
- Periksa konsol untuk error ApexCharts/CDN. Loader sudah memiliki fallback; pastikan jaringan/CDN accessible.

Data warehouse kosong:
- Jalankan ETL/queues dan cek views `vw_*`. Lihat skrip verifikasi di root (`verify_*.php`, `check_*.php`).

AI Chat error:
- Pastikan `GEMINI_API_KEY` benar. Cek endpoint `POST /ai-chat` dan log Laravel.

RBAC akses ditolak:
- Pastikan user terkait dengan `business_id` yang sama dan role sesuai.

Cache tidak memperbarui UI:
- `php artisan view:clear` dan refresh browser (hard refresh/Incognito).

---

## 13) Roadmap

- View churn & retention cohort.
- Enrichment `fact_sales.cogs_amount` dari sumber resmi (ETL update).
- Incremental snapshots (inventory, AR aging).
- Laporan terjadwal & ekspor lanjutan.

---

## 14) Lisensi & Dukungan

Lisensi: [MIT](https://opensource.org/licenses/MIT)

Dukungan: mfadil25@outlook.com

—

Catatan referensi cepat:
- OLAP Aggregator: `app/app/Services/OlapMetricAggregator.php`
- Controller metrics: `app/app/Http/Controllers/Dashboard/MetricRecordsController.php`
- View metrics (edit + AI): `app/resources/views/dashboard-metrics/edit.blade.php`
- Frontend AI chat: `app/public/js/ai-chat.js`
- Dokumen: `docs/data-pipeline-plan.md`, `docs/transaction-table-fix.md`, `docs/table-structure-fixes.md`
