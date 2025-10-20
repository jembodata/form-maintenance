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
        Schema::create('spareparts', function (Blueprint $table) {
            $table->id();
            $table->date('Tanggal');
            $table->string('Tipe_Maintenance',20);
            $table->string('Kelompok', 10);
            $table->string('Item',30);
            $table->string('Deskripsi',100);
            $table->unsignedInteger('Masuk')->nullable();
            $table->unsignedInteger('Keluar')->nullable();
            $table->unsignedInteger('Stok');
            $table->string('Nama_Plant',6);
            $table->string('Nama_Mesin',20);
            $table->string('Nama_Bagian',20);
            $table->string('Nama_Operator',20)->nullable();
            $table->unsignedInteger('Cycle_Time')->nullable();
            $table->date('Tanggal_Kembali')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spareparts');
    }
};
