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
        Schema::create('po_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_customer')->constrained('customers')->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->string('nomor_po')->unique();
            $table->date('tanggal_po');
            $table->string('jenis_po');
            $table->enum('status_po', ['draft', 'pending', 'approved', 'rejected', 'completed'])->default('draft');
            $table->decimal('total_sebelum_pajak', 15, 2)->default(0);
            $table->decimal('total_pajak', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_customer');
    }
};