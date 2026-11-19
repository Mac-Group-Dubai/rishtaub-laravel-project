<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reporter_id');
            $table->uuid('reported_user_id');
            $table->uuid('conversation_id');
            $table->uuid('message_id')->nullable();
            $table->text('reason');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->foreign('reporter_id')->references('id')->on('profiles');
            $table->foreign('reported_user_id')->references('id')->on('profiles');
            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('message_id')->references('id')->on('messages');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_reports');
    }
};
