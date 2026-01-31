<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenant_applications', 'number_of_people')) {
            Schema::table('tenant_applications', function (Blueprint $table) {
                $table->integer('number_of_people')->default(1)->after('address');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenant_applications', 'number_of_people')) {
            Schema::table('tenant_applications', function (Blueprint $table) {
                $table->dropColumn('number_of_people');
            });
        }
    }
};
