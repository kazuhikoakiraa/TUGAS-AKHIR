<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->description('Enter complete customer information')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter customer name')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Address')
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter complete customer address')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('telepon')
                            ->label('Phone Number')
                            ->required()
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('Example: 08123456789')
                            ->prefixIcon('heroicon-m-phone'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->unique(Customer::class, 'email', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('customer@example.com')
                            ->prefixIcon('heroicon-m-envelope'),

                        Forms\Components\TextInput::make('npwp')
                            ->label('NPWP')
                            ->placeholder('XX.XXX.XXX.X-XXX.XXX or 15 digits')
                            ->maxLength(20)
                            ->prefixIcon('heroicon-m-identification')
                            ->helperText('Enter 15 digits NPWP number (optional)')
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (!empty($value)) {
                                            $cleanNpwp = preg_replace('/[^0-9]/', '', $value);

                                            if (strlen($cleanNpwp) !== 15) {
                                                $fail('NPWP must be exactly 15 digits.');
                                                return;
                                            }

                                            if (!Customer::validateNpwp($value)) {
                                                $fail('Invalid NPWP format.');
                                            }
                                        }
                                    };
                                }
                            ])
                            ->unique(Customer::class, 'npwp', ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                return $rule->whereNotNull('npwp')->where('npwp', '!=', '');
                            })
                            ->dehydrateStateUsing(function ($state) {
                                return $state ? preg_replace('/[^0-9]/', '', $state) : null;
                            })
                            ->formatStateUsing(function ($state) {
                                if (!$state || strlen($state) !== 15) {
                                    return $state;
                                }

                                return substr($state, 0, 2) . '.' .
                                       substr($state, 2, 3) . '.' .
                                       substr($state, 5, 3) . '.' .
                                       substr($state, 8, 1) . '-' .
                                       substr($state, 9, 3) . '.' .
                                       substr($state, 12, 3);
                            })
                            ->columnSpanFull(),
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
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
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
                    }),

                Tables\Columns\TextColumn::make('telepon')
                    ->label('Phone')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Phone number copied successfully')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('Email copied successfully')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-identification')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '-';
                        }

                        $cleanNpwp = preg_replace('/[^0-9]/', '', $state);

                        if (strlen($cleanNpwp) === 15) {
                            return substr($cleanNpwp, 0, 2) . '.' .
                                   substr($cleanNpwp, 2, 3) . '.' .
                                   substr($cleanNpwp, 5, 3) . '.' .
                                   substr($cleanNpwp, 8, 1) . '-' .
                                   substr($cleanNpwp, 9, 3) . '.' .
                                   substr($cleanNpwp, 12, 3);
                        }

                        return $cleanNpwp;
                    })
                    ->copyable()
                    ->copyMessage('NPWP copied successfully')
                    ->copyMessageDuration(1500)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('po_customers_count')
                    ->label('Total PO')
                    ->counts('poCustomers')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('penawaran_count')
                    ->label('Total Quotations')
                    ->counts('penawaran')
                    ->sortable()
                    ->badge()
                    ->color('success'),

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
                            ->label('Created from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
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
                    }),
                Tables\Filters\Filter::make('has_orders')
                    ->label('Has Purchase Orders')
                    ->query(fn (Builder $query): Builder => $query->has('poCustomers'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_npwp')
                    ->label('Has NPWP')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('npwp')->where('npwp', '!=', ''))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Customer')
                    ->modalDescription('Are you sure you want to delete this customer? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Customers')
                        ->modalDescription('Are you sure you want to delete all selected customers? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete All'),
                ]),
            ])
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Search results not found.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama')
                            ->label('Customer Name')
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Address')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('telepon')
                            ->label('Phone Number')
                            ->icon('heroicon-m-phone')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('npwp')
                            ->label('NPWP')
                            ->icon('heroicon-m-identification')
                            ->formatStateUsing(function ($state) {
                                if (!$state) {
                                    return '-';
                                }

                                $cleanNpwp = preg_replace('/[^0-9]/', '', $state);

                                if (strlen($cleanNpwp) === 15) {
                                    return substr($cleanNpwp, 0, 2) . '.' .
                                           substr($cleanNpwp, 2, 3) . '.' .
                                           substr($cleanNpwp, 5, 3) . '.' .
                                           substr($cleanNpwp, 8, 1) . '-' .
                                           substr($cleanNpwp, 9, 3) . '.' .
                                           substr($cleanNpwp, 12, 3);
                                }

                                return $cleanNpwp;
                            })
                            ->copyable()
                            ->placeholder('No NPWP'),

                        Infolists\Components\TextEntry::make('')
                            ->label('')
                            ->state('')
                            ->visible(false),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('po_customers_count')
                            ->label('Total Purchase Orders')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('penawaran_count')
                            ->label('Total Quotations')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Registration Date')
                            ->dateTime('d F Y, H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('d F Y, H:i'),
                    ])
                    ->columns(2),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
