<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quill_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('quill_documents', 'clean_delta')) {
                $table->longText('clean_delta')->nullable()->after('content_delta');
            }
        });

        Schema::table('quill_changes', function (Blueprint $table) {
            if (! Schema::hasColumn('quill_changes', 'anchor_index')) {
                $table->unsignedInteger('anchor_index')->default(0)->after('status');
            }

            if (! Schema::hasColumn('quill_changes', 'anchor_length')) {
                $table->unsignedInteger('anchor_length')->default(0)->after('anchor_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quill_changes', function (Blueprint $table) {
            if (Schema::hasColumn('quill_changes', 'anchor_index')) {
                $table->dropColumn('anchor_index');
            }
            if (Schema::hasColumn('quill_changes', 'anchor_length')) {
                $table->dropColumn('anchor_length');
            }
        });

        Schema::table('quill_documents', function (Blueprint $table) {
            if (Schema::hasColumn('quill_documents', 'clean_delta')) {
                $table->dropColumn('clean_delta');
            }
        });
    }
};
