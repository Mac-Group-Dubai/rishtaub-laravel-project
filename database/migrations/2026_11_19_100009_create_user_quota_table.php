<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_quota', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->integer('interests_remaining')->default(100);
            $table->integer('contact_views_remaining')->default(10);
            $table->integer('gallery_uploads_remaining')->default(10);
            $table->integer('profile_views_this_week')->default(0);
            $table->timestamp('quota_reset_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->foreign('user_id')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quota');
    }
};
