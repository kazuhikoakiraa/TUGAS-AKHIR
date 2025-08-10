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
        Schema::table('invoice', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['id_po_customer']);

            // Modify the column to not allow null and add cascade delete
            $table->foreignId('id_po_customer')->change()->constrained('po_customer')->onDelete('cascade');

            // Add indexes for optimization
            $table->index(['status'], 'idx_invoice_status');
            $table->index(['tanggal'], 'idx_invoice_tanggal');
            $table->index(['created_at'], 'idx_invoice_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice', function (Blueprint $table) {
            // Drop the indexes
            $table->dropIndex('idx_invoice_status');
            $table->dropIndex('idx_invoice_tanggal');
            $table->dropIndex('idx_invoice_created_at');

            // Drop foreign key constraint
            $table->dropForeign(['id_po_customer']);

            // Restore original foreign key with set null
            $table->foreignId('id_po_customer')->nullable()->change()->constrained('po_customer')->onDelete('set null');
        });
    }
};
