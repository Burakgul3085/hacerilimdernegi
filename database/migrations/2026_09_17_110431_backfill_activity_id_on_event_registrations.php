<?php

use App\Models\EventRegistration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EventRegistration::fileUnderParentActivity();
    }

    public function down(): void
    {
        // Geri alınmaz: faaliyet klasörüne taşınan kayıtlar formun gerçek sahibidir.
    }
};
