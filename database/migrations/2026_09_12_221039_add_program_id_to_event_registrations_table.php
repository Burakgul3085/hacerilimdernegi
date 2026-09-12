<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->nullable()->change();
            $table->foreignId('program_id')->nullable()->after('event_id')->constrained()->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['event_id']);
            $table->dropColumn('program_id');
            $table->unsignedBigInteger('event_id')->nullable(false)->change();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }
};
