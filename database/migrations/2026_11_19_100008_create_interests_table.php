<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sender_id');
            $table->uuid('receiver_id');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->timestamp('acted_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->foreign('sender_id')->references('id')->on('profiles');
            $table->foreign('receiver_id')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
    }
};
