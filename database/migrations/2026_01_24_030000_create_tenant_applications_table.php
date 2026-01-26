<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->string('id_picture')->nullable();
            $table->string('profile_picture')->nullable();
            
            // Personal Information
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('whatsapp');
            $table->string('occupation');
            $table->integer('monthly_income');
            $table->text('address');
            $table->integer('number_of_people')->default(1);
            
            // Reference 1 Information
            $table->string('reference1_name');
            $table->text('reference1_address');
            $table->string('reference1_phone');
            $table->string('reference1_email');
            $table->string('reference1_relationship')->nullable();
            
            // Reference 2 Information
            $table->string('reference2_name');
            $table->text('reference2_address');
            $table->string('reference2_phone');
            $table->string('reference2_email');
            $table->string('reference2_relationship')->nullable();
            
            // Application Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_applications');
    }
};
