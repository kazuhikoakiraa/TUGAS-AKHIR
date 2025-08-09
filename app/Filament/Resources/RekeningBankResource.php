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

    protected static ?string $navigationLabel = 'Rekening Bank';

    protected static ?string $modelLabel = 'Rekening Bank';

    protected static ?string $pluralModelLabel = 'Rekening Bank';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Rekening Bank')
                    ->description('Masukkan data lengkap rekening bank')
                    ->schema([
                        Forms\Components\Select::make('nama_bank')
                            ->label('Nama Bank')
                            ->required()
                            ->searchable()
                            ->options(BankHelper::getBankOptions())
                            ->placeholder('Pilih nama bank')
                            ->prefixIcon('heroicon-o-building-library')
                            ->helperText('Pilih bank resmi yang terdaftar di Indonesia')
                            ->native(false),

                        Forms\Components\TextInput::make('nomor_rekening')
                            ->label('Nomor Rekening')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nomor rekening')
                            ->prefixIcon('heroicon-o-credit-card')
                            ->helperText('Nomor rekening bank tanpa spasi atau tanda baca')
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('nama_pemilik')
                            ->label('Nama Pemilik')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama pemilik rekening')
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('Nama pemilik sesuai dengan rekening bank'),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Masukkan keterangan tambahan (opsional)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Informasi tambahan tentang rekening bank'),
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
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-library')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => BankHelper::getShortBankName($state))
                    ->wrap(),

                Tables\Columns\TextColumn::make('kode_bank')
                    ->label('Kode Bank')
                    ->state(fn (RekeningBank $record): ?string => BankHelper::getBankCode($record->nama_bank))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nomor_rekening')
                    ->label('Nomor Rekening')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-credit-card')
                    ->copyable()
                    ->copyMessage('Nomor rekening berhasil disalin')
                    ->copyMessageDuration(1500),


                Tables\Columns\TextColumn::make('nama_pemilik')
                    ->label('Nama Pemilik')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->description(fn (RekeningBank $record): ?string => $record->keterangan ? substr($record->keterangan, 0, 50) . '...' : null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('transaksi_keuangan_count')
                    ->label('Total Transaksi')
                    ->counts('transaksiKeuangan')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-arrow-path'),

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
                Tables\Filters\SelectFilter::make('nama_bank')
                    ->label('Filter Bank')
                    ->searchable()
                     ->options(BankHelper::getPopularBankOptions())
                    ->multiple(),

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

                Tables\Filters\Filter::make('has_transactions')
                    ->label('Memiliki Transaksi')
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
                    ->modalHeading('Hapus Rekening Bank')
                    ->modalDescription('Apakah Anda yakin ingin menghapus rekening bank ini? Data yang sudah dihapus tidak dapat dikembalikan.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Rekening Bank Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua rekening bank yang dipilih? Data yang sudah dihapus tidak dapat dikembalikan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->emptyStateHeading('Belum ada data rekening bank')
            ->emptyStateDescription('Hasil pencarian tidak ditemukan.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Rekening Bank')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('nama_bank')
                                    ->label('Nama Bank')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-o-building-library')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('nomor_rekening')
                                    ->label('Nomor Rekening')
                                    ->icon('heroicon-o-credit-card')
                                    ->copyable()
                                    ->copyMessage('Nomor rekening berhasil disalin')
                                    ->formatStateUsing(fn (string $state): string => chunk_split($state, 4, ' ')),

                                Infolists\Components\TextEntry::make('nama_pemilik')
                                    ->label('Nama Pemilik')
                                    ->icon('heroicon-o-user')
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('transaksi_keuangan_count')
                                    ->label('Total Transaksi')
                                    ->icon('heroicon-o-arrow-path')
                                    ->state(fn (RekeningBank $record): int => $record->transaksiKeuangan()->count())
                                    ->badge()
                                    ->color('info'),
                            ]),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->icon('heroicon-o-information-circle')
                            ->placeholder('Tidak ada keterangan')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-information-circle'),

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
            'Nomor Rekening' => $record->nomor_rekening,
            'Pemilik' => $record->nama_pemilik,
        ];
    }
}
