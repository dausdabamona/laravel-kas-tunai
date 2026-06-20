<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan kekurangan: bendahara menambah uang ketika belanja (nota) MELEBIHI
 * uang muka yang diserahkan. Cermin dari tabel `pengembalian` (arah berlawanan):
 * tiap record 1:1 ke baris KELUAR TransaksiKas (ref_keluar_id) yang dibuat
 * otomatis oleh TambahanService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tambahan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaksi_id')
                ->constrained('transaksi_kas')
                ->comment('Induk transaksi belanja yang kekurangannya ditambah');

            $table->unsignedInteger('urutan')->default(1);
            $table->date('tanggal');
            $table->bigInteger('jumlah')->comment('Rp integer yang ditambahkan bendahara');
            $table->string('keterangan')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('ref_keluar_id')
                ->nullable()
                ->constrained('transaksi_kas')
                ->nullOnDelete()
                ->comment('Baris keluar TransaksiKas yang dibuat otomatis (1:1)');

            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 6);

            $table->index('transaksi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tambahan');
    }
};
