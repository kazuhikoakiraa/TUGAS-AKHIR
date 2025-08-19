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
        // Add new columns to penawaran table
        Schema::table('penawaran', function (Blueprint $table) {
            $table->text('terms_conditions')->nullable()->after('status');
            $table->decimal('total_sebelum_pajak', 15, 2)->default(0)->after('harga');
            $table->decimal('total_pajak', 15, 2)->default(0)->after('total_sebelum_pajak');
            $table->decimal('tax_rate', 5, 2)->default(11.00)->after('total_pajak');
        });

        // Create penawaran_details table
        Schema::create('penawaran_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penawaran_id')->constrained('penawaran')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->integer('jumlah')->default(1);
            $table->string('satuan', 50)->default('pcs');
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penawaran_details');

        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropColumn(['terms_conditions', 'total_sebelum_pajak', 'total_pajak', 'tax_rate']);
        });
    }
};
