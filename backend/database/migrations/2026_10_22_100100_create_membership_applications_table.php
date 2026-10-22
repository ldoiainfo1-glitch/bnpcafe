<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->string('phone', 32);
            $table->string('business_name', 160);
            $table->string('industry', 120);
            $table->string('designation', 120)->nullable();
            $table->text('business_address')->nullable();
            $table->text('applicant_note')->nullable();
            $table->string('status', 24)->default('submitted')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['chapter_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_application_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('chapter_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('pending_payment')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_applications');
    }
};
