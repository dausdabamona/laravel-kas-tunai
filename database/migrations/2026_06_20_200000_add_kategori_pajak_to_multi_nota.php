<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Override kategori pajak per nota. NULL = klasifikasi otomatis dari uraian
     * kegiatan transaksi (perilaku lama). Terisi (label kategori) = paksa manual,
     * agar satu transaksi bisa memuat nota dgn perlakuan pajak berbeda.
     */
    public function up(): void
    {
        Schema::table('multi_nota', function (Blueprint $table) {
            $table->string('kategori_pajak')->nullable()->after('alamat_penyedia')
                ->comment('Label kategori config/pajak; NULL = otomatis dari kegiatan');
        });
    }

    public function down(): void
    {
        Schema::table('multi_nota', function (Blueprint $table) {
            $table->dropColumn('kategori_pajak');
        });
    }
};
