<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 180);
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'slug']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->text('value');
            $table->boolean('is_public')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('content_pages');
    }
};
