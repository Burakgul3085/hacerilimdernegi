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
            $table->foreignId('registration_spreadsheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('cells');
            $table->timestamps();

            $table->index(['registration_spreadsheet_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_spreadsheet_rows');
    }
};
