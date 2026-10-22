<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 32)->nullable();
            $table->string('business_name', 160)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('designation', 120)->nullable();
            $table->text('business_address')->nullable();
            $table->text('bio')->nullable();
            $table->string('website', 255)->nullable();
            $table->string('directory_email', 255)->nullable();
            $table->string('directory_phone', 32)->nullable();
            $table->boolean('show_in_directory')->default(false);
            $table->timestamps();
            $table->index(['show_in_directory', 'industry']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
