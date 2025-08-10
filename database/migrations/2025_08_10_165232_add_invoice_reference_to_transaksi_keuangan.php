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
        Schema::table('transaksi_keuangan', function (Blueprint $table) {
            // Tambah kolom untuk referensi invoice (opsional)
            $table->string('referensi_type')->nullable()->after('keterangan')
                ->comment('Tipe referensi: po_supplier, invoice, manual');

            $table->string('referensi_id')->nullable()->after('referensi_type')
                ->comment('ID referensi sesuai dengan tipe');

            // Index untuk performance
            $table->index(['referensi_type', 'referensi_id'], 'idx_transaksi_referensi');
            $table->index(['jenis', 'tanggal'], 'idx_transaksi_jenis_tanggal');
            $table->index(['tanggal'], 'idx_transaksi_tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_keuangan', function (Blueprint $table) {
            $table->dropIndex('idx_transaksi_referensi');
            $table->dropIndex('idx_transaksi_jenis_tanggal');
            $table->dropIndex('idx_transaksi_tanggal');
            $table->dropColumn(['referensi_type', 'referensi_id']);
        });
    }
};
