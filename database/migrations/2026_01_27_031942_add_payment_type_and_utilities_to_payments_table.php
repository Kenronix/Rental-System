<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add payment_type column
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['rent', 'utility'])->default('rent')->after('unit_id');
            }
            
            // Add utility columns only if they don't exist
            if (!Schema::hasColumn('payments', 'water')) {
                $table->decimal('water', 10, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'electricity')) {
                $table->decimal('electricity', 10, 2)->nullable()->after('water');
            }
            if (!Schema::hasColumn('payments', 'internet')) {
                $table->decimal('internet', 10, 2)->nullable()->after('electricity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
            // Note: Not dropping utility columns in down() to preserve data
        });
    }
};
