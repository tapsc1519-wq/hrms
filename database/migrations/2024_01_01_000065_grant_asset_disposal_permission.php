<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('organization_roles')) {
            return;
        }

        $roles = DB::table('organization_roles')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereJsonContains('permissions', 'assets.catalog')
                    ->orWhereJsonContains('permissions', 'assets.delete')
                    ->orWhereJsonContains('permissions', 'maintenance.manage');
            })
            ->get();

        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];
            if (!in_array('assets.disposal', $permissions, true)) {
                $permissions[] = 'assets.disposal';
                DB::table('organization_roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values($permissions)),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('organization_roles')) {
            return;
        }

        $roles = DB::table('organization_roles')->get();
        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];
            $permissions = array_values(array_filter($permissions, fn ($permission) => $permission !== 'assets.disposal'));
            DB::table('organization_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at' => now(),
            ]);
        }
    }
};
