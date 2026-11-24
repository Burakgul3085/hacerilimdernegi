<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_spreadsheet_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_spreadsheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->boolean('single_use')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_spreadsheet_share_links');
    }
};
