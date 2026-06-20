<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uang muka perjalanan dinas PER PEGAWAI (tunai/transfer).
 *
 * Setiap record tertaut 1:1 ke baris KELUAR TransaksiKas (ref_kas_id, jenis
 * PdUangMuka) pada ref_group surat tugas — itulah yang menggerakkan saldo kas
 * tunai/bank. Σ uang muka = "telah dibayar" pada kuitansi rampung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uang_muka_pd', function (Blueprint $table) {
            $table->id();

            $table->foreignId('surat_tugas_id')
                ->constrained('surat_tugas')
                ->comment('Surat tugas induk');

            $table->unsignedInteger('pegawai_index')->default(0);
            $table->unsignedInteger('urutan')->default(1);
            $table->date('tanggal');
            $table->bigInteger('jumlah')->comment('Rp integer uang muka diberikan');
            $table->string('metode')->comment('tunai|bank (transfer)');
            $table->string('keterangan')->nullable();

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ref_kas_id')->nullable()->constrained('transaksi_kas')->nullOnDelete()
                ->comment('Baris keluar TransaksiKas yang dibuat otomatis (1:1)');

            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 6);

            $table->index('surat_tugas_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uang_muka_pd');
    }
};
