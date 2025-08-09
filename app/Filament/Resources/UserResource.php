<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\Widgets;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Filament\Notifications\Notification;
use App\Models\ActivityLog;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'User';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi User')
                    ->description('Masukkan data lengkap user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap user')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('user@example.com')
                            ->prefixIcon('heroicon-o-envelope')
                            ->helperText('Email akan digunakan untuk login dan verifikasi akan dikirim otomatis'),

                        Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->label('Roles'),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->placeholder('Masukkan password')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->helperText('Minimal 8 karakter')
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn (string $context): bool => $context === 'create'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->placeholder('Ulangi password')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->same('password')
                            ->visible(fn (string $context): bool => $context === 'create'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama User')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email)
                    ->wrap(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email berhasil disalin')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->searchable(),


                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email Terverifikasi')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Belum diverifikasi')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state): string => $state ? 'Terverifikasi' : 'Belum Verifikasi'),

                Tables\Columns\TextColumn::make('latest_activity')
                    ->label('Aktivitas Terakhir')
                    ->state(function (User $record): string {
                        $latestActivity = $record->activityLogs()->latest()->first();
                        if ($latestActivity) {
                            return $latestActivity->event_label . ' oleh ' . $latestActivity->causer_name . ' (' . $latestActivity->created_at->diffForHumans() . ')';
                        }
                        return 'Tidak ada aktivitas';
                    })
                    ->wrap()
                    ->tooltip(function (User $record): string {
                        $activities = $record->activityLogs()->latest()->limit(3)->get();
                        return $activities->map(fn($activity) =>
                            $activity->event_label . ' oleh ' . $activity->causer_name . ' - ' . $activity->created_at->format('d M Y H:i')
                        )->implode("\n");
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options([
                        'manager' => 'Manager',
                        'staff' => 'Staff',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('email_verified')
                    ->label('Email Terverifikasi')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat dari tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dibuat dari ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString())
                                ->removeField('created_from');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dibuat sampai ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString())
                                ->removeField('created_until');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus User')
                    ->modalDescription('Apakah Anda yakin ingin menghapus user ini? Data yang sudah dihapus tidak dapat dikembalikan.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus User Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua user yang dipilih? Data yang sudah dihapus tidak dapat dikembalikan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-user')
            ->emptyStateHeading('Belum ada data user')
            ->emptyStateDescription('Hasil pencarian tidak ditemukan.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi User')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nama User')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email berhasil disalin'),

                                Infolists\Components\TextEntry::make('role')
                                    ->label('Role')
                                    ->icon('heroicon-o-shield-check')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'manager' => 'warning',
                                        'staff' => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'manager' => 'Manager',
                                        'staff' => 'Staff',
                                        default => $state,
                                    }),

                                Infolists\Components\TextEntry::make('email_verified_at')
                                    ->label('Status Email')
                                    ->icon('heroicon-o-check-circle')
                                    ->badge()
                                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                                    ->formatStateUsing(fn ($state): string => $state ? 'Terverifikasi pada ' . \Carbon\Carbon::parse($state)->format('d F Y, H:i:s') : 'Belum Diverifikasi'),
                            ]),
                    ])
                    ->icon('heroicon-o-information-circle'),

                Infolists\Components\Section::make('Riwayat Aktivitas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('activityLogs')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('event')
                                            ->label('Aktivitas')
                                            ->badge()
                                            ->color(fn (ActivityLog $record): string => $record->event_color)
                                            ->formatStateUsing(fn (ActivityLog $record): string => $record->event_label),

                                        Infolists\Components\TextEntry::make('causer_name')
                                            ->label('Dilakukan oleh')
                                            ->icon('heroicon-o-user'),

                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Waktu')
                                            ->dateTime('d M Y, H:i:s')
                                            ->since(),
                                    ]),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Deskripsi')
                                    ->columnSpanFull(),
                            ])
                            ->contained(false)
                    ])
                    ->icon('heroicon-o-clock')
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d F Y, H:i:s')
                                    ->icon('heroicon-o-calendar-days'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d F Y, H:i:s')
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'role'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Role' => match ($record->role) {
                'manager' => 'Manager',
                'staff' => 'Staff',
                default => $record->role,
            },
        ];
    }
}
