<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->string('pincode', 20)->unique();
            $table->string('name');
            $table->string('state')->nullable();
            $table->string('chapter_head_name')->nullable();
            $table->string('chapter_head_phone', 32)->nullable();
            $table->string('chapter_head_email')->nullable();
            $table->text('postal_address')->nullable();
            $table->text('about')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('guest')->index();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('password_set_at')->nullable();
        });

        Schema::create('user_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('normalized_value', 320);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['type', 'normalized_value']);
            $table->index(['user_id', 'type']);
        });

        Schema::create('chapter_admin_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'chapter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_admin_assignments');
        Schema::dropIfExists('user_identities');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chapter_id');
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'password_set_at']);
        });

        Schema::dropIfExists('chapters');
    }
};
