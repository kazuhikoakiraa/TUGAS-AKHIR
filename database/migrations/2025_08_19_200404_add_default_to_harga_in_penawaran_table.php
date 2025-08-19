<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->change();
        });
    }
};