<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PoSupplierResource\Pages;
use App\Models\Supplier;
use App\Models\PoSupplier;
use App\Models\User;
use App\Enums\PoStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;

class PoSupplierResource extends Resource
{
    protected static ?string $model = PoSupplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

       protected static ?string $navigationLabel = 'PO Supplier';

    protected static ?string $modelLabel = 'PO Supplier';

    protected static ?string $pluralModelLabel = 'PO Suppliers';

    protected static ?string $navigationGroup = 'Purchase Order';

    protected static ?int $navigationSort = 3;

    // Method untuk menampilkan badge notifikasi di sidebar
    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status_po', PoStatus::PENDING->value)->count();

        return $pendingCount >= 0 ? (string) $pendingCount : null;
    }

    // Method untuk mengatur warna badge
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary'; // Bisa juga 'danger', 'success', 'info', dll
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi PO')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_po')
                            ->label('Nomor PO')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Akan di-generate otomatis'),

                        Forms\Components\Select::make('id_supplier')
                            ->label('Supplier')
                            ->relationship('supplier', 'nama')
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->preload(),

                        Forms\Components\Hidden::make('id_user')
                            ->default(optional(\Illuminate\Support\Facades\Auth::user())->id),

                        Forms\Components\DatePicker::make('tanggal_po')
                            ->label('Tanggal PO')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('jenis_po')
                            ->label('Jenis PO')
                            ->options([
                              'Produk' => 'Produk',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('status_po')
                            ->label('Status PO')
                            ->options(PoStatus::getOptions())
                            ->default(PoStatus::DRAFT->value)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Detail Item')
                    ->schema([
                        Repeater::make('details')
                            ->relationship('details')
                            ->rules(['required', new \App\Rules\ValidPoDetails()])
                            ->schema([
                                Forms\Components\Textarea::make('deskripsi')
                                    ->label('Deskripsi Barang')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah/Qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $jumlah = (float) $get('jumlah') ?? 0;
                                        $harga = (float) $get('harga_satuan') ?? 0;
                                        $set('total', $jumlah * $harga);
                                    }),

                                Forms\Components\TextInput::make('harga_satuan')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $jumlah = (float) $get('jumlah') ?? 0;
                                        $harga = (float) $get('harga_satuan') ?? 0;
                                        $set('total', $jumlah * $harga);
                                    }),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah Item')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['deskripsi'] ?? null)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            })

                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->after(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                            ),
                    ]),

                Section::make('Total & Pajak')
                    ->schema([
                        Forms\Components\TextInput::make('total_sebelum_pajak')
                            ->label('Total Sebelum Pajak')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('total_pajak')
                            ->label('Pajak (11%)')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Placeholder::make('total_keseluruhan')
                            ->label('Total Keseluruhan')
                            ->content(function (Get $get): string {
                                $totalSebelumPajak = (float) $get('total_sebelum_pajak') ?? 0;
                                $totalPajak = (float) $get('total_pajak') ?? 0;
                                return 'Rp ' . number_format($totalSebelumPajak + $totalPajak, 0, ',', '.');
                            }),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.nama')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_po')
                    ->label('Tanggal PO')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis_po')
                    ->label('Jenis PO')
                    ->badge()
                    ->color('info'),

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
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_po')
                    ->label('Status')
                    ->options(PoStatus::getOptions()),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (PoSupplier $record): bool => $record->canBeEdited()),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve PO Supplier')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui PO ini?')
                    ->visible(fn (PoSupplier $record): bool => $record->isPending())
                    ->action(fn (PoSupplier $record) => $record->update(['status_po' => PoStatus::APPROVED->value]))
                    ->after(fn () => \Filament\Notifications\Notification::make()
                        ->title('PO berhasil disetujui')
                        ->success()
                        ->send()),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject PO Supplier')
                    ->modalDescription('Apakah Anda yakin ingin menolak PO ini?')
                    ->visible(fn (PoSupplier $record): bool => $record->isPending())
                    ->action(fn (PoSupplier $record) => $record->update(['status_po' => PoStatus::REJECTED->value]))
                    ->after(fn () => \Filament\Notifications\Notification::make()
                        ->title('PO berhasil ditolak')
                        ->success()
                        ->send()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (PoSupplier $record): bool => $record->canBeDeleted()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPoSuppliers::route('/'),
            'create' => Pages\CreatePoSupplier::route('/create'),
            'view' => Pages\ViewPoSupplier::route('/{record}'),
            'edit' => Pages\EditPoSupplier::route('/{record}/edit'),
        ];
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        $details = $get('details') ?? [];
        $subtotal = 0;

        foreach ($details as $detail) {
            $subtotal += (float) ($detail['total'] ?? 0);
        }

        $pajak = $subtotal * 0.11; // 11% pajak

        $set('total_sebelum_pajak', $subtotal);
        $set('total_pajak', $pajak);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['supplier', 'user']);
    }
}
