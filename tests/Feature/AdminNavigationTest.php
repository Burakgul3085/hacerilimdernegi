<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_and_programs_appear_under_the_projects_group(): void
    {
        $this->actingAs($this->editor());

        Filament::setCurrentPanel('admin');

        $projectLabels = $this->navigationLabelsInGroup('Projeler');
        $contentLabels = $this->navigationLabelsInGroup('İçerik');

        $this->assertContains('Etkinlikler', $projectLabels);
        $this->assertContains('Programlar', $projectLabels);
        $this->assertNotContains('Etkinlikler', $contentLabels);
        $this->assertNotContains('Programlar', $contentLabels);
    }

    public function test_pages_appear_as_corporate_in_the_content_group(): void
    {
        $this->actingAs($this->editor());

        Filament::setCurrentPanel('admin');

        $contentLabels = $this->navigationLabelsInGroup('İçerik');

        $this->assertContains('Kurumsal', $contentLabels);
        $this->assertNotContains('Sayfalar', $contentLabels);
    }

    /**
     * @return list<string>
     */
    private function navigationLabelsInGroup(string $groupLabel): array
    {
        $group = collect(Filament::getCurrentOrDefaultPanel()->getNavigation())
            ->first(fn (NavigationGroup $group): bool => $group->getLabel() === $groupLabel);

        $this->assertNotNull($group, "Missing navigation group [{$groupLabel}].");

        return collect($group->getItems())
            ->map(fn (NavigationItem $item): string => $item->getLabel())
            ->values()
            ->all();
    }

    private function editor(): User
    {
        return User::factory()->create(['role' => UserRole::Editor]);
    }
}
