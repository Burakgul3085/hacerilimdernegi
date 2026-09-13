<?php

use App\Models\Activity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('cadence')->nullable()->after('excerpt');
            $table->json('highlights')->nullable()->after('description');
        });

        $center = Activity::query()->where('slug', 'cumartesi-merkez-dersleri')->first();

        if ($center !== null) {
            $center->cadence = 'Her cumartesi 14.00';
            $center->highlights = [
                ['title' => 'Kimler için', 'text' => 'Merkez derslerine katılmak isteyen herkes.'],
                ['title' => 'Nasıl katılır', 'text' => 'Dernek merkezine belirtilen saatte gelmeniz yeterlidir.'],
            ];
            $center->save();
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['cadence', 'highlights']);
        });
    }
};
