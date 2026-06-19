<?php

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
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
        Schema::create('transaksi_kas', function (Blueprint $table) {
            $table->id();

            $table->string('no', 30)->unique()->comment('Nomor urut otomatis, mis. KAS-2026-0001');
            $table->date('tanggal');
            $table->string('kegiatan');
            $table->string('penjab')->nullable()->comment('Penanggung jawab');

            $table->bigInteger('debet')->default(0)->unsigned()->comment('Penerimaan (Rp integer)');
            $table->bigInteger('kredit')->default(0)->unsigned()->comment('Pengeluaran (Rp integer)');

            $table->enum('sumber', array_column(Sumber::cases(), 'value'))->comment('Sumber dana: tunai | bank');
            $table->enum('jenis', array_column(JenisTransaksi::cases(), 'value'))->comment('Jenis transaksi');

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('transaksi_kas')
                ->nullOnDelete()
                ->comment('Transaksi induk (untuk pengembalian / anak pindah_dana)');

            $table->string('ref_group')->nullable()->comment('Pengelompokan transaksi majemuk (UUID/kode)');

            $table->enum('status_spj', array_column(StatusSpj::cases(), 'value'))
                ->default(StatusSpj::Belum->value)
                ->comment('Status pertanggungjawaban');

            // Data SPBY (Surat Permintaan Bayar / Uang)
            $table->string('no_spby')->nullable();
            $table->date('tgl_spby')->nullable();
            $table->bigInteger('nilai_spby')->nullable()->unsigned();
            $table->bigInteger('uang_diserahkan')->nullable()->unsigned();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tanggal');
            $table->index(['sumber', 'tanggal']);
            $table->index('status_spj');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_kas');
    }
};
