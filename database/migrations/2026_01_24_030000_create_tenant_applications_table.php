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
            
            // Personal Information
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('whatsapp');
            $table->string('occupation');
            $table->integer('monthly_income');
            $table->text('address');
            $table->integer('number_of_people')->default(1);
            
            // Mother's Information
            $table->string('mother_name');
            $table->text('mother_address');
            $table->string('mother_phone');
            $table->string('mother_email');
            
            // Father's Information
            $table->string('father_name');
            $table->text('father_address');
            $table->string('father_phone');
            $table->string('father_email');
            
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
