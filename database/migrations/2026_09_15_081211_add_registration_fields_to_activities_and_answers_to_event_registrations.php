<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->json('registration_fields')->nullable()->after('registration_open');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->json('answers')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('registration_fields');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('answers');
        });
    }
};
