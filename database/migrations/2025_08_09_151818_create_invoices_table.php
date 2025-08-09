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
        Schema::create('invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_po_customer')->nullable()->constrained('po_customer')->onDelete('set null');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->string('nomor_invoice')->unique();
            $table->date('tanggal');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue'])->default('draft');
            $table->decimal('total_sebelum_pajak', 15, 2)->default(0);
            $table->decimal('total_pajak', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};