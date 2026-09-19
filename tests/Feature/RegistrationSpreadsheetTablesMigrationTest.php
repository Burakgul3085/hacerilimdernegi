<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationSpreadsheetTablesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_migration_recreates_missing_spreadsheet_tables(): void
    {
        Schema::dropIfExists('registration_spreadsheet_rows');
        Schema::dropIfExists('registration_spreadsheets');

        $this->assertFalse(Schema::hasTable('registration_spreadsheets'));
        $this->assertFalse(Schema::hasTable('registration_spreadsheet_rows'));

        $migration = require database_path('migrations/2026_09_19_225933_ensure_registration_spreadsheet_tables_exist.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('registration_spreadsheets'));
        $this->assertTrue(Schema::hasTable('registration_spreadsheet_rows'));
    }

    public function test_ensure_migration_is_safe_when_tables_already_exist(): void
    {
        $this->assertTrue(Schema::hasTable('registration_spreadsheets'));
        $this->assertTrue(Schema::hasTable('registration_spreadsheet_rows'));

        $migration = require database_path('migrations/2026_09_19_225933_ensure_registration_spreadsheet_tables_exist.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('registration_spreadsheets'));
        $this->assertTrue(Schema::hasTable('registration_spreadsheet_rows'));
    }
}
