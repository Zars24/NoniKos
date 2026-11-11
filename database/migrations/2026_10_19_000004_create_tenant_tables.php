<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 24)->nullable();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('identity_number')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_name')->nullable();
            $table->string('emergency_phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'full_name']);
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('tenant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 24)->unique();
            $table->date('start_date');
            $table->unsignedSmallInteger('duration_months')->default(1);
            $table->unsignedBigInteger('monthly_price');
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'status']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('code', 24)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('monthly_price');
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->unsignedSmallInteger('billing_cycle_months')->default(1);
            $table->unsignedSmallInteger('due_days')->default(7);
            $table->string('status', 20)->default('active');
            $table->date('checked_in_at')->nullable();
            $table->date('checked_out_at')->nullable();
            $table->string('termination_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'status', 'start_date', 'end_date']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('tenant_documents');
        Schema::dropIfExists('tenants');
    }
};
