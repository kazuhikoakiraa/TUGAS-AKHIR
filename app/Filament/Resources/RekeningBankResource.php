<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekeningBankResource\Pages;
use App\Models\RekeningBank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Helpers\BankHelper;

class RekeningBankResource extends Resource
{
    protected static ?string $model = RekeningBank::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Bank Account';

    protected static ?string $modelLabel = 'Bank Account';

    protected static ?string $pluralModelLabel = 'Bank Accounts';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bank Account Information')
                    ->description('Enter complete bank account data')
                    ->schema([
                        Forms\Components\Select::make('nama_bank')
                            ->label('Bank Name')
                            ->required()
                            ->searchable()
                            ->options(BankHelper::getBankOptions())
                            ->placeholder('Select bank name')
                            ->prefixIcon('heroicon-o-building-library')
                            ->helperText('Select official bank registered in Indonesia')
                            ->native(false),

                        Forms\Components\TextInput::make('nomor_rekening')
                            ->label('Account Number')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter account number')
                            ->prefixIcon('heroicon-o-credit-card')
                            ->helperText('Bank account number without spaces or punctuation')
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('nama_pemilik')
                            ->label('Account Holder Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter account holder name')
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('Account holder name as per bank account'),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Notes')
                            ->placeholder('Enter additional notes (optional)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Additional information about the bank account'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_bank')
                    ->label('Bank Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-library')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => BankHelper::getShortBankName($state))
                    ->wrap(),

                Tables\Columns\TextColumn::make('kode_bank')
                    ->label('Bank Code')
                    ->state(fn (RekeningBank $record): ?string => BankHelper::getBankCode($record->nama_bank))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nomor_rekening')
                    ->label('Account Number')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-credit-card')
                    ->copyable()
                    ->copyMessage('Account number successfully copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('nama_pemilik')
                    ->label('Account Holder')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->description(fn (RekeningBank $record): ?string => $record->keterangan ? substr($record->keterangan, 0, 50) . '...' : null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('transaksi_keuangan_count')
                    ->label('Total Transactions')
                    ->counts('transaksiKeuangan')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-arrow-path'),

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
                Tables\Filters\SelectFilter::make('nama_bank')
                    ->label('Filter Bank')
                    ->searchable()
                    ->options(BankHelper::getPopularBankOptions())
                    ->multiple(),

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

                Tables\Filters\Filter::make('has_transactions')
                    ->label('Has Transactions')
                    ->query(fn (Builder $query): Builder => $query->has('transaksiKeuangan'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Bank Account')
                    ->modalDescription('Are you sure you want to delete this bank account? Deleted data cannot be recovered.')
                    ->modalSubmitActionLabel('Yes, Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Bank Accounts')
                        ->modalDescription('Are you sure you want to delete all selected bank accounts? Deleted data cannot be recovered.')
                        ->modalSubmitActionLabel('Yes, Delete All'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->emptyStateHeading('No bank account data yet')
            ->emptyStateDescription('No search results found.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Bank Account Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('nama_bank')
                                    ->label('Bank Name')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-o-building-library')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('nomor_rekening')
                                    ->label('Account Number')
                                    ->icon('heroicon-o-credit-card')
                                    ->copyable()
                                    ->copyMessage('Account number successfully copied')
                                    ->formatStateUsing(fn (string $state): string => chunk_split($state, 4, ' ')),

                                Infolists\Components\TextEntry::make('nama_pemilik')
                                    ->label('Account Holder')
                                    ->icon('heroicon-o-user')
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('transaksi_keuangan_count')
                                    ->label('Total Transactions')
                                    ->icon('heroicon-o-arrow-path')
                                    ->state(fn (RekeningBank $record): int => $record->transaksiKeuangan()->count())
                                    ->badge()
                                    ->color('info'),
                            ]),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Notes')
                            ->icon('heroicon-o-information-circle')
                            ->placeholder('No notes')
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
            'index' => Pages\ListRekeningBank::route('/'),
            'create' => Pages\CreateRekeningBank::route('/create'),
            'view' => Pages\ViewRekeningBank::route('/{record}'),
            'edit' => Pages\EditRekeningBank::route('/{record}/edit'),
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
        return ['nama_bank', 'nomor_rekening', 'nama_pemilik'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Bank' => $record->nama_bank,
            'Account Number' => $record->nomor_rekening,
            'Account Holder' => $record->nama_pemilik,
        ];
    }
}
