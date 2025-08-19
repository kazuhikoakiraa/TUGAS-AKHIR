<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->string('nama_produk')->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->string('nama_produk')->change();
        });
    }
};
