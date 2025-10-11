<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $base = [
            ['resource'=>'data','action'=>'manage'],
            ['resource'=>'data','action'=>'create'],
            ['resource'=>'data','action'=>'view'],
            ['resource'=>'reports','action'=>'view'],
            ['resource'=>'users','action'=>'manage'],
            ['resource'=>'users','action'=>'promote'],
            ['resource'=>'metrics','action'=>'import'],
            ['resource'=>'metrics','action'=>'delete'],
            ['resource'=>'metrics','action'=>'view'],
            ['resource'=>'summary','action'=>'view'],
            ['resource'=>'stats','action'=>'view'],
            ['resource'=>'feeds','action'=>'view'],
            ['resource'=>'profile','action'=>'view'],
        ];

        $permissionIds = [];
        foreach ($base as $row) {
            $code = Permission::buildCode($row['resource'],$row['action']);
            $perm = Permission::updateOrCreate(
                ['code'=>$code],
                [
                    'resource'=>$row['resource'],
                    'action'=>$row['action'],
                    'scope'=>null,
                    'description'=> ucfirst($row['action']).' '.$row['resource']
                ]
            );
            $permissionIds[$code] = $perm->id;
        }

        // Attach to roles if roles exist
        $roles = Role::all();
        foreach ($roles as $role) {
            if (in_array('all', $role->permissions ?? [])) {
                // owner: attach all
                $role->permissionItems()->sync(array_values($permissionIds));
                // also add wildcard special row if desired (not stored now)
                continue;
            }
            // Map legacy JSON to new codes
            $legacy = $role->permissions ?? [];
            $attach = [];
            foreach ($legacy as $legacyCode) {
                if ($legacyCode === 'all') continue;
                // legacy like manage_data becomes data.manage
                if (str_contains($legacyCode, '_')) {
                    [$act,$res] = explode('_',$legacyCode,2); // manage_data
                    $mapped = $res.'.'.$act; // data.manage
                } else {
                    $mapped = $legacyCode;
                }
                if (isset($permissionIds[$mapped])) {
                    $attach[] = $permissionIds[$mapped];
                }
            }
            if ($attach) {
                $role->permissionItems()->syncWithoutDetaching($attach);
            }
        }
    }
}
