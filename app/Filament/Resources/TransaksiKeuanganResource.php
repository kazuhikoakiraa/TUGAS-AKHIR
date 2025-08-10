<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiKeuanganResource\Pages;
use App\Models\TransaksiKeuangan;
use App\Models\PoSupplier;
use App\Models\Invoice;
use App\Models\RekeningBank;
use App\Enums\PoStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransaksiKeuanganExport;

class TransaksiKeuanganResource extends Resource
{
    protected static ?string $model = TransaksiKeuangan::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Transaksi Keuangan';
    protected static ?string $modelLabel = 'Transaksi Keuangan';
    protected static ?string $pluralModelLabel = 'Transaksi Keuangan';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transaksi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('jenis')
                                    ->label('Jenis Transaksi')
                                    ->options([
                                        'pemasukan' => 'Pemasukan',
                                        'pengeluaran' => 'Pengeluaran',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // Reset related fields when jenis changes
                                        $set('id_po_supplier', null);
                                        $set('invoice_id', null);
                                    }),

                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->required()
                                    ->default(now()),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('id_rekening')
                                    ->label('Rekening Bank')
                                    ->options(RekeningBank::all()->pluck('nama_bank', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(fn (RekeningBank $record): string =>
                                        "{$record->nama_bank} - {$record->nomor_rekening}"),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->mask(999999999999.99),
                            ]),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Referensi Transaksi')
                    ->description('Pilih referensi jika transaksi terkait dengan PO Supplier atau Invoice')
                    ->schema([
                        Forms\Components\Select::make('id_po_supplier')
                            ->label('PO Supplier')
                            ->options(function () {
                                return PoSupplier::with('supplier')
                                    ->where('status_po', PoStatus::APPROVED)
                                    ->get()
                                    ->mapWithKeys(function ($po) {
                                        return [$po->id => $po->nomor_po . ' - ' . $po->supplier->nama . ' (Rp ' . number_format($po->total, 0, ',', '.') . ')'];
                                    });
                            })
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => $get('jenis') === 'pengeluaran')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $po = PoSupplier::find($state);
                                    if ($po) {
                                        $set('jumlah', $po->total);
                                        $set('keterangan', "Pembayaran PO Supplier {$po->nomor_po} - {$po->supplier->nama}");
                                    }
                                }
                            }),

                        Forms\Components\Select::make('invoice_id')
                            ->label('Invoice')
                            ->options(function () {
                                return Invoice::with('poCustomer.customer')
                                    ->where('status', 'paid')
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer tidak ditemukan';
                                        return [$invoice->id => $invoice->nomor_invoice . ' - ' . $customerName . ' (Rp ' . number_format($invoice->grand_total, 0, ',', '.') . ')'];
                                    });
                            })
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => $get('jenis') === 'pemasukan')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $invoice = Invoice::with('poCustomer.customer')->find($state);
                                    if ($invoice) {
                                        $set('jumlah', $invoice->grand_total);
                                        $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer tidak ditemukan';
                                        $set('keterangan', "Pembayaran Invoice {$invoice->nomor_invoice} - {$customerName}");
                                    }
                                }
                            }),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('jenis')
                    ->label('Jenis')
                    ->colors([
                        'success' => 'pemasukan',
                        'danger' => 'pengeluaran',
                    ])
                    ->icons([
                        'heroicon-s-arrow-trending-up' => 'pemasukan',
                        'heroicon-s-arrow-trending-down' => 'pengeluaran',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('rekening.nama_bank')
                    ->label('Bank')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rekening.nomor_rekening')
                    ->label('No. Rekening')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn (string $state, $record): string =>
                        $record->jenis === 'pemasukan' ? 'success' : 'danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('poSupplier.nomor_po')
                    ->label('PO Supplier')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis Transaksi')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

                SelectFilter::make('id_rekening')
                    ->label('Rekening Bank')
                    ->relationship('rekening', 'nama_bank')
                    ->searchable(),

                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_dari')
                            ->label('Dari Tanggal'),
                        DatePicker::make('tanggal_sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tanggal_dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['tanggal_dari'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['tanggal_dari'])->format('d/m/Y');
                        }
                        if ($data['tanggal_sampai'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['tanggal_sampai'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Filter::make('periode')
                    ->form([
                        Forms\Components\Select::make('periode')
                            ->label('Periode')
                            ->options([
                                'minggu_ini' => 'Minggu Ini',
                                'bulan_ini' => 'Bulan Ini',
                                'tahun_ini' => 'Tahun Ini',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['periode'] ?? null) {
                            'minggu_ini' => $query->mingguIni(),
                            'bulan_ini' => $query->bulanIni(),
                            'tahun_ini' => $query->tahunIni(),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return match ($data['periode'] ?? null) {
                            'minggu_ini' => 'Periode: Minggu Ini',
                            'bulan_ini' => 'Periode: Bulan Ini',
                            'tahun_ini' => 'Periode: Tahun Ini',
                            default => null,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    BulkAction::make('export_excel')
                        ->label('Export ke Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $fileName = 'transaksi-keuangan-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

                            Notification::make()
                                ->title('Export berhasil')
                                ->body("File {$fileName} sedang diunduh")
                                ->success()
                                ->send();

                            return Excel::download(new TransaksiKeuanganExport($records), $fileName);
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_all')
                    ->label('Export Semua')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $fileName = 'semua-transaksi-keuangan-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

                        Notification::make()
                            ->title('Export berhasil')
                            ->body("File {$fileName} sedang diunduh")
                            ->success()
                            ->send();

                        return Excel::download(new TransaksiKeuanganExport(), $fileName);
                    }),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('60s'); // Auto refresh every 60 seconds
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiKeuangan::route('/'),
            'create' => Pages\CreateTransaksiKeuangan::route('/create'),
            'edit' => Pages\EditTransaksiKeuangan::route('/{record}/edit'),
            'view' => Pages\ViewTransaksiKeuangan::route('/{record}'),
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
