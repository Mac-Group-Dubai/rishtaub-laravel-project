<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->text('url');
            $table->text('path');
            $table->boolean('is_active')->nullable()->default(false);
            $table->integer('size_bytes')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->foreign('user_id')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_images');
    }
};
