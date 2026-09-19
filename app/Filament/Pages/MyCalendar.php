<?php

namespace App\Filament\Pages;

use App\Actions\SaveAdminCalendarEntry;
use App\Enums\CalendarAssignmentStatus;
use App\Enums\CalendarReminderOffset;
use App\Enums\CalendarReminderStatus;
use App\Enums\UserRole;
use App\Models\AdminCalendarEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class MyCalendar extends Page
{
    protected static ?string $title = 'Takvimim';

    protected static ?string $navigationLabel = 'Takvimim';

    protected static ?string $slug = 'takvimim';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = -10;

    protected string $view = 'filament.pages.my-calendar';

    public string $viewMode = 'month';

    public string $focusDate = '';

    public bool $formOpen = false;

    public ?int $editingId = null;

    public bool $canChangeAssignee = true;

    public bool $showAssignmentStatus = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && in_array($user->role, [UserRole::SuperAdmin, UserRole::Editor, UserRole::Media], true);
    }

    public function mount(): void
    {
        $this->focusDate = now()->toDateString();
        $this->form->fill($this->defaultFormState());
    }

    public function form(Schema $schema): Schema
    {
        $actor = auth()->user();
        $isSuperAdmin = $actor instanceof User && $actor->isSuperAdmin();

        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Açıklama / not')
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Select::make('assigned_to_id')
                            ->label('Kime ata?')
                            ->helperText('Boş bırakırsanız not yalnızca sizin takviminizde kalır.')
                            ->placeholder('Kişisel not (kimseye atama)')
                            ->searchable()
                            ->options(fn (): array => $this->superAdminOptions())
                            ->visible(fn (): bool => $isSuperAdmin && $this->canChangeAssignee)
                            ->columnSpanFull(),
                        Select::make('assignment_status')
                            ->label('Görev durumu')
                            ->options(collect(CalendarAssignmentStatus::cases())->mapWithKeys(
                                fn (CalendarAssignmentStatus $status): array => [$status->value => $status->label()],
                            ))
                            ->visible(fn (): bool => $this->showAssignmentStatus)
                            ->required(fn (): bool => $this->showAssignmentStatus)
                            ->columnSpanFull(),
                        Toggle::make('all_day')
                            ->label('Tüm gün')
                            ->live()
                            ->columnSpanFull(),
                        DateTimePicker::make('starts_at')
                            ->label('Başlangıç')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->timezone('Europe/Istanbul')
                            ->displayFormat('d.m.Y H:i'),
                        DateTimePicker::make('ends_at')
                            ->label('Bitiş')
                            ->seconds(false)
                            ->native(false)
                            ->timezone('Europe/Istanbul')
                            ->displayFormat('d.m.Y H:i')
                            ->visible(fn ($get): bool => ! (bool) $get('all_day')),
                        Toggle::make('reminder_enabled')
                            ->label('E-posta hatırlatması')
                            ->helperText('Atanan görevde hatırlatma, görevin sahibinin e-postasına gider.')
                            ->live()
                            ->columnSpanFull(),
                        Select::make('reminder_offset')
                            ->label('Ne zaman hatırlatılsın?')
                            ->options(collect(CalendarReminderOffset::cases())->mapWithKeys(
                                fn (CalendarReminderOffset $offset): array => [$offset->value => $offset->label()],
                            ))
                            ->default(CalendarReminderOffset::AtStart->value)
                            ->visible(fn ($get): bool => (bool) $get('reminder_enabled'))
                            ->required(fn ($get): bool => (bool) $get('reminder_enabled'))
                            ->live(),
                        DateTimePicker::make('remind_at')
                            ->label('Özel hatırlatma zamanı')
                            ->seconds(false)
                            ->native(false)
                            ->timezone('Europe/Istanbul')
                            ->displayFormat('d.m.Y H:i')
                            ->visible(fn ($get): bool => (bool) $get('reminder_enabled')
                                && $get('reminder_offset') === CalendarReminderOffset::Custom->value)
                            ->required(fn ($get): bool => (bool) $get('reminder_enabled')
                                && $get('reminder_offset') === CalendarReminderOffset::Custom->value),
                    ])
                    ->columns(2),
            ]);
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['month', 'week', 'agenda'], true)) {
            return;
        }

        $this->viewMode = $mode;
    }

    public function goToday(): void
    {
        $this->focusDate = now()->toDateString();
    }

    public function shiftPeriod(int $direction): void
    {
        $focus = Carbon::parse($this->focusDate)->timezone((string) config('app.timezone'));

        $this->focusDate = match ($this->viewMode) {
            'week' => $focus->addWeeks($direction)->toDateString(),
            'agenda' => $focus->addWeeks($direction)->toDateString(),
            default => $focus->addMonthsNoOverflow($direction)->toDateString(),
        };
    }

    public function openCreate(?string $startsAt = null): void
    {
        $this->authorize('create', AdminCalendarEntry::class);

        $this->editingId = null;
        $this->canChangeAssignee = auth()->user()?->isSuperAdmin() ?? false;
        $this->showAssignmentStatus = false;
        $start = $startsAt
            ? Carbon::parse($startsAt)->timezone((string) config('app.timezone'))
            : now()->timezone((string) config('app.timezone'))->addHour()->startOfHour();

        $this->form->fill([
            ...$this->defaultFormState(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
        ]);
        $this->formOpen = true;
    }

    public function openEdit(int $entryId): void
    {
        $entry = AdminCalendarEntry::query()->with(['user', 'creator'])->findOrFail($entryId);
        $this->authorize('update', $entry);

        $actor = auth()->user();
        $this->editingId = $entry->id;
        $this->canChangeAssignee = $actor instanceof User
            && $actor->isSuperAdmin()
            && ($entry->isCreatedBy($actor) || ! $entry->isAssigned());
        $this->showAssignmentStatus = $entry->isAssigned();

        $this->form->fill([
            'title' => $entry->title,
            'description' => $entry->description,
            'starts_at' => $entry->starts_at,
            'ends_at' => $entry->ends_at,
            'all_day' => $entry->all_day,
            'reminder_enabled' => $entry->reminder_enabled,
            'reminder_offset' => $entry->reminder_offset?->value,
            'remind_at' => $entry->remind_at,
            'assigned_to_id' => $entry->isAssigned() ? $entry->user_id : null,
            'assignment_status' => $entry->assignment_status?->value ?? CalendarAssignmentStatus::InProgress->value,
        ]);
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->editingId = null;
        $this->canChangeAssignee = auth()->user()?->isSuperAdmin() ?? false;
        $this->showAssignmentStatus = false;
        $this->form->fill($this->defaultFormState());
    }

    public function saveEntry(SaveAdminCalendarEntry $action): void
    {
        $state = $this->form->getState();
        $entry = $this->editingId
            ? AdminCalendarEntry::query()->findOrFail($this->editingId)
            : null;

        if ($entry !== null) {
            $this->authorize('update', $entry);
        } else {
            $this->authorize('create', AdminCalendarEntry::class);
        }

        $action->handle(auth()->user(), $state, $entry);

        Notification::make()
            ->title($entry ? 'Not güncellendi' : 'Not eklendi')
            ->success()
            ->send();

        $this->closeForm();
    }

    public function deleteEntry(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $entry = AdminCalendarEntry::query()->findOrFail($this->editingId);
        $this->authorize('delete', $entry);
        $entry->delete();

        Notification::make()
            ->title('Not silindi')
            ->success()
            ->send();

        $this->closeForm();
    }

    /**
     * @return array{label: string, range: string, days: list<array<string, mixed>>, agenda: list<array<string, mixed>>, stats: array{today: int, upcoming: int, pending: int}}
     */
    public function getCalendarDataProperty(): array
    {
        $timezone = (string) config('app.timezone');
        $focus = Carbon::parse($this->focusDate)->timezone($timezone)->startOfDay();
        /** @var User $user */
        $user = auth()->user();

        [$rangeStart, $rangeEnd, $label, $rangeLabel] = match ($this->viewMode) {
            'week' => $this->weekBounds($focus),
            'agenda' => [
                $focus->copy()->startOfDay(),
                $focus->copy()->addDays(29)->endOfDay(),
                'Ajanda',
                $focus->format('d.m.Y').' – '.$focus->copy()->addDays(29)->format('d.m.Y'),
            ],
            default => $this->monthBounds($focus),
        };

        $entries = AdminCalendarEntry::query()
            ->with(['user', 'creator'])
            ->visibleTo($user)
            ->where('starts_at', '<=', $rangeEnd)
            ->where(function ($query) use ($rangeStart): void {
                $query->where('ends_at', '>=', $rangeStart)
                    ->orWhere(function ($inner) use ($rangeStart): void {
                        $inner->whereNull('ends_at')->where('starts_at', '>=', $rangeStart);
                    });
            })
            ->orderBy('starts_at')
            ->get();

        $byDate = $entries->groupBy(
            fn (AdminCalendarEntry $entry): string => $entry->starts_at->timezone($timezone)->toDateString(),
        );

        $days = [];

        if ($this->viewMode === 'month') {
            $cursor = $rangeStart->copy();
            while ($cursor->lte($rangeEnd)) {
                $key = $cursor->toDateString();
                $days[] = $this->dayPayload($cursor, $byDate->get($key, collect()), $focus->month);
                $cursor->addDay();
            }
        } elseif ($this->viewMode === 'week') {
            $cursor = $rangeStart->copy();
            while ($cursor->lte($rangeEnd)) {
                $key = $cursor->toDateString();
                $days[] = $this->dayPayload($cursor, $byDate->get($key, collect()), null);
                $cursor->addDay();
            }
        }

        $agenda = $entries
            ->filter(fn (AdminCalendarEntry $entry): bool => $entry->starts_at->gte(now()->timezone($timezone)->startOfDay())
                || $entry->starts_at->betweenIncluded($rangeStart, $rangeEnd))
            ->values()
            ->map(fn (AdminCalendarEntry $entry): array => $this->entryPayload($entry, $user))
            ->all();

        $todayStart = now()->timezone($timezone)->startOfDay();
        $todayEnd = now()->timezone($timezone)->endOfDay();

        $stats = [
            'today' => AdminCalendarEntry::query()
                ->visibleTo($user)
                ->whereBetween('starts_at', [$todayStart, $todayEnd])
                ->count(),
            'upcoming' => AdminCalendarEntry::query()
                ->visibleTo($user)
                ->where('starts_at', '>', $todayEnd)
                ->where('starts_at', '<=', $todayEnd->copy()->addDays(7))
                ->count(),
            'pending' => AdminCalendarEntry::query()
                ->visibleTo($user)
                ->where('reminder_status', CalendarReminderStatus::Pending)
                ->count(),
        ];

        return [
            'label' => $label,
            'range' => $rangeLabel,
            'days' => $days,
            'agenda' => $agenda,
            'stats' => $stats,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function superAdminOptions(): array
    {
        $actorId = (int) auth()->id();

        return User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('id', '!=', $actorId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormState(): array
    {
        $start = now()->timezone((string) config('app.timezone'))->addHour()->startOfHour();

        return [
            'title' => '',
            'description' => '',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'all_day' => false,
            'reminder_enabled' => false,
            'reminder_offset' => CalendarReminderOffset::AtStart->value,
            'remind_at' => null,
            'assigned_to_id' => null,
            'assignment_status' => CalendarAssignmentStatus::InProgress->value,
        ];
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string, 3: string}
     */
    private function monthBounds(CarbonInterface $focus): array
    {
        $start = $focus->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $focus->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return [
            $start,
            $end,
            $focus->translatedFormat('F Y'),
            $focus->format('m.Y'),
        ];
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string, 3: string}
     */
    private function weekBounds(CarbonInterface $focus): array
    {
        $start = $focus->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $focus->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return [
            $start,
            $end,
            'Hafta',
            $start->format('d.m').' – '.$end->format('d.m.Y'),
        ];
    }

    /**
     * @param  Collection<int, AdminCalendarEntry>  $entries
     * @return array<string, mixed>
     */
    private function dayPayload(CarbonInterface $day, Collection $entries, ?int $focusMonth): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'date' => $day->toDateString(),
            'day' => $day->day,
            'weekday' => $day->translatedFormat('D'),
            'is_today' => $day->isToday(),
            'is_outside' => $focusMonth !== null && $day->month !== $focusMonth,
            'entries' => $entries->map(fn (AdminCalendarEntry $entry): array => $this->entryPayload($entry, $user))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entryPayload(AdminCalendarEntry $entry, User $viewer): array
    {
        $timezone = (string) config('app.timezone');
        $starts = $entry->starts_at->timezone($timezone);
        $assignmentBadge = null;

        if ($entry->isAssigned()) {
            if ($entry->isCreatedBy($viewer)) {
                $assignmentBadge = 'Atandı: '.($entry->user?->name ?? 'Süper yönetici');
            } elseif ($entry->isOwnedBy($viewer)) {
                $assignmentBadge = 'Atayan: '.($entry->creator?->name ?? 'Süper yönetici');
            }
        }

        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'description' => $entry->description,
            'time_label' => $entry->all_day
                ? 'Tüm gün'
                : $starts->format('H:i'),
            'date_label' => $starts->translatedFormat('d M Y'),
            'starts_at' => $starts->toIso8601String(),
            'reminder_status' => $entry->reminder_status->value,
            'reminder_label' => $entry->reminder_status->label(),
            'has_reminder' => $entry->reminder_enabled,
            'assignment_badge' => $assignmentBadge,
            'assignment_status_label' => $entry->assignment_status?->label(),
            'is_assigned' => $entry->isAssigned(),
        ];
    }
}
