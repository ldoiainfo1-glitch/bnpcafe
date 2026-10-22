<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('venue', 255);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('registration_closes_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
            $table->index(['status', 'starts_at']);
            $table->index(['chapter_id', 'status']);
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('email', 255);
            $table->string('phone', 32)->nullable();
            $table->string('business_name', 160)->nullable();
            $table->string('status', 20)->default('registered')->index();
            $table->timestamps();
            $table->unique(['event_id', 'email']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('contact_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('email', 255);
            $table->string('phone', 32)->nullable();
            $table->string('subject', 180);
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_inquiries');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
    }
};
