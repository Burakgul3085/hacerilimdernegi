<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->boolean('reminder_enabled')->default(false);
            $table->string('reminder_offset')->nullable();
            $table->timestamp('remind_at')->nullable();
            $table->string('reminder_status')->default('none');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('reminder_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'starts_at']);
            $table->index(['reminder_status', 'remind_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_calendar_entries');
    }
};
