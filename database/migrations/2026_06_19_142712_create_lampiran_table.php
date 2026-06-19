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
        Schema::create('lampiran', function (Blueprint $table) {
            $table->id();

            // attachable_type menyimpan ALIAS morph map ('transaksi'/'nota'), bukan FQCN.
            $table->morphs('attachable');

            $table->string('kategori')->comment('Cast ke App\Enums\KategoriLampiran');
            $table->unsignedInteger('urutan')->default(1);

            $table->string('disk')->default('privat');
            $table->string('path')->comment('Path di disk privat; diisi sungguhan di slice 2.4');
            $table->string('nama_file');
            $table->string('mime');

            // TIDAK ada kolom `url`: URL signed & kedaluwarsa, dibangkitkan saat baca (2.4).
            $table->json('meta')->nullable()->comment('lat,lng,maps_url,jenis_dok,keterangan,waktu');

            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lampiran');
    }
};
