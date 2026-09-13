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

    public function test_only_activities_appear_under_the_activities_group(): void
    {
        $this->actingAs($this->editor());

        Filament::setCurrentPanel('admin');

        $activityLabels = $this->navigationLabelsInGroup('Faaliyetler');
        $allLabels = $this->allNavigationLabels();

        $this->assertContains('Faaliyetler', $activityLabels);
        $this->assertNotContains('Ders ve sohbetler', $activityLabels);
        $this->assertNotContains('Kayıtlı programlar', $activityLabels);
        $this->assertNotContains('Ders ve sohbetler', $allLabels);
        $this->assertNotContains('Kayıtlı programlar', $allLabels);
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

    /**
     * @return list<string>
     */
    private function allNavigationLabels(): array
    {
        return collect(Filament::getCurrentOrDefaultPanel()->getNavigation())
            ->flatMap(fn (NavigationGroup $group) => $group->getItems())
            ->map(fn (NavigationItem $item): string => $item->getLabel())
            ->values()
            ->all();
    }

    private function editor(): User
    {
        return User::factory()->create(['role' => UserRole::Editor]);
    }
}
