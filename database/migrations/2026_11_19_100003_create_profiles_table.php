<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('onbehalf')->nullable()->default('self');
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('education')->nullable();
            $table->text('profession')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('about')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('height')->nullable();
            $table->string('children')->nullable();
            $table->string('salary')->nullable();
            $table->text('photo')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->string('plan')->nullable()->default('free');
            $table->string('email')->nullable();
            $table->timestamp('plan_activated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
