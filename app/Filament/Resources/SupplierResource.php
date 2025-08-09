<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
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

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Supplier';

    protected static ?string $modelLabel = 'Supplier';

    protected static ?string $pluralModelLabel = 'Suppliers';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supplier Information')
                    ->description('Enter complete supplier data')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Supplier Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter supplier name')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Address')
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter complete supplier address')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('telepon')
                            ->label('Phone')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('Example: 021-12345678')
                            ->prefixIcon('heroicon-o-phone')
                            ->helperText('Format: 021-12345678 or 081234567890'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('supplier@example.com')
                            ->prefixIcon('heroicon-o-envelope')
                            ->helperText('Email will be used for communication'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Supplier Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Supplier $record): string => $record->email)
                    ->wrap(),

                Tables\Columns\TextColumn::make('alamat')
                    ->label('Address')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('telepon')
                    ->label('Phone')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->copyMessage('Phone number successfully copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email successfully copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('po_suppliers_count')
                    ->label('Total PO')
                    ->counts('poSuppliers')
                    ->badge()
                    ->color('success')
                    ->sortable(),

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

                Tables\Filters\Filter::make('has_orders')
                    ->label('Has Purchase Orders')
                    ->query(fn (Builder $query): Builder => $query->has('poSuppliers'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Supplier')
                    ->modalDescription('Are you sure you want to delete this supplier? Deleted data cannot be recovered.')
                    ->modalSubmitActionLabel('Yes, Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Suppliers')
                        ->modalDescription('Are you sure you want to delete all selected suppliers? Deleted data cannot be recovered.')
                        ->modalSubmitActionLabel('Yes, Delete All'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('No supplier data yet')
            ->emptyStateDescription('No search results found.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Supplier Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('nama')
                                    ->label('Supplier Name')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-o-building-office'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email successfully copied'),

                                Infolists\Components\TextEntry::make('telepon')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->copyMessage('Phone number successfully copied'),

                                Infolists\Components\TextEntry::make('po_suppliers_count')
                                    ->label('Total Purchase Orders')
                                    ->badge()
                                    ->color('success')
                                    ->getStateUsing(fn (Supplier $record): int => $record->poSuppliers()->count()),
                            ]),

                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Address')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-information-circle'),

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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['poSuppliers']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'email', 'telepon', 'alamat'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Phone' => $record->telepon,
        ];
    }
}
