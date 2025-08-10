<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUsersWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Users';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'staff' => 'info',
                        'user' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrator',
                        'manager' => 'Manager',
                        'staff' => 'Staff',
                        'user' => 'User',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state): string => $state ? 'Verified' : 'Not Verified'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->description(fn (User $record): string => $record->created_at->diffForHumans()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn (User $record): string => route('filament.admin.resources.users.view', $record)),
            ])
            ->paginated(false);
    }
}
