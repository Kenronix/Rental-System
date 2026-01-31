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
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
                $table->string('unit_number');
                $table->string('unit_type');
                $table->integer('bedrooms');
                $table->decimal('bathrooms', 2, 1);
                $table->integer('square_footage');
                $table->decimal('monthly_rent', 10, 2);
                $table->decimal('security_deposit', 10, 2);
                $table->decimal('advance_deposit', 10, 2);
                $table->text('description')->nullable();
                $table->json('photos')->nullable();
                $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
                $table->boolean('is_occupied')->default(false);
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
                $table->date('lease_start')->nullable();
                $table->date('lease_end')->nullable();
                $table->integer('lease_duration')->nullable();
                $table->decimal('lease_amount', 10, 2)->nullable();
                $table->decimal('lease_deposit', 10, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
