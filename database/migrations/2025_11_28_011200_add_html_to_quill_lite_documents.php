<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quill_lite_documents', function (Blueprint $table) {
            $table->longText('html')->nullable()->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('quill_lite_documents', function (Blueprint $table) {
            $table->dropColumn('html');
        });
    }
};
