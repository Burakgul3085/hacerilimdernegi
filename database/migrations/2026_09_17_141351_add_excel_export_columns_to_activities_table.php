<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->json('excel_columns')->nullable()->after('registration_fields');
            $table->json('excel_archived_questions')->nullable()->after('excel_columns');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['excel_columns', 'excel_archived_questions']);
        });
    }
};
