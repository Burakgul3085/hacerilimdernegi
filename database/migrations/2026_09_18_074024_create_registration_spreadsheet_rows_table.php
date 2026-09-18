<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('registration_spreadsheet_rows');
    }
};
