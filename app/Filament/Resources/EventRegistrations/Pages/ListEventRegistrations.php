<?php

namespace App\Filament\Resources\EventRegistrations\Pages;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\EventRegistrations\EventRegistrationResource;
use App\Filament\Resources\EventRegistrations\RegistrationExcelAction;
use App\Filament\Resources\EventRegistrations\Tables\EventRegistrationFoldersTable;
use App\Models\Activity;
use App\Models\EventRegistration;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListEventRegistrations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = EventRegistrationResource::class;

    protected static ?string $breadcrumb = 'Faaliyetler';

    public function getTitle(): string|Htmlable
    {
        return 'Program kayıtları';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Program kayıtları';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Başvurular faaliyete göre ayrılır. Bir faaliyete tıklayınca yalnızca onun başvuranları açılır.';
    }

    public function table(Table $table): Table
    {
        return EventRegistrationFoldersTable::configure($table);
    }

    /**
     * @return Builder<Activity>
     */
    protected function getTableQuery(): Builder
    {
        return Activity::query()
            ->withCount([
                'registrations',
                'registrations as pending_registrations_count' => fn (Builder $query): Builder => $query->where(
                    'status',
                    ApplicationStatus::Pending,
                ),
            ])
            ->withMax('registrations', 'created_at')
            ->ordered();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $unassignedCount = EventRegistration::query()->unassigned()->count();

        $actions = [
            RegistrationExcelAction::make(),
        ];

        if ($unassignedCount === 0) {
            return $actions;
        }

        return [
            ...$actions,
            Action::make('unassigned')
                ->label('Diğer başvurular ('.$unassignedCount.')')
                ->icon('heroicon-o-question-mark-circle')
                ->color('warning')
                ->url(EventRegistrationResource::getUrl('unassigned')),
        ];
    }
}
