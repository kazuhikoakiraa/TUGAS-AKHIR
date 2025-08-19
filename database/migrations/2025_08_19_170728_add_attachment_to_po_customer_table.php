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
        Schema::table('po_customer', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('tax_rate');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->text('keterangan')->nullable()->after('attachment_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_customer', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'keterangan']);
        });
    }
};
