<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title', 180);
            $table->longText('content');

            $table->string('status', 20)
                ->default('pending');

            $table->string('embedding_model', 100)
                ->default('text-embedding-3-small');

            $table->unsignedInteger('chunk_count')
                ->default(0);

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('knowledge_document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('chunk_index');
            $table->text('content');

            $table->vector('embedding', dimensions: 1536)
                ->index();

            $table->timestamps();

            $table->unique([
                'knowledge_document_id',
                'chunk_index',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
    }
};
