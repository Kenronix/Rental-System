<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_applications', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('id_picture');
            }
            
            // Check which column names exist (old or new)
            $hasOldColumns = Schema::hasColumn('tenant_applications', 'mother_email');
            $hasNewColumns = Schema::hasColumn('tenant_applications', 'reference1_email');
            
            if (!Schema::hasColumn('tenant_applications', 'reference1_relationship')) {
                if ($hasNewColumns) {
                    $table->string('reference1_relationship')->nullable()->after('reference1_email');
                } elseif ($hasOldColumns) {
                    $table->string('reference1_relationship')->nullable()->after('mother_email');
                }
            }
            
            if (!Schema::hasColumn('tenant_applications', 'reference2_relationship')) {
                if ($hasNewColumns) {
                    $table->string('reference2_relationship')->nullable()->after('reference2_email');
                } elseif ($hasOldColumns) {
                    $table->string('reference2_relationship')->nullable()->after('father_email');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table) {
            $table->dropColumn(['profile_picture', 'reference1_relationship', 'reference2_relationship']);
        });
    }
};
