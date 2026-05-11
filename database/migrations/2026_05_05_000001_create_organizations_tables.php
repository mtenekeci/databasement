<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create organizations table
        Schema::create('organizations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name')->unique();
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });

        // 2. Create organization_user pivot table
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->char('organization_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('role');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['organization_id', 'user_id']);
        });

        // 3. Add super_admin to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('super_admin')->default(false)->after('role');
        });

        // 4. Add nullable organization_id to scoped models
        Schema::table('database_servers', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable()->after('id');
        });

        Schema::table('volumes', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable()->after('id');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable()->after('id');
        });

        Schema::table('database_server_ssh_configs', function (Blueprint $table) {
            $table->char('organization_id', 26)->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('database_server_ssh_configs', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });

        Schema::table('volumes', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });

        Schema::table('database_servers', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('super_admin');
        });

        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
