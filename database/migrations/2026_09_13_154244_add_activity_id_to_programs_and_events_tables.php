<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
        });
    }
};
