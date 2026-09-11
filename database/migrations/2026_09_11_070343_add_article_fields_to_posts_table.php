<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('subtitle')->nullable()->after('title');
            $table->string('location')->nullable()->after('excerpt');
            $table->json('gallery')->nullable()->after('image');
            $table->text('featured_quote')->nullable()->after('gallery');
            $table->string('source_url')->nullable()->after('featured_quote');
            $table->string('source_label')->nullable()->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'subtitle',
                'location',
                'gallery',
                'featured_quote',
                'source_url',
                'source_label',
            ]);
        });
    }
};
