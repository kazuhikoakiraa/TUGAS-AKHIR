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
                Forms\Components\Section::make('User Information')
                    ->description('Enter complete user data')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter user full name')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('user@example.com')
                            ->prefixIcon('heroicon-o-envelope')
                            ->helperText('Email will be used for login and verification will be sent automatically'),

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
                            ->placeholder('Enter password')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->helperText('Minimum 8 characters')
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn (string $context): bool => $context === 'create'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->placeholder('Repeat password')
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
                    ->label('User Name')
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
                    ->copyMessage('Email copied successfully')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->searchable(),


                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Not verified')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state): string => $state ? 'Verified' : 'Not Verified'),

                Tables\Columns\TextColumn::make('latest_activity')
                    ->label('Latest Activity')
                    ->state(function (User $record): string {
                        $latestActivity = $record->activityLogs()->latest()->first();
                        if ($latestActivity) {
                            return $latestActivity->event_label . ' ' . __('activity.by', [], 'en') . ' ' . $latestActivity->causer_name . ' (' . $latestActivity->created_at->diffForHumans() . ')';
                        }
                        return __('activity.no_activity', [], 'en');
                    })
                    ->wrap()
                    ->tooltip(function (User $record): string {
                        $activities = $record->activityLogs()->latest()->limit(3)->get();
                        return $activities->map(fn($activity) =>
                            $activity->event_label . ' ' . __('activity.by', [], 'en') . ' ' . $activity->causer_name . ' - ' . $activity->created_at->format('d M Y H:i')
                        )->implode("\n");
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Filter Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('email_verified')
                    ->label('Email Verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until date'),
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
                            $indicators[] = Tables\Filters\Indicator::make('Created from ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString())
                                ->removeField('created_from');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Created until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString())
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
                    ->modalHeading('Delete User')
                    ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Users')
                        ->modalDescription('Are you sure you want to delete all selected users? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete All'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-user')
            ->emptyStateHeading('No user data yet')
            ->emptyStateDescription('Search results not found.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('User Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('User Name')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied successfully'),

                                Infolists\Components\TextEntry::make('roles.name')
                                    ->label('Roles')
                                    ->icon('heroicon-o-shield-check')
                                    ->badge()
                                    ->separator(',')
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('email_verified_at')
                                    ->label('Email Status')
                                    ->icon('heroicon-o-check-circle')
                                    ->badge()
                                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                                    ->formatStateUsing(fn ($state): string => $state ? 'Verified on ' . \Carbon\Carbon::parse($state)->format('d F Y, H:i:s') : 'Not Verified'),
                            ]),
                    ])
                    ->icon('heroicon-o-information-circle'),

                Infolists\Components\Section::make('Activity History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('activityLogs')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('event')
                                            ->label('Activity')
                                            ->badge()
                                            ->color(fn (ActivityLog $record): string => $record->event_color)
                                            ->formatStateUsing(fn (ActivityLog $record): string => $record->event_label),

                                        Infolists\Components\TextEntry::make('causer_name')
                                            ->label('Performed by')
                                            ->icon('heroicon-o-user'),

                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Time')
                                            ->dateTime('d M Y, H:i:s')
                                            ->since(),
                                    ]),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),
                            ])
                            ->contained(false)
                    ])
                    ->icon('heroicon-o-clock')
                    ->collapsible(),

                Infolists\Components\Section::make('System Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d F Y, H:i:s')
                                    ->icon('heroicon-o-calendar-days'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
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
        return ['name', 'email', 'roles.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Roles' => $record->roles->pluck('name')->join(', '),
        ];
    }
}