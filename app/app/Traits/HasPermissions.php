<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    // Access normalized permission relationship through role
    public function permissionItems()
    {
        return $this->userRole?->permissionItems() ?? collect();
    }

    protected function permissionCacheKey(): string
    {
        return 'user:'.$this->id.':perms:v1';
    }

    public function refreshPermissionCache(): void
    {
        Cache::forget($this->permissionCacheKey());
        $this->cachePermissions();
    }

    protected function cachePermissions(): array
    {
        return Cache::remember($this->permissionCacheKey(), 3600, function () {
            $codes = [];
            // Pivot-based permissions
            if ($this->userRole && method_exists($this->userRole, 'permissionItems')) {
                $this->userRole->loadMissing('permissionItems');
                $codes = $this->userRole->permissionItems->where('is_active', true)->pluck('code')->all();
            }
            // Legacy JSON fall back
            if ($this->userRole && $this->userRole->permissions && is_array($this->userRole->permissions)) {
                foreach ($this->userRole->permissions as $legacy) {
                    if ($legacy === 'all') {
                        $codes[] = '*.*';
                    } else {
                        $codes[] = $legacy; // Already like resource.action (mapped previously)
                    }
                }
            }
            return array_values(array_unique($codes));
        });
    }

    public function allPermissionCodes(): array
    {
        return $this->cachePermissions();
    }

    public function can($ability, $arguments = []): bool
    {
        return $this->hasPermissionCode($ability);
    }

    public function hasPermission(string $code): bool
    {
        return $this->hasPermissionCode($code);
    }

    protected function hasPermissionCode(string $code): bool
    {
        $codes = $this->allPermissionCodes();
        if (in_array('*.*', $codes, true)) {
            return true;
        }
        // Exact
        if (in_array($code, $codes, true)) return true;
        // If scoped, degrade to unscoped
        $parts = explode('.', $code);
        if (count($parts) === 3) {
            [$res,$act,$scope] = $parts;
            if (in_array("$res.$act", $codes, true)) return true;
            if (in_array("$res.*", $codes, true)) return true;
            if (in_array("*.$act", $codes, true)) return true;
        } elseif (count($parts) === 2) {
            [$res,$act] = $parts;
            if (in_array("$res.*", $codes, true)) return true;
            if (in_array("*.$act", $codes, true)) return true;
        }
        return false;
    }

    public function canAction(string $resource, string $action, ?string $scope = null): bool
    {
        $code = $scope ? "$resource.$action.$scope" : "$resource.$action";
        return $this->hasPermissionCode($code);
    }
}
