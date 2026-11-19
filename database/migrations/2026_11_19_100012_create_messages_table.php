<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->uuid('sender_id');
            $table->text('body');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('deleted_at')->nullable();
            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('sender_id')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
