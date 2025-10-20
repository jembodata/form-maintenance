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
        Schema::create('sparepart_stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sparepart_id')
                  ->constrained('spareparts')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->timestamp('performed_at')->useCurrent();
            $table->enum('direction', ['IN', 'OUT', 'ADJUST']);
            $table->unsignedInteger('qty');
            $table->unsignedInteger('balance_after');
            $table->string('note', 200)->nullable();
            $table->string('actor_name', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sparepart_stock_histories');
    }
};
