<?php

namespace App\Filament\Resources\EventRegistrations\Pages;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\EventRegistrations\EventRegistrationResource;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListActivityRegistrations extends ListRecords
{
    protected static string $resource = EventRegistrationResource::class;

    public int $activity = 0;

    private ?Activity $resolvedActivity = null;

    public function mount(): void
    {
        if ($this->activity === 0) {
            $this->activity = (int) request()->route('activity');
        }

        $this->resolvedActivity = Activity::query()->findOrFail($this->activity);

        parent::mount();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->activityRecord()->title;
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->activityRecord()->title;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $activity = $this->activityRecord()->loadCount([
            'registrations',
            'registrations as pending_registrations_count' => fn (Builder $query): Builder => $query->where(
                'status',
                ApplicationStatus::Pending,
            ),
        ]);

        return $activity->registrations_count.' başvuru, '.$activity->pending_registrations_count.' beklemede. Diğer faaliyetlerin kayıtları bu listede görünmez.';
    }

    public function getBreadcrumb(): string
    {
        return $this->activityRecord()->title;
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->forActivity($this->activity);
    }

    private function activityRecord(): Activity
    {
        return $this->resolvedActivity ??= Activity::query()->findOrFail($this->activity);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Bu faaliyete henüz başvuru yok')
            ->emptyStateDescription('Form doldurulduğunda başvurular yalnızca bu faaliyetin listesinde görünür.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Tüm faaliyetler')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(EventRegistrationResource::getUrl()),
        ];
    }
}
