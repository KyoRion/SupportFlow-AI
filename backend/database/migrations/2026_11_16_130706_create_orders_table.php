<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('order_number', 30)->unique();

            $table->decimal('total_amount', 14, 2);
            $table->char('currency', 3)->default('VND');

            $table->string('status', 24)->default('pending');

            $table->timestampTz('placed_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'placed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
