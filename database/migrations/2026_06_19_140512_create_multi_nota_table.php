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
        Schema::create('multi_nota', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaksi_id')
                ->constrained('transaksi_kas')
                ->comment('Induk transaksi. Cascade soft-delete dikelola model event TransaksiKas');

            $table->unsignedInteger('urutan')->default(1);
            $table->string('nama_penyedia');
            $table->bigInteger('nominal')->default(0)->comment('Rp integer');
            $table->string('npwp_penyedia')->nullable();
            $table->string('alamat_penyedia')->nullable();
            $table->date('tgl_nota')->nullable();

            $table->foreignId('penyedia_id')
                ->nullable()
                ->constrained('master_penyedia')
                ->nullOnDelete()
                ->comment('Tautan opsional ke master_penyedia');

            $table->timestamps();
            $table->softDeletes();

            $table->index('transaksi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_nota');
    }
};
