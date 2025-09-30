<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Log an activity for the current user and business
     */
    protected function logActivity(string $type, string $title, string $description, array $options = []): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Get user's business
        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business && !($options['allow_no_business'] ?? false)) {
            return;
        }

        $defaultIcons = [
            'auth' => 'bi-shield-check',
            'user_joined' => 'bi-person-check',
            'data_input' => 'bi-plus-circle',
            'data_update' => 'bi-pencil',
            'data_delete' => 'bi-trash',
            'metric_created' => 'bi-graph-up',
            'metric_updated' => 'bi-pencil-square',
            'metric_deleted' => 'bi-trash',
            'invitation_sent' => 'bi-envelope-plus',
            'invitation_accepted' => 'bi-person-check',
            'settings_updated' => 'bi-gear',
            'profile_updated' => 'bi-person-gear',
            'dashboard_viewed' => 'bi-eye',
            'export' => 'bi-download',
            'import' => 'bi-upload',
        ];

        $defaultColors = [
            'auth' => 'info',
            'user_joined' => 'success',
            'data_input' => 'primary',
            'data_update' => 'warning',
            'data_delete' => 'danger',
            'metric_created' => 'success',
            'metric_updated' => 'warning',
            'metric_deleted' => 'danger',
            'invitation_sent' => 'info',
            'invitation_accepted' => 'success',
            'settings_updated' => 'primary',
            'profile_updated' => 'primary',
            'dashboard_viewed' => 'info',
            'export' => 'success',
            'import' => 'success',
        ];

        ActivityLog::create([
            'business_id' => $business?->id,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'icon' => $options['icon'] ?? $defaultIcons[$type] ?? 'bi-info-circle',
            'color' => $options['color'] ?? $defaultColors[$type] ?? 'info',
            'metadata' => json_encode(array_merge([
                'user_role' => $user->userRole->name ?? 'Unknown',
                'timestamp' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $options['metadata'] ?? []))
        ]);
    }

    /**
     * Log metric-related activities
     */
    protected function logMetricActivity(string $action, $metric, array $options = []): void
    {
        $metricName = is_object($metric) ? $metric->name : $metric;
        
        $titles = [
            'viewed' => 'Metric Accessed',
            'created' => 'Metric Created',
            'updated' => 'Metric Updated', 
            'deleted' => 'Metric Deleted',
            'record_added' => 'Data Added',
            'record_updated' => 'Data Updated',
            'record_deleted' => 'Data Deleted',
        ];

        $descriptions = [
            'viewed' => "Accessed metric: {$metricName}",
            'created' => "Created new metric: {$metricName}",
            'updated' => "Updated metric: {$metricName}",
            'deleted' => "Deleted metric: {$metricName}",
            'record_added' => "Added data to metric: {$metricName}",
            'record_updated' => "Updated data in metric: {$metricName}",
            'record_deleted' => "Deleted data from metric: {$metricName}",
        ];

        $type = in_array($action, ['record_added', 'record_updated', 'record_deleted']) 
            ? 'data_input' 
            : 'metric_' . $action;

        $this->logActivity(
            $type,
            $titles[$action] ?? 'Metric Activity',
            $descriptions[$action] ?? "Performed {$action} on metric: {$metricName}",
            array_merge([
                'metadata' => [
                    'metric_name' => $metricName,
                    'action' => $action,
                ]
            ], $options)
        );
    }

    /**
     * Log dashboard activities
     */
    protected function logDashboardActivity(string $page, array $options = []): void
    {
        $this->logActivity(
            'dashboard_viewed',
            'Dashboard Page Accessed',
            "Viewed {$page} page",
            array_merge([
                'metadata' => [
                    'page' => $page,
                ]
            ], $options)
        );
    }
}