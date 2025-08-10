<?php

namespace App\Filament\Widgets;

use App\Models\PoSupplier;
use App\Enums\PoStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPoSupplierWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Supplier Purchase Orders';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PoSupplier::query()
                    ->with(['Supplier', 'user'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('Supplier.nama')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tanggal_po')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis_po')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Produk' => 'success',
                        'Jasa' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status_po')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof \App\Enums\PoStatus) {
                            return $state->getLabel();
                        }
                        return PoStatus::from($state)->getLabel();
                    })
                    ->color(function ($state): string {
                        if ($state instanceof \App\Enums\PoStatus) {
                            return $state->getColor();
                        }
                        return PoStatus::from($state)->getColor();
                    }),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (PoSupplier $record): string => \App\Filament\Resources\PoSupplierResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (PoSupplier $record): string => \App\Filament\Resources\PoSupplierResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (PoSupplier $record): bool => $record->canBeEdited())
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Supplier PO')
                    ->modalDescription('Are you sure you want to approve this PO?')
                    ->visible(fn (PoSupplier $record): bool => $record->isPending())
                    ->action(fn (PoSupplier $record) => $record->update(['status_po' => PoStatus::APPROVED->value]))
                    ->after(fn () => \Filament\Notifications\Notification::make()
                        ->title('PO successfully approved')
                        ->success()
                        ->send()),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated(false);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }
}
