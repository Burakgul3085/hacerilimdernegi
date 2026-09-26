<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 180);
            $table->text('body');
            $table->boolean('hide_name')->default(false);
            $table->string('status')->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('approval_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};
