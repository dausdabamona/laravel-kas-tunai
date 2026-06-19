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
        Schema::create('master_penyedia', function (Blueprint $table) {
            $table->id();

            $table->string('nama')->unique()->comment('Nama penyedia; pencocokan case-insensitive di service');
            $table->string('nama_normal')->storedAs('lower(nama)')->unique()
                ->comment('Kolom turunan lower(nama): jaminan dedup case-insensitive di level DB');
            $table->string('npwp')->nullable();
            $table->text('alamat')->nullable();

            $table->timestamp('terakhir_digunakan')->nullable()
                ->comment('Kapan terakhir dipakai di transaksi/nota');
            $table->unsignedInteger('frekuensi')->default(0)
                ->comment('Berapa kali dipakai; mengurutkan autocomplete');

            $table->timestamps();
            // Tanpa softDeletes: ini master reference, bukan record transaksional.

            $table->index('frekuensi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_penyedia');
    }
};
