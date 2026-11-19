<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_read_states', function (Blueprint $table) {
            $table->uuid('conversation_id');
            $table->uuid('user_id');
            $table->uuid('last_read_message_id')->nullable();
            $table->timestamp('last_read_at')->nullable()->useCurrent();
            $table->primary(['conversation_id', 'user_id']);
            $table->foreign('conversation_id')->references('id')->on('conversations');
            $table->foreign('user_id')->references('id')->on('profiles');
            $table->foreign('last_read_message_id')->references('id')->on('messages');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_read_states');
    }
};
