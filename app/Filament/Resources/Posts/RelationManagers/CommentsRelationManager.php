<?php

namespace App\Filament\Resources\Posts\RelationManagers;

use App\Actions\NotifyPostCommentApproved;
use App\Enums\PostCommentStatus;
use App\Models\PostComment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Yorumlar';

    protected static ?string $modelLabel = 'yorum';

    protected static ?string $badgeColor = 'warning';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        if (! method_exists($ownerRecord, 'pendingComments')) {
            return null;
        }

        $count = $ownerRecord->pendingComments()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label('Ad')
                    ->required()
                    ->maxLength(80),
                TextInput::make('last_name')
                    ->label('Soyad')
                    ->required()
                    ->maxLength(80),
                TextInput::make('email')
                    ->label('E-posta')
                    ->email()
                    ->required()
                    ->maxLength(180),
                Textarea::make('body')
                    ->label('Yorum')
                    ->required()
                    ->rows(8)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Toggle::make('hide_name')
                    ->label('İsmi sitede gizle'),
                Select::make('status')
                    ->label('Durum')
                    ->options(collect(PostCommentStatus::cases())->mapWithKeys(
                        fn (PostCommentStatus $status): array => [$status->value => $status->label()],
                    )->all())
                    ->required(),
                TextInput::make('ip_address')
                    ->label('IP')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->orderByRaw("case status when 'pending' then 0 when 'approved' then 1 else 2 end")
                ->orderBy('created_at'))
            ->columns([
                TextColumn::make('first_name')
                    ->label('Ad soyad')
                    ->formatStateUsing(fn (PostComment $record): string => $record->fullName())
                    ->description(fn (PostComment $record): string => $record->hide_name ? 'İsim gizli' : 'İsim görünür')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('body')
                    ->label('Yorum')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                IconColumn::make('hide_name')
                    ->label('İsim gizli')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (PostCommentStatus $state): string => $state->label())
                    ->color(fn (PostCommentStatus $state): string => $state->color()),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->timezone('Europe/Istanbul'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(collect(PostCommentStatus::cases())->mapWithKeys(
                        fn (PostCommentStatus $status): array => [$status->value => $status->label()],
                    )->all()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Yorumu yayınla')
                    ->modalDescription('Yorum yazının altında görünür ve yazarına onay e-postası gider.')
                    ->visible(fn (PostComment $record): bool => $record->status !== PostCommentStatus::Approved)
                    ->action(function (PostComment $record): void {
                        $record->update(['status' => PostCommentStatus::Approved]);
                        $this->notifyApproval($record);
                    }),
                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Yorumu sil')
                    ->modalDescription('Yorum kalıcı olarak silinir ve siteden kalkar.')
                    ->modalSubmitActionLabel('Sil')
                    ->successNotificationTitle('Yorum silindi')
                    ->action(fn (PostComment $record): bool => (bool) $record->delete()),
                Action::make('reject')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Yorumu reddet')
                    ->modalDescription('Yorum siteden kalkar. Kayıt panelde durur.')
                    ->visible(fn (PostComment $record): bool => $record->status !== PostCommentStatus::Rejected)
                    ->action(function (PostComment $record): void {
                        $record->update(['status' => PostCommentStatus::Rejected]);

                        Notification::make()
                            ->title('Yorum reddedildi')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->after(function (PostComment $record): void {
                        $this->notifyApproval($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function notifyApproval(PostComment $comment): void
    {
        $comment->refresh();

        if ($comment->status !== PostCommentStatus::Approved || $comment->approval_notified_at !== null) {
            return;
        }

        $sent = app(NotifyPostCommentApproved::class)->handle($comment);

        if ($sent) {
            Notification::make()
                ->title('Yorum yayınlandı')
                ->body('Yazarına e-posta gönderildi.')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Yorum yayınlandı')
            ->body('Onay e-postası gönderilemedi.')
            ->warning()
            ->send();
    }
}
