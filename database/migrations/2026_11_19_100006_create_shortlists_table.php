<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id');
            $table->uuid('target_id');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->foreign('owner_id')->references('id')->on('profiles');
            $table->foreign('target_id')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlists');
    }
};
