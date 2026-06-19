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
        Schema::create('pengembalian', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaksi_id')
                ->constrained('transaksi_kas')
                ->comment('Induk transaksi belanja yang dikembalikan sebagiannya');

            $table->unsignedInteger('urutan')->default(1);
            $table->date('tanggal');
            $table->bigInteger('jumlah')->comment('Rp integer yang dikembalikan');
            $table->string('keterangan')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('ref_masuk_id')
                ->nullable()
                ->constrained('transaksi_kas')
                ->nullOnDelete()
                ->comment('Baris masuk TransaksiKas yang dibuat otomatis (1:1)');

            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 6);

            $table->index('transaksi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengembalian');
    }
};
