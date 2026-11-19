<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug');
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->text('cover')->nullable();
            $table->string('category')->nullable()->default('General');
            $table->string('author_name')->nullable()->default('RishtaHub Team');
            $table->text('author_avatar')->nullable();
            $table->string('read_time')->nullable()->default('5 min read');
            $table->boolean('published')->nullable()->default(false);
            $table->integer('views')->nullable()->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->timestamp('published_at')->nullable()->useCurrent();
            $table->uuid('author_id');
            $table->foreign('author_id')->references('id')->on('blog_authors');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
