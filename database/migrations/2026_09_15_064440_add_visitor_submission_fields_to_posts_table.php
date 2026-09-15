<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('submitter_email')->nullable()->after('author_name');
            $table->boolean('submitted_from_public')->default(false)->after('submitter_email');
            $table->timestamp('approval_notified_at')->nullable()->after('is_published');
        });

        DB::table('posts')
            ->whereIn('type', ['announcement', 'announcements', 'duyuru', 'duyurular'])
            ->update(['type' => 'article']);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'submitter_email',
                'submitted_from_public',
                'approval_notified_at',
            ]);
        });
    }
};
