<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('success_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('couple');
            $table->string('location')->nullable();
            $table->string('date')->nullable();
            $table->text('quote')->nullable();
            $table->text('image')->nullable();
            $table->boolean('published')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('visible')->nullable()->default(true);
            $table->string('story_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('success_stories');
    }
};
