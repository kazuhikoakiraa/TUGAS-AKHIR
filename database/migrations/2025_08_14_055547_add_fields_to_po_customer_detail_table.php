<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('po_customer_detail', function (Blueprint $table) {
            // Tambah field yang mungkin belum ada
            if (!Schema::hasColumn('po_customer_detail', 'nama_produk')) {
                $table->string('nama_produk')->nullable()->after('id_po_customer');
            }

            if (!Schema::hasColumn('po_customer_detail', 'satuan')) {
                $table->string('satuan', 50)->default('pcs')->after('jumlah');
            }

            if (!Schema::hasColumn('po_customer_detail', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('total');
            }
        });
    }

    public function down()
    {
        Schema::table('po_customer_detail', function (Blueprint $table) {
            $table->dropColumn(['nama_produk', 'satuan', 'keterangan']);
        });
    }
};
