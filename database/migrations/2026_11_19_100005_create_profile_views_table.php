<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('viewer_id');
            $table->uuid('viewed_id');
            $table->timestamp('viewed_at')->nullable()->useCurrent();
            $table->foreign('viewer_id')->references('id')->on('profiles');
            $table->foreign('viewed_id')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};
