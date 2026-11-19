<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('created_by');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->timestamp('last_message_at')->nullable()->useCurrent();
            $table->foreign('created_by')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
