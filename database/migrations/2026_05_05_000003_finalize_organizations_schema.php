<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Make organization_id NOT NULL and add foreign keys
        Schema::table('database_servers', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable(false)->change();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        Schema::table('volumes', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable(false)->change();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable(false)->change();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        Schema::table('database_server_ssh_configs', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable(false)->change();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        // 2. Drop role column from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        // Re-add role column
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('super_admin');
        });

        // Drop foreign keys and make nullable again
        Schema::table('database_server_ssh_configs', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->char('organization_id', 26)->nullable()->change();
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->char('organization_id', 26)->nullable()->change();
        });

        Schema::table('volumes', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->char('organization_id', 26)->nullable()->change();
        });

        Schema::table('database_servers', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->char('organization_id', 26)->nullable()->change();
        });
    }
};
