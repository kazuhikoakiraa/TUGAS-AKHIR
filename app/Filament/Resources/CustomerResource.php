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

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Customer')
                    ->description('Masukkan informasi lengkap customer')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Customer')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama customer')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->placeholder('Masukkan alamat lengkap customer')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->required()
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('Contoh: 08123456789')
                            ->prefixIcon('heroicon-m-phone'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->unique(Customer::class, 'email', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('customer@example.com')
                            ->prefixIcon('heroicon-m-envelope'),
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
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),

                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
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
                    ->label('Telepon')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Nomor telepon berhasil disalin')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('Email berhasil disalin')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('po_customers_count')
                    ->label('Total PO')
                    ->counts('poCustomers')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('penawaran_count')
                    ->label('Total Penawaran')
                    ->counts('penawaran')
                    ->sortable()
                    ->badge()
                    ->color('success'),

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
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat dari'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat sampai'),
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
                    ->label('Memiliki Purchase Order')
                    ->query(fn (Builder $query): Builder => $query->has('poCustomers'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Customer')
                    ->modalDescription('Apakah Anda yakin ingin menghapus customer ini? Data yang sudah dihapus tidak dapat dikembalikan.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Customer Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua customer yang dipilih? Data yang sudah dihapus tidak dapat dikembalikan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua'),
                ]),
            ])
            ->emptyStateHeading('Belum ada customer')
            ->emptyStateDescription('Hasil pencarian tidak ditemukan.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama')
                            ->label('Nama Customer')
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Alamat')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('telepon')
                            ->label('Nomor Telepon')
                            ->icon('heroicon-m-phone')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Statistik')
                    ->schema([
                        Infolists\Components\TextEntry::make('po_customers_count')
                            ->label('Total Purchase Order')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('penawaran_count')
                            ->label('Total Penawaran')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Pendaftaran')
                            ->dateTime('d F Y, H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
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
