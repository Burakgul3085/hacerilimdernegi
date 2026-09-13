<?php

use App\Models\Activity;
use App\Models\ActivitySession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->string('location')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'starts_at']);
        });

        $center = Activity::query()->where('slug', 'cumartesi-merkez-dersleri')->first();

        if ($center !== null) {
            ActivitySession::query()->create([
                'activity_id' => $center->id,
                'starts_at' => now()->startOfWeek()->addDays(5)->setTime(14, 0)->subWeek(),
                'location' => 'Dernek merkezi',
                'note' => 'Tefsir ve merkez sohbeti',
            ]);
            ActivitySession::query()->create([
                'activity_id' => $center->id,
                'starts_at' => now()->startOfWeek()->addDays(5)->setTime(14, 0),
                'location' => 'Dernek merkezi',
                'note' => 'Tefsir ve merkez sohbeti',
            ]);
            ActivitySession::query()->create([
                'activity_id' => $center->id,
                'starts_at' => now()->startOfWeek()->addDays(5)->setTime(14, 0)->addWeek(),
                'location' => 'Dernek merkezi',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_sessions');
    }
};
