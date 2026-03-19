<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2);
            $table->decimal('grand_total', 12, 2);

            $table->timestamp('ordered_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['branch_id', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

