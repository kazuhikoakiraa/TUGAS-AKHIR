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
        // Update po_customer table - tambah tax_rate
        if (!Schema::hasColumn('po_customer', 'tax_rate')) {
            Schema::table('po_customer', function (Blueprint $table) {
                $table->decimal('tax_rate', 5, 2)->default(11.00)->after('total_pajak'); // Tax rate dalam persen
            });
        }

        // Update po_customer_detail table
        Schema::table('po_customer_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('po_customer_detail', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('id_po_customer')->constrained('products')->nullOnDelete();
            }
            if (Schema::hasColumn('po_customer_detail', 'nama_produk')) {
                $table->string('nama_produk')->nullable()->change(); // Buat nullable karena bisa diisi dari product
            }
            if (!Schema::hasColumn('po_customer_detail', 'satuan')) {
                $table->string('satuan', 50)->nullable()->after('jumlah'); // Unit dari product atau manual
            }
            if (!Schema::hasColumn('po_customer_detail', 'keterangan')) {
                $table->string('keterangan')->nullable()->after('total'); // Optional notes
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_customer_detail', function (Blueprint $table) {
            if (Schema::hasColumn('po_customer_detail', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
            if (Schema::hasColumn('po_customer_detail', 'satuan')) {
                $table->dropColumn('satuan');
            }
            if (Schema::hasColumn('po_customer_detail', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
            if (Schema::hasColumn('po_customer_detail', 'nama_produk')) {
                $table->string('nama_produk')->nullable(false)->change();
            }
        });

        if (Schema::hasColumn('po_customer', 'tax_rate')) {
            Schema::table('po_customer', function (Blueprint $table) {
                $table->dropColumn('tax_rate');
            });
        }
    }
};