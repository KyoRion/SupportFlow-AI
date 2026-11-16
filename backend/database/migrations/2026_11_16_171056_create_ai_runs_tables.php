<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->uuid('conversation_id')->nullable()->index();

            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();

            $table->string('status', 20)->default('running');

            $table->unsignedInteger('ttft_ms')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();

            $table->unsignedInteger('tool_calls')->default(0);

            $table->string('error_code', 60)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ai_tool_runs', function (Blueprint $table) {
            $table->id();

            $table->uuid('ai_run_id');

            $table->foreign('ai_run_id')
                ->references('id')
                ->on('ai_runs')
                ->cascadeOnDelete();

            $table->string('tool_call_id', 191);
            $table->string('tool_name', 100);

            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('successful')->nullable();

            $table->timestamps();

            $table->unique(['ai_run_id', 'tool_call_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_runs');
        Schema::dropIfExists('ai_runs');
    }
};
