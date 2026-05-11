<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $mainOrgId = Str::ulid()->toBase32();

        DB::transaction(function () use ($mainOrgId) {
            // 1. Create "Main" organization
            DB::table('organizations')->insert([
                'id' => $mainOrgId,
                'name' => 'Main',
                'is_main' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Backfill organization_id on scoped models
            DB::table('database_servers')->whereNull('organization_id')->update(['organization_id' => $mainOrgId]);
            DB::table('volumes')->whereNull('organization_id')->update(['organization_id' => $mainOrgId]);
            DB::table('agents')->whereNull('organization_id')->update(['organization_id' => $mainOrgId]);
            DB::table('database_server_ssh_configs')->whereNull('organization_id')->update(['organization_id' => $mainOrgId]);

            // 3. Map existing users to organization with their current roles
            $users = DB::table('users')->get(['id', 'role']);

            foreach ($users as $user) {
                // admin users become super_admin
                if ($user->role === 'admin') {
                    DB::table('users')->where('id', $user->id)->update(['super_admin' => true]);
                }

                // All users get attached to main org with their current role
                DB::table('organization_user')->insert([
                    'organization_id' => $mainOrgId,
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Restore role column from pivot data (best effort)
        $pivotEntries = DB::table('organization_user')
            ->join('organizations', 'organizations.id', '=', 'organization_user.organization_id')
            ->where('organizations.is_main', true)
            ->get(['organization_user.user_id', 'organization_user.role']);

        foreach ($pivotEntries as $entry) {
            DB::table('users')->where('id', $entry->user_id)->update(['role' => $entry->role]);
        }

        // Reset super_admin
        DB::table('users')->update(['super_admin' => false]);

        // Clear pivot table
        DB::table('organization_user')->delete();

        // Clear organization_id
        DB::table('database_servers')->update(['organization_id' => null]);
        DB::table('volumes')->update(['organization_id' => null]);
        DB::table('agents')->update(['organization_id' => null]);
        DB::table('database_server_ssh_configs')->update(['organization_id' => null]);

        // Remove main org
        DB::table('organizations')->where('is_main', true)->delete();
    }
};
