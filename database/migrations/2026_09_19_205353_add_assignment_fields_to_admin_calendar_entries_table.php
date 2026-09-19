<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_calendar_entries', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('created_by');
            $table->string('assignment_status')->nullable()->after('assigned_at');
        });

        DB::table('admin_calendar_entries')
            ->whereNull('created_by')
            ->update([
                'created_by' => DB::raw('user_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('admin_calendar_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['assigned_at', 'assignment_status']);
        });
    }
};
