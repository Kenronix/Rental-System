<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('unit_number');
            $table->string('unit_type');
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('square_footage')->nullable();
            $table->integer('monthly_rent');
            $table->integer('security_deposit');
            $table->integer('advance_deposit');
            $table->text('description');
            $table->json('photos')->nullable();
            $table->enum('status', ['active', 'inactive', 'vacant'])->default('active');
            $table->boolean('is_occupied')->default(false);
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->date('lease_start')->nullable();
            $table->date('lease_end')->nullable();
            $table->integer('lease_duration')->nullable();
            $table->integer('lease_amount')->nullable();
            $table->integer('lease_deposit')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};