<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('blocker_id');
            $table->uuid('blocked_id');
            $table->uuid('conversation_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->foreign('blocker_id')->references('id')->on('profiles');
            $table->foreign('blocked_id')->references('id')->on('profiles');
            $table->foreign('conversation_id')->references('id')->on('conversations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
    }
};
