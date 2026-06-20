<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian biaya perjalanan dinas PER PEGAWAI.
 *
 * Tiap baris = satu komponen biaya (uang harian, fullboard, transport darat/udara,
 * penginapan, lainnya) untuk satu pegawai pada satu surat tugas. Komponen bisa
 * ditambah/edit/hapus. pembayar (bendahara|pelaksana) + metode (tunai|bank)
 * menentukan pengelompokan kuitansi rampung & dampak ke kas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rincian_pd', function (Blueprint $table) {
            $table->id();

            $table->foreignId('surat_tugas_id')
                ->constrained('surat_tugas')
                ->comment('Surat tugas induk');

            $table->unsignedInteger('pegawai_index')->default(0)->comment('Indeks pegawai pada JSON surat_tugas.pegawai');
            $table->string('pegawai_nama')->comment('Denormalisasi nama pegawai untuk cetak stabil');

            $table->string('komponen')->comment('uang_harian|fullboard|transport_darat|transport_udara|penginapan|lainnya');
            $table->string('uraian')->nullable()->comment('mis. "Ambon - Sorong", "4 malam"');
            $table->unsignedInteger('qty')->default(1);
            $table->bigInteger('harga_satuan')->default(0);
            $table->bigInteger('nominal')->comment('qty × harga_satuan (Rp integer)');

            $table->string('pembayar')->comment('bendahara|pelaksana');
            $table->string('metode')->comment('tunai|bank');
            $table->string('keterangan')->nullable();

            $table->unsignedInteger('urutan')->default(1);

            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 6);

            $table->index('surat_tugas_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rincian_pd');
    }
};
