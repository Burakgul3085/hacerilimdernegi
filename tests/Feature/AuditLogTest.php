<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\MembershipApplication;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_post_writes_an_editor_readable_summary(): void
    {
        $this->actingAs($this->editor());

        Post::query()->create([
            'type' => 'announcement',
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'excerpt' => 'Kısa özet',
            'body' => '<p>Yazı gövdesi</p>',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $log = AuditLog::query()->where('model_type', Post::class)->first();

        $this->assertNotNull($log);
        $this->assertSame('Burak Gül «Web sitemiz yayında» yazısını ekledi.', $log->summary());
        $this->assertSame('Yazı / duyuru', $log->typeLabel());
        $this->assertSame('Ekledi', $log->actionLabel());
        $this->assertStringNotContainsString('App\\Models\\Post', $log->summary());
        $this->assertStringNotContainsString('created', $log->summary());
    }

    public function test_updating_a_post_names_the_changed_fields_in_turkish(): void
    {
        $post = Post::query()->create([
            'type' => 'announcement',
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'is_published' => true,
        ]);

        $this->actingAs($this->editor());

        $post->update(['title' => 'Yeni başlık', 'is_published' => false]);

        $log = AuditLog::query()->where('model_type', Post::class)->where('action', 'updated')->first();

        $this->assertNotNull($log);
        $this->assertSame('Burak Gül «Yeni başlık» yazısını güncelledi (Başlık, Yayında).', $log->summary());
        $this->assertStringContainsString('Başlık: Yeni başlık', $log->changeSummaryText());
        $this->assertStringContainsString('Yayında: Hayır', $log->changeSummaryText());
    }

    public function test_updating_a_user_does_not_store_the_password(): void
    {
        $this->actingAs($this->superAdmin());

        $user = User::factory()->create(['name' => 'Editör']);
        $user->update([
            'name' => 'Yeni Ad',
            'password' => 'secret-password',
        ]);

        $log = AuditLog::query()
            ->where('model_type', User::class)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Burak Gül «Yeni Ad» kullanıcısını güncelledi (Ad).', $log->summary());
        $this->assertStringNotContainsString('secret-password', json_encode($log->properties));
        $this->assertStringNotContainsString('password', strtolower($log->changeSummaryText()));
    }

    public function test_saving_site_settings_writes_a_single_readable_log(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageSettings::class)
            ->set('data.hero_title', 'Yeni giriş başlığı')
            ->call('save')
            ->assertHasNoErrors();

        $logs = AuditLog::query()->where('model_type', Setting::class)->get();

        $this->assertCount(1, $logs);
        $this->assertSame('Burak Gül site ayarlarını güncelledi.', $logs->first()->summary());
    }

    public function test_membership_status_change_is_logged_in_turkish(): void
    {
        $application = MembershipApplication::query()->create([
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($this->editor());

        $application->update(['status' => ApplicationStatus::Approved]);

        $log = AuditLog::query()
            ->where('model_type', MembershipApplication::class)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Burak Gül «Ayşe Yılmaz» üyelik başvurusunu güncelledi (Durum).', $log->summary());
        $this->assertStringContainsString('Durum: Onaylandı', $log->changeSummaryText());
    }

    public function test_unauthenticated_changes_are_not_logged(): void
    {
        Post::query()->create([
            'type' => 'announcement',
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'is_published' => true,
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_marking_a_contact_message_as_read_is_not_logged(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'Ziyaretçi',
            'email' => 'ziyaretci@example.com',
            'subject' => 'Merhaba',
            'message' => 'Kısa mesaj',
            'kvkk_accepted' => true,
            'is_read' => false,
        ]);

        $this->actingAs($this->editor());

        $message->forceFill(['is_read' => true])->save();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_legacy_properties_still_produce_a_readable_summary(): void
    {
        $log = AuditLog::query()->create([
            'user_id' => $this->editor()->id,
            'action' => 'deleted',
            'model_type' => Post::class,
            'model_id' => 1,
            'properties' => ['title' => 'Eski albüm kaydı'],
        ]);

        $this->assertSame('Burak Gül «Eski albüm kaydı» yazısını sildi.', $log->summary());
    }

    public function test_editors_can_read_the_audit_log_list_without_class_names(): void
    {
        $this->actingAs($this->editor());

        Post::query()->create([
            'type' => 'announcement',
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'is_published' => true,
        ]);

        Livewire::test(ListAuditLogs::class)
            ->assertOk()
            ->assertSee('Burak Gül «Web sitemiz yayında» yazısını ekledi.')
            ->assertSee('Yazı / duyuru')
            ->assertSee('Ekledi');
    }

    public function test_editors_can_open_the_audit_log_detail(): void
    {
        $this->actingAs($this->editor());

        Post::query()->create([
            'type' => 'announcement',
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'is_published' => true,
        ]);

        $log = AuditLog::query()->first();

        Livewire::test(ViewAuditLog::class, ['record' => $log->getKey()])
            ->assertOk()
            ->assertSee('Burak Gül «Web sitemiz yayında» yazısını ekledi.')
            ->assertSee('Başlık: Web sitemiz yayında')
            ->assertSee('Tür: Duyuru');
    }

    public function test_media_managers_can_access_audit_logs(): void
    {
        $this->actingAs(User::factory()->create([
            'name' => 'Medya Sorumlusu',
            'role' => UserRole::Media,
        ]));

        $this->assertTrue(AuditLogResource::canAccess());
    }

    public function test_guests_are_redirected_from_audit_logs(): void
    {
        $this->get('/yonetim/audit-logs')->assertRedirect();
    }

    public function test_editors_can_delete_a_single_audit_log_from_the_list(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);
        $log = $this->makeLog($editor);

        Livewire::test(ListAuditLogs::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($log))
            ->assertNotified();

        $this->assertModelMissing($log);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_editors_can_bulk_delete_selected_audit_logs(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);

        $first = $this->makeLog($editor, ['properties' => ['label' => 'Birinci kayıt']]);
        $second = $this->makeLog($editor, ['properties' => ['label' => 'İkinci kayıt']]);
        $kept = $this->makeLog($editor, ['properties' => ['label' => 'Kalan kayıt']]);

        Livewire::test(ListAuditLogs::class)
            ->selectTableRecords([$first, $second])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified();

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        $this->assertModelExists($kept);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_editors_can_delete_an_audit_log_from_the_detail_page(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);
        $log = $this->makeLog($editor);

        Livewire::test(ViewAuditLog::class, ['record' => $log->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        $this->assertModelMissing($log);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeLog(User $actor, array $attributes = []): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'created',
            'model_type' => Post::class,
            'model_id' => 1,
            'properties' => ['label' => 'Web sitemiz yayında'],
            ...$attributes,
        ]);
    }

    private function editor(): User
    {
        return User::factory()->create([
            'name' => 'Burak Gül',
            'role' => UserRole::Editor,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Burak Gül',
            'role' => UserRole::SuperAdmin,
        ]);
    }
}
