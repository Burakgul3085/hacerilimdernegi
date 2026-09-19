<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eski kurulumda e-tablo migration'ı migrations tablosuna yazılıp tablolar
 * oluşmamış / yarım kalmış olabilir. Bu migration eksik tabloları tamamlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registration_spreadsheets')) {
            Schema::create('registration_spreadsheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->string('source', 32);
                $table->json('column_keys');
                $table->json('headers');
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('registration_spreadsheet_rows')) {
            Schema::create('registration_spreadsheet_rows', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registration_spreadsheet_id');
                $table->unsignedBigInteger('event_registration_id')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('cells');
                $table->timestamps();

                $table->foreign('registration_spreadsheet_id', 'rs_rows_spreadsheet_fk')
                    ->references('id')
                    ->on('registration_spreadsheets')
                    ->cascadeOnDelete();

                $table->foreign('event_registration_id', 'rs_rows_registration_fk')
                    ->references('id')
                    ->on('event_registrations')
                    ->nullOnDelete();

                $table->index(['registration_spreadsheet_id', 'sort_order'], 'rs_rows_spreadsheet_sort_idx');
            });
        }
    }

    public function down(): void
    {
        // Bilinçli olarak boş: onarım migration'ı geri alınmaz.
    }
};
