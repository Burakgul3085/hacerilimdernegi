<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_albums', function (Blueprint $table) {
            $table->foreignId('activity_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('activities')
                ->nullOnDelete();
            $table->unique('activity_id');
        });

        Schema::table('media_items', function (Blueprint $table) {
            $table->string('source_path')->nullable()->after('path');
            $table->unique(['media_album_id', 'source_path']);
        });
    }

    public function down(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->dropUnique(['media_album_id', 'source_path']);
            $table->dropColumn('source_path');
        });

        Schema::table('media_albums', function (Blueprint $table) {
            $table->dropUnique(['activity_id']);
            $table->dropConstrainedForeignId('activity_id');
        });
    }
};
