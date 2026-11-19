<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->text('subject');
            $table->text('message');
            $table->string('status')->nullable()->default('open');
            $table->string('priority')->nullable()->default('medium');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->foreign('user_id')->references('id')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
