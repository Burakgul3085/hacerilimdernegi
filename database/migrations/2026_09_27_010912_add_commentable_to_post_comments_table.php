<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->nullableMorphs('commentable');
        });

        DB::table('post_comments')->update([
            'commentable_type' => Post::class,
            'commentable_id' => DB::raw('post_id'),
        ]);

        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        Schema::table('post_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable()->change();
        });

        Schema::table('post_comments', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        DB::table('post_comments')->whereNull('post_id')->delete();

        Schema::table('post_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->dropMorphs('commentable');
        });
    }
};
