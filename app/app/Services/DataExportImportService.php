<?php

namespace App\Services;

use App\Models\BusinessMetric;
use App\Models\MetricRecord;
use League\Csv\Reader;
use League\Csv\Writer;
use League\Csv\CannotInsertRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataExportImportService
{
    /**
     * Export metric records to CSV
     */
    public function exportToCsv(BusinessMetric $businessMetric, array $options = [])
    {
        try {
            $records = MetricRecord::where('business_metric_id', $businessMetric->id)
                ->orderBy('record_date', 'desc')
                ->get();

            $csv = Writer::createFromString('');

            // Add BOM for proper UTF-8 encoding
            $csv->insertOne(["\xEF\xBB\xBF"]);

            // Headers
            $headers = [
                'ID',
                'Date',
                'Value',
                'Notes',
                'Created At',
                'Updated At'
            ];

            $csv->insertOne($headers);

            // Data rows
            foreach ($records as $record) {
                $csv->insertOne([
                    $record->id,
                    $record->record_date->format('Y-m-d'),
                    $record->value,
                    $record->notes ?? '',
                    $record->created_at->format('Y-m-d H:i:s'),
                    $record->updated_at->format('Y-m-d H:i:s')
                ]);
            }

            $filename = 'metric_' . $businessMetric->id . '_' . str_replace(' ', '_', $businessMetric->metric_name) . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

            return [
                'success' => true,
                'content' => $csv->toString(),
                'filename' => $filename,
                'count' => $records->count()
            ];

        } catch (\Exception $e) {
            Log::error('CSV Export Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to export data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export metric records to Excel format (as CSV with .xlsx extension for compatibility)
     */
    public function exportToExcel(BusinessMetric $businessMetric, array $options = [])
    {
        try {
            $records = MetricRecord::where('business_metric_id', $businessMetric->id)
                ->orderBy('record_date', 'desc')
                ->get();

            $csv = Writer::createFromString('');

            // Headers
            $headers = ['ID', 'Date', 'Value', 'Notes', 'Created At', 'Updated At'];
            $csv->insertOne($headers);

            // Data rows
            foreach ($records as $record) {
                $csv->insertOne([
                    $record->id,
                    $record->record_date->format('Y-m-d'),
                    $record->value,
                    $record->notes ?? '',
                    $record->created_at->format('Y-m-d H:i:s'),
                    $record->updated_at->format('Y-m-d H:i:s')
                ]);
            }

            $filename = 'metric_' . $businessMetric->id . '_' . str_replace(' ', '_', $businessMetric->metric_name) . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

            // Create temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_export');
            file_put_contents($tempFile, $csv->toString());

            return [
                'success' => true,
                'file_path' => $tempFile,
                'filename' => $filename,
                'count' => $records->count()
            ];

        } catch (\Exception $e) {
            Log::error('Excel Export Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to export data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import data from CSV file
     */
    public function importFromCsv(BusinessMetric $businessMetric, UploadedFile $file, array $options = [])
    {
        try {
            $overwrite = $options['overwrite'] ?? false;
            $skipErrors = $options['skip_errors'] ?? true;

            // Read CSV file
            $csv = Reader::createFromPath($file->getPathname(), 'r');
            $csv->setHeaderOffset(0);

            $headers = $csv->getHeader();
            $records = $csv->getRecords();

            $importedCount = 0;
            $errorCount = 0;
            $errors = [];
            $existingCount = 0;

            DB::beginTransaction();

            // If overwrite is enabled, delete existing records
            if ($overwrite) {
                MetricRecord::where('business_metric_id', $businessMetric->id)->delete();
            }

            foreach ($records as $offset => $record) {
                try {
                    $rowNumber = $offset + 2; // CSV rows start from 2 (after header)

                    // Validate required fields (flexible to handle both old and new formats)
                    $date = $record['Date'] ?? '';
                    $value = $record['Value'] ?? '';

                    if (empty($date) || $value === '') {
                        throw new \Exception("Missing required fields (Date, Value)");
                    }

                    // Parse date
                    $parsedDate = Carbon::parse($date)->format('Y-m-d');
                    $parsedValue = floatval($value);
                    $notes = $record['Notes'] ?? '';

                    // Check if record already exists (if not overwriting)
                    if (!$overwrite) {
                        $existing = MetricRecord::where('business_metric_id', $businessMetric->id)
                            ->where('record_date', $parsedDate)
                            ->first();

                        if ($existing) {
                            $existingCount++;
                            if (!$skipErrors) {
                                throw new \Exception("Record for date {$parsedDate} already exists");
                            }
                            continue;
                        }
                    }

                    // Create new record (ignore ID, Created At, Updated At from import)
                    MetricRecord::create([
                        'business_metric_id' => $businessMetric->id,
                        'record_date' => $parsedDate,
                        'value' => $parsedValue,
                        'notes' => $notes
                    ]);

                    $importedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();

                    if (!$skipErrors) {
                        DB::rollBack();
                        return [
                            'success' => false,
                            'error' => "Import failed at row {$rowNumber}: " . $e->getMessage()
                        ];
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported_count' => $importedCount,
                'error_count' => $errorCount,
                'existing_count' => $existingCount,
                'errors' => $errors,
                'message' => "Successfully imported {$importedCount} records" .
                           ($errorCount > 0 ? " with {$errorCount} errors" : "") .
                           ($existingCount > 0 ? ", {$existingCount} records skipped (already exist)" : "")
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV Import Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to import data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import data from uploaded file (CSV or basic Excel support)
     */
    public function importFromFile(BusinessMetric $businessMetric, UploadedFile $file, array $options = [])
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'])) {
            return $this->importFromCsv($businessMetric, $file, $options);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            // For Excel files, we'll try to read as CSV if possible
            // This is a simplified approach without PhpSpreadsheet
            return $this->importFromCsv($businessMetric, $file, $options);
        } else {
            return [
                'success' => false,
                'error' => 'Unsupported file format. Please use CSV, TXT, XLS, or XLSX files.'
            ];
        }
    }

    /**
     * Get sample template for import (with same headers as table)
     */
    public function getSampleTemplate($format = 'csv')
    {
        $sampleData = [
            ['ID', 'Date', 'Value', 'Notes', 'Created At', 'Updated At'],
            ['1', now()->format('Y-m-d'), '1000', 'Sample data entry', now()->format('Y-m-d H:i:s'), now()->format('Y-m-d H:i:s')],
            ['2', now()->subDay()->format('Y-m-d'), '1500', 'Another sample entry', now()->format('Y-m-d H:i:s'), now()->format('Y-m-d H:i:s')],
            ['3', now()->subDays(2)->format('Y-m-d'), '1200', '', now()->format('Y-m-d H:i:s'), now()->format('Y-m-d H:i:s')]
        ];

        // Always return CSV format for simplicity
        $csv = Writer::createFromString('');
        $csv->insertAll($sampleData);

        return [
            'success' => true,
            'content' => $csv->toString(),
            'filename' => 'import_template_sample.csv'
        ];
    }

    /**
     * Validate import file structure (minimal validation)
     */
    public function validateImportFile(UploadedFile $file)
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, ['csv', 'txt', 'xlsx', 'xls'])) {
                return [
                    'valid' => false,
                    'error' => 'Unsupported file format. Please use CSV, TXT, XLS, or XLSX files.'
                ];
            }

            // Check file size (max 10MB)
            if ($file->getSize() > 10485760) {
                return [
                    'valid' => false,
                    'error' => 'File too large. Maximum size is 10MB.'
                ];
            }

            // Try to read first few lines to validate structure
            $csv = Reader::createFromPath($file->getPathname(), 'r');
            $csv->setHeaderOffset(0);

            $headers = $csv->getHeader();

            // Basic validation - just check if we have headers
            if (empty($headers)) {
                return [
                    'valid' => false,
                    'error' => 'No headers found in file. Please ensure first row contains column headers.'
                ];
            }

            // Count records (optional - just for info)
            $recordCount = 0;
            foreach ($csv->getRecords() as $record) {
                $recordCount++;
                if ($recordCount >= 1000) { // Limit check for large files
                    break;
                }
            }

            return [
                'valid' => true,
                'headers' => $headers,
                'estimated_records' => $recordCount >= 1000 ? '1000+' : $recordCount,
                'file_size' => $this->formatBytes($file->getSize()),
                'message' => 'File appears valid. Required columns: Date, Value (case sensitive)'
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Error reading file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
