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
        Schema::create('surat_tugas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaksi_id')
                ->constrained('transaksi_kas')
                ->comment('Baris porsi pelaksana (PD_POKOK)');

            $table->string('nomor_surat');
            $table->string('dasar')->nullable();
            $table->string('maksud');
            $table->string('angkutan')->nullable();
            $table->string('tempat_berangkat');
            $table->string('tempat_tujuan');
            $table->date('tgl_berangkat');
            $table->date('tgl_kembali');
            $table->unsignedInteger('lama_hari');
            $table->string('akun')->nullable();
            $table->string('jenis')->comment('dalam_kota | luar_kota');

            $table->json('pegawai')->comment('array {nama,nip,pangkat,jabatan,golongan,uang_harian,transport,penginapan,biaya}');

            $table->bigInteger('biaya_total');
            $table->string('sumber_pelaksana');
            $table->string('sumber_bendahara')->nullable();

            $table->string('ttd_ppk')->nullable();
            $table->string('nip_ppk')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

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
        Schema::dropIfExists('surat_tugas');
    }
};
