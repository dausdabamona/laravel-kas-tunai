<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal penerbitan surat tugas (berbeda dari tgl_berangkat). Dipakai pada
 * subjudul dokumen ("SPD Nomor ... Tanggal ...").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_tugas', function (Blueprint $table) {
            $table->date('tanggal_surat')->nullable()->after('nomor_surat');
        });
    }

    public function down(): void
    {
        Schema::table('surat_tugas', function (Blueprint $table) {
            $table->dropColumn('tanggal_surat');
        });
    }
};
