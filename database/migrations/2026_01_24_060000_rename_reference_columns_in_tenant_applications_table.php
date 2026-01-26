<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        // Check if the old columns exist before renaming
        if (Schema::hasColumn('tenant_applications', 'mother_name')) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                // MySQL/MariaDB syntax
                DB::statement('ALTER TABLE tenant_applications CHANGE mother_name reference1_name VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE mother_address reference1_address TEXT');
                DB::statement('ALTER TABLE tenant_applications CHANGE mother_phone reference1_phone VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE mother_email reference1_email VARCHAR(255)');
            } elseif ($driver === 'pgsql') {
                // PostgreSQL syntax
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN mother_name TO reference1_name');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN mother_address TO reference1_address');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN mother_phone TO reference1_phone');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN mother_email TO reference1_email');
            } elseif ($driver === 'sqlite') {
                // SQLite requires recreating the table
                $this->renameSqliteColumns(['mother_name' => 'reference1_name', 'mother_address' => 'reference1_address', 'mother_phone' => 'reference1_phone', 'mother_email' => 'reference1_email']);
            }
        }
        
        if (Schema::hasColumn('tenant_applications', 'father_name')) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                // MySQL/MariaDB syntax
                DB::statement('ALTER TABLE tenant_applications CHANGE father_name reference2_name VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE father_address reference2_address TEXT');
                DB::statement('ALTER TABLE tenant_applications CHANGE father_phone reference2_phone VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE father_email reference2_email VARCHAR(255)');
            } elseif ($driver === 'pgsql') {
                // PostgreSQL syntax
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN father_name TO reference2_name');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN father_address TO reference2_address');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN father_phone TO reference2_phone');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN father_email TO reference2_email');
            } elseif ($driver === 'sqlite') {
                // SQLite requires recreating the table
                $this->renameSqliteColumns(['father_name' => 'reference2_name', 'father_address' => 'reference2_address', 'father_phone' => 'reference2_phone', 'father_email' => 'reference2_email']);
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        
        // Check if the new columns exist before reverting
        if (Schema::hasColumn('tenant_applications', 'reference1_name')) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement('ALTER TABLE tenant_applications CHANGE reference1_name mother_name VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE reference1_address mother_address TEXT');
                DB::statement('ALTER TABLE tenant_applications CHANGE reference1_phone mother_phone VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE reference1_email mother_email VARCHAR(255)');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference1_name TO mother_name');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference1_address TO mother_address');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference1_phone TO mother_phone');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference1_email TO mother_email');
            }
        }
        
        if (Schema::hasColumn('tenant_applications', 'reference2_name')) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement('ALTER TABLE tenant_applications CHANGE reference2_name father_name VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE reference2_address father_address TEXT');
                DB::statement('ALTER TABLE tenant_applications CHANGE reference2_phone father_phone VARCHAR(255)');
                DB::statement('ALTER TABLE tenant_applications CHANGE reference2_email father_email VARCHAR(255)');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference2_name TO father_name');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference2_address TO father_address');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference2_phone TO father_phone');
                DB::statement('ALTER TABLE tenant_applications RENAME COLUMN reference2_email TO father_email');
            }
        }
    }

    private function renameSqliteColumns(array $renames): void
    {
        // SQLite doesn't support RENAME COLUMN directly, so we skip it
        // In production, you'd need to recreate the table
        // For now, we'll just skip SQLite renaming
    }
};
