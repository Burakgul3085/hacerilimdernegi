<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\RegistrationSpreadsheets\Pages\EditRegistrationSpreadsheet;
use App\Models\AuditLog;
use App\Models\RegistrationSpreadsheet;
use App\Models\RegistrationSpreadsheetShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SpreadsheetShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_link_shows_landing_page_without_consuming_it(): void
    {
        $spreadsheet = RegistrationSpreadsheet::factory()->create(['title' => 'Şiir Atölyesi Listesi']);
        $issued = RegistrationSpreadsheetShareLink::issue($spreadsheet, null, 24, true);

        $this->get(route('spreadsheet-share.show', ['token' => $issued['token']]))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Şiir Atölyesi Listesi')
            ->assertSee('Excel dosyasını indir');

        $this->assertSame(0, $issued['link']->fresh()->download_count);
    }

    public function test_expired_revoked_and_unknown_links_are_gone(): void
    {
        $spreadsheet = RegistrationSpreadsheet::factory()->create();
        $expired = RegistrationSpreadsheetShareLink::issue($spreadsheet, null, 1, false);
        $expired['link']->forceFill(['expires_at' => now()->subMinute()])->save();
        $revoked = RegistrationSpreadsheetShareLink::issue($spreadsheet, null, 24, false);
        $revoked['link']->forceFill(['revoked_at' => now()])->save();

        foreach ([$expired['token'], $revoked['token'], str_repeat('a', 48)] as $token) {
            $this->get(route('spreadsheet-share.show', ['token' => $token]))
                ->assertStatus(410)
                ->assertSee('Bu bağlantı artık kullanılamıyor');

            $this->post(route('spreadsheet-share.download', ['token' => $token]))
                ->assertStatus(410);
        }
    }

    public function test_download_returns_excel_and_single_use_link_closes(): void
    {
        $spreadsheet = RegistrationSpreadsheet::factory()->create();
        $issued = RegistrationSpreadsheetShareLink::issue($spreadsheet, null, 24, true);

        $response = $this->post(route('spreadsheet-share.download', ['token' => $issued['token']]));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertSame(1, $issued['link']->fresh()->download_count);

        $this->post(route('spreadsheet-share.download', ['token' => $issued['token']]))->assertStatus(410);
        $this->assertSame('Kullanıldı', $issued['link']->fresh()->statusLabel());
    }

    public function test_share_file_route_requires_panel_access_and_is_logged(): void
    {
        $spreadsheet = RegistrationSpreadsheet::factory()->create(['title' => 'Liste']);

        $this->get(route('admin.spreadsheets.share-file', $spreadsheet))->assertRedirect();

        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]))
            ->get(route('admin.spreadsheets.share-file', $spreadsheet))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'shared',
            'model_type' => RegistrationSpreadsheet::class,
            'model_id' => $spreadsheet->id,
        ]);
    }

    public function test_share_action_creates_link_and_link_can_be_revoked(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $spreadsheet = RegistrationSpreadsheet::factory()->create(['title' => 'Liste']);

        $this->actingAs($editor);

        $page = Livewire::test(EditRegistrationSpreadsheet::class, ['record' => $spreadsheet->getKey()])
            ->callAction('shareWhatsapp', ['hours' => 72, 'single_use' => false])
            ->assertHasNoActionErrors()
            ->assertNotified('Paylaşım bağlantısı hazır');

        $link = RegistrationSpreadsheetShareLink::query()->sole();
        $this->assertSame($editor->id, $link->user_id);
        $this->assertFalse($link->single_use);
        $this->assertTrue($link->expires_at->between(now()->addHours(71), now()->addHours(73)));
        $this->assertSame('shared', AuditLog::query()->latest('id')->value('action'));

        $this->view('filament.spreadsheet-share-links', ['links' => $spreadsheet->shareLinks()->with('user')->get()])
            ->assertSee('Oluşturulan bağlantılar')
            ->assertSee($editor->name)
            ->assertSee('Bağlantıyı kapat');

        $page->call('revokeSpreadsheetShareLink', $link->id);

        $this->assertFalse($link->fresh()->isActive());
        $this->assertSame('Kapatıldı', $link->fresh()->statusLabel());
    }
}
