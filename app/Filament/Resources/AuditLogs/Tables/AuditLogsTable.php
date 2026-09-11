<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Models\MembershipApplication;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Henüz denetim kaydı yok')
            ->emptyStateDescription('Yönetim panelinde bir içerik eklenince, güncellenince veya silinince kim ne yaptı burada görünür.')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->timezone('Europe/Istanbul')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Kim')
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('summary')
                    ->label('Ne yaptı')
                    ->getStateUsing(fn (AuditLog $record): string => $record->summary())
                    ->wrap()
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function (Builder $builder) use ($search): void {
                                $builder->where('properties', 'like', '%'.$search.'%')
                                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                                        $userQuery->where('name', 'like', '%'.$search.'%');
                                    });
                            });
                        },
                    ),
                TextColumn::make('typeLabel')
                    ->label('Bölüm')
                    ->badge()
                    ->getStateUsing(fn (AuditLog $record): string => $record->typeLabel()),
                TextColumn::make('action')
                    ->label('İşlem')
                    ->badge()
                    ->color(fn (AuditLog $record): string => match ($record->action) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (AuditLog $record): string => $record->actionLabel()),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('İşlem')
                    ->options([
                        'created' => 'Ekledi',
                        'updated' => 'Güncelledi',
                        'deleted' => 'Sildi',
                    ]),
                SelectFilter::make('model_type')
                    ->label('Bölüm')
                    ->options([
                        Post::class => 'Yazı / duyuru',
                        MediaAlbum::class => 'Albüm',
                        MediaItem::class => 'Medya öğesi',
                        Program::class => 'Program',
                        Event::class => 'Etkinlik',
                        Page::class => 'Sayfa',
                        Category::class => 'Kategori',
                        User::class => 'Kullanıcı',
                        Setting::class => 'Site ayarı',
                        MembershipApplication::class => 'Üyelik başvurusu',
                        EventRegistration::class => 'Etkinlik kaydı',
                        ContactMessage::class => 'İletişim mesajı',
                        NewsletterSubscriber::class => 'E-bülten',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Detay'),
                DeleteAction::make()->label('Sil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri sil'),
                ]),
            ]);
    }
}
