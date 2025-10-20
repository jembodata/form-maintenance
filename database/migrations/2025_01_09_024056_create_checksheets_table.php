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
        Schema::create('checksheets', function (Blueprint $table) {
            $table->id();
            $table->string('plant_area', 20);
            $table->string('nama_operator', 30);
            $table->string('posisi_operator', 10);
            $table->string('tipe_proses', 10);
            $table->string('nama_mesin', 20);
            $table->string('hours_meter');
            $table->date('date');
            $table->time('time_start');
            $table->time('time_end');
            $table->string('image_before')->nullable();
            $table->string('image_after')->nullable();
            $table->text('signature')->nullable();

            $table->string('elektrik_carbon_brush', 10)->nullable();
            $table->string('elektrik_suara', 10)->nullable();
            $table->string('elektrik_getaran', 10)->nullable();
            $table->string('elektrik_suhu', 10)->nullable();
            $table->string('elektrik_tahanan_isolasi', 10)->nullable();
            $table->string('elektrik_ampere_motor', 10)->nullable();
            $table->string('remarks_elektrik_motor_penggerak')->nullable();

            $table->string('elektrik_suhu_heater', 10)->nullable();
            $table->string('elektrik_fungsi_pemanas', 10)->nullable();
            $table->string('remarks_elektrik_heater')->nullable();

            $table->string('elektrik_tombol', 10)->nullable();
            $table->string('elektrik_layar', 10)->nullable();
            $table->string('elektrik_PLC', 10)->nullable();
            $table->string('elektrik_kontaktor', 10)->nullable();
            $table->string('elektrik_drive-inverter', 10)->nullable();
            $table->string('remarks_elektrik_sistem_control')->nullable();

            $table->string('elektrik_kondisi_kabel', 10)->nullable();
            $table->string('elektrik_socket_kabel', 10)->nullable();
            $table->string('remarks_elektrik_kabel_dan_konektor')->nullable();

            $table->string('elektrik_kebocoran', 10)->nullable();
            $table->string('elektrik_tekanan', 10)->nullable();
            $table->string('remarks_elektrik_sistem_hidrolik')->nullable();

            $table->string('elektrik_sispemanas_suhu', 10)->nullable();
            $table->string('elektrik_kestabilan_pemanas', 10)->nullable();
            $table->string('remarks_elektrik_sistem_pemanas')->nullable();

            $table->string('elektrik_kebersihan_kipas', 10)->nullable();
            $table->string('elektrik_fungsi_kipas', 10)->nullable();
            $table->string('remarks_elektrik_sistem_pendingin_tinning')->nullable();

            $table->string('elektrik_kebersihan', 10)->nullable();
            $table->string('elektrik_aliran_cairan', 10)->nullable();
            $table->string('elektrik_tekanan_coloring', 10)->nullable();
            $table->string('remarks_elektrik_sistem_pewarna')->nullable();

            $table->string('elektrik_filter', 10)->nullable();
            $table->string('elektrik_blower', 10)->nullable();
            $table->string('elektrik_sirkulasi', 10)->nullable();
            $table->string('remarks_elektrik_sistem_pendingin_motor')->nullable();

            $table->string('elektrik_kalibrasi_suhu', 10)->nullable();
            $table->string('remarks_elektrik_thermocouple')->nullable();

            $table->string('mekanik_gearbox_pelumasan', 10)->nullable();
            $table->string('mekanik_gearbox_kebersihan', 10)->nullable();
            $table->string('mekanik_gearbox_suara', 10)->nullable();
            $table->string('remarks_mekanik_gearbox')->nullable();

            $table->string('mekanik_sispendingin_aliran_pendingin', 10)->nullable();
            $table->string('mekanik_sispendingin_kebersihan_pipa', 10)->nullable();
            $table->string('mekanik_sispendingin_sirkulasi_pipa', 10)->nullable();
            $table->string('remarks_mekanik_sispendingin')->nullable();

            $table->string('mekanik_shaft_keausan', 10)->nullable();
            $table->string('mekanik_shaft_kerusakan', 10)->nullable();
            $table->string('remarks_mekanik_shaft')->nullable();

            $table->string('mekanik_anealing_anealing', 10)->nullable();
            $table->string('mekanik_anealing_carbon_brush', 10)->nullable();
            $table->string('remarks_mekanik_anealing')->nullable();

            $table->string('mekanik_rollcap_keausan', 10)->nullable();
            $table->string('mekanik_rollcap_kerusakan', 10)->nullable();
            $table->string('remarks_mekanik_rollcap')->nullable();

            $table->string('mekanik_pulleybelt_kekencangan', 10)->nullable();
            $table->string('mekanik_pulleybelt_ketebalan', 10)->nullable();
            $table->string('remarks_mekanik_pulleybelt')->nullable();

            $table->string('mekanik_bearing_pelumasan', 10)->nullable();
            $table->string('mekanik_bearing_kondisi_fisik', 10)->nullable();
            $table->string('remarks_mekanik_bearing')->nullable();

            $table->string('mekanik_alignmesin_kesejajaran', 10)->nullable();
            $table->string('remarks_mekanik_alignmesin')->nullable();

            $table->string('mekanik_gearrantai_pelumasan', 10)->nullable();
            $table->string('mekanik_gearrantai_keausan', 10)->nullable();
            $table->string('remarks_mekanik_gearrantai')->nullable();

            $table->string('mekanik_screewbarel_kondisi', 10)->nullable();
            $table->string('mekanik_screewbarel_kerusakan', 10)->nullable();
            $table->string('remarks_mekanik_screewbarel')->nullable();

            $table->string('mekanik_mesintinning_kebersihan', 10)->nullable();
            $table->string('mekanik_mesintinning_roller', 10)->nullable();
            $table->string('remarks_mekanik_mesintinning')->nullable();

            $table->string('mekanik_sispencoloring_aliran', 10)->nullable();
            $table->string('mekanik_sispencoloring_kebersihan_pipa', 10)->nullable();
            $table->string('mekanik_sispencoloring_flowmeter_n2', 10)->nullable();
            $table->string('remarks_mekanik_sispencoloring')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checksheets');
    }
};
