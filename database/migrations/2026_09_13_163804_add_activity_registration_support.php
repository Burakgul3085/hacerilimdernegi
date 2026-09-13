<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('registration_open')->default(true)->after('is_published');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreignId('activity_id')
                ->nullable()
                ->after('program_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('registration_open');
        });
    }
};
