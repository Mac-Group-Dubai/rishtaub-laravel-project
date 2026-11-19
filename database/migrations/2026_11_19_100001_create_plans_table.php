<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('duration');
            $table->text('tagline');
            $table->boolean('highlight')->nullable()->default(false);
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('active')->nullable()->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
