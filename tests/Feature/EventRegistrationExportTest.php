<?php

namespace Tests\Feature;

use App\Actions\ExportEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\RegistrationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class EventRegistrationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_workbook_gives_each_activity_its_own_sheet_and_selected_answers(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $poetry = $this->activity('Şiir Atölyesi', 'siir-atolyesi', [
            ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text', 'excel' => true],
            ['key' => 'sehir', 'label' => 'Şehir', 'type' => 'text', 'excel' => false],
        ]);
        $voyage = $this->activity('Satır Arası Seferleri', 'satir-arasi', [
            ['key' => 'gemi', 'label' => 'Gemi adı', 'type' => 'text', 'excel' => true],
        ]);

        $this->registration($poetry, 'Ayşe Yılmaz', 'ayse@example.com', '05320000000', [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '28'],
            ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'Gaziantep'],
        ]);
        $this->registration($voyage, 'Mehmet Demir', 'mehmet@example.com', '05440000000', [
            ['key' => 'gemi', 'label' => 'Gemi adı', 'value' => 'Hacer'],
        ]);

        $workbook = app(ExportEventRegistrations::class)->handle();
        $poetrySheet = $this->sheet($workbook['contents'], 'Şiir Atölyesi');
        $voyageSheet = $this->sheet($workbook['contents'], 'Satır Arası Seferleri');

        $this->assertStringContainsString('Ayşe Yılmaz', $poetrySheet);
        $this->assertStringContainsString('05320000000', $poetrySheet);
        $this->assertStringContainsString('28', $poetrySheet);
        $this->assertStringNotContainsString('Gaziantep', $poetrySheet);
        $this->assertStringNotContainsString('Mehmet Demir', $poetrySheet);
        $this->assertStringContainsString('Mehmet Demir', $voyageSheet);
        $this->assertStringContainsString('Hacer', $voyageSheet);
        $this->assertStringNotContainsString('Ayşe Yılmaz', $voyageSheet);
        $this->assertSame(1, AuditLog::query()->where('action', 'exported')->count());
    }

    public function test_status_filter_and_unassigned_rows_stay_in_their_own_sheet(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = $this->activity('İlmihal', 'ilmihal', [
            ['key' => 'not', 'label' => 'Not', 'type' => 'textarea', 'excel' => true],
        ]);
        $this->registration($activity, 'Bekleyen', 'bekleyen@example.com', null, [], ApplicationStatus::Pending);
        $this->registration($activity, 'Onaylı', 'onayli@example.com', null, [], ApplicationStatus::Approved);

        $event = Event::query()->create([
            'title' => 'Bağımsız söyleşi',
            'slug' => 'bagimsiz-soylesi',
            'is_published' => true,
        ]);
        EventRegistration::query()->create([
            'event_id' => $event->id,
            'name' => 'Bağımsız Başvuran',
            'email' => 'bagimsiz@example.com',
            'notes' => 'Not düştü.',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $pending = app(ExportEventRegistrations::class)->handle($activity, ApplicationStatus::Pending);
        $pendingSheet = $this->sheet($pending['contents'], 'İlmihal');

        $this->assertStringContainsString('Bekleyen', $pendingSheet);
        $this->assertStringNotContainsString('Onaylı', $pendingSheet);
        $this->assertStringNotContainsString('Bağımsız Başvuran', $pendingSheet);

        $all = app(ExportEventRegistrations::class)->handle();
        $other = $this->sheet($all['contents'], 'Diğer başvurular');

        $this->assertStringContainsString('Bağımsız Başvuran', $other);
        $this->assertStringContainsString('Not düştü.', $other);
        $this->assertStringNotContainsString('Bağımsız Başvuran', $this->sheet($all['contents'], 'İlmihal'));
    }

    public function test_removed_question_can_return_with_its_old_answers(): void
    {
        $activity = $this->activity('Kamp', 'kamp', [
            ['key' => 'yas', 'label' => 'Yaş', 'type' => 'number', 'excel' => true],
        ]);
        $this->registration($activity, 'Ayşe', 'ayse@example.com', null, [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '17'],
        ]);

        $activity->update([
            'registration_fields' => RegistrationForm::normalize([
                ['key' => 'sehir', 'label' => 'Şehir', 'type' => 'text', 'excel' => true],
            ]),
        ]);
        $activity->refresh();

        $this->assertSame('yas', $activity->excel_archived_questions[0]['key'] ?? null);
        $this->assertFalse($activity->excel_archived_questions[0]['include'] ?? true);
        $this->assertStringNotContainsString('>17<', $this->sheet(
            app(ExportEventRegistrations::class)->handle($activity)['contents'],
            'Kamp',
        ));

        $archive = $activity->excel_archived_questions;
        $archive[0]['include'] = true;
        $activity->update(['excel_archived_questions' => $archive]);

        $this->assertStringContainsString('17', $this->sheet(
            app(ExportEventRegistrations::class)->handle($activity->refresh())['contents'],
            'Kamp',
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function activity(string $title, string $slug, array $fields): Activity
    {
        return Activity::factory()->create([
            'title' => $title,
            'slug' => $slug,
            'registration_fields' => $fields,
        ]);
    }

    /**
     * @param  list<array{key: string, label: string, value: string}>  $answers
     */
    private function registration(
        Activity $activity,
        string $name,
        string $email,
        ?string $phone,
        array $answers,
        ApplicationStatus $status = ApplicationStatus::Pending,
    ): EventRegistration {
        return EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'answers' => $answers,
            'kvkk_accepted' => true,
            'status' => $status,
        ]);
    }

    private function sheet(string $xlsx, string $name): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $xlsx);

        $zip = new ZipArchive;
        $zip->open($path);
        $workbook = $zip->getFromName('xl/workbook.xml');
        preg_match('/name="'.preg_quote($name, '/').'"[^>]*r:id="(rId\d+)"/', (string) $workbook, $match);
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        preg_match('/Id="'.$match[1].'"[^>]*Target="([^"]+)"/', (string) $rels, $target);
        $xml = $zip->getFromName('xl/'.ltrim($target[1], '/'));
        $zip->close();
        @unlink($path);

        return (string) $xml;
    }
}
