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
    protected static ?string $navigationLabel = 'Financial Transactions';
    protected static ?string $modelLabel = 'Financial Transaction';
    protected static ?string $pluralModelLabel = 'Financial Transactions';
    protected static ?string $navigationGroup = 'Transactions';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Transaction Information')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('jenis')
                                ->label('Transaction Type')
                                ->options([
                                    'pemasukan' => 'Income',
                                    'pengeluaran' => 'Expense',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Reset related fields when jenis changes
                                    $set('id_po_supplier', null);
                                    $set('referensi_type', null);
                                    $set('referensi_id', null);
                                }),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Transaction Date')
                                ->required()
                                ->default(now()),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('id_rekening')
                                ->label('Bank Account')
                                ->options(function () {
                                    $banks = RekeningBank::all();
                                    return $banks->pluck('nama_bank', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (RekeningBank $record): string =>
                                    "{$record->nama_bank} - {$record->nomor_rekening}"),

                            Forms\Components\TextInput::make('jumlah')
                                ->label('Amount')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->mask(999999999999.99),
                        ]),

                    Forms\Components\Textarea::make('keterangan')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Transaction Reference')
                ->description('Select reference if transaction is related to Supplier PO or Invoice')
                ->schema([
                    // PO Supplier untuk expense
                    Forms\Components\Select::make('id_po_supplier')
                        ->label('Supplier PO')
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
                                    $set('keterangan', "Payment for Supplier PO {$po->nomor_po} - {$po->supplier->nama}");
                                    $set('referensi_type', 'po_supplier');
                                    $set('referensi_id', $po->id);
                                }
                            }
                        }),

                    // Invoice untuk income
                    Forms\Components\Select::make('invoice_reference')
                        ->label('Invoice')
                        ->options(function () {
                            return Invoice::with('poCustomer.customer')
                                ->where('status', 'paid')
                                ->get()
                                ->mapWithKeys(function ($invoice) {
                                    $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer not found';
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
                                    $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer not found';
                                    $set('keterangan', "Payment for Invoice {$invoice->nomor_invoice} - {$customerName}");
                                    $set('referensi_type', 'invoice');
                                    $set('referensi_id', $invoice->id);
                                }
                            }
                        }),

                    // Hidden fields untuk referensi system
                    Forms\Components\Hidden::make('referensi_type'),
                    Forms\Components\Hidden::make('referensi_id'),
                ])
                ->collapsed(),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('jenis')
                    ->label('Type')
                    ->colors([
                        'success' => 'pemasukan',
                        'danger' => 'pengeluaran',
                    ])
                    ->icons([
                        'heroicon-s-arrow-trending-up' => 'pemasukan',
                        'heroicon-s-arrow-trending-down' => 'pengeluaran',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pemasukan' => 'Income',
                        'pengeluaran' => 'Expense',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rekening.nama_bank')
                    ->label('Bank')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rekening.nomor_rekening')
                    ->label('Account No.')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn (string $state, $record): string =>
                        $record->jenis === 'pemasukan' ? 'success' : 'danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('poSupplier.nomor_po')
    ->label('Supplier PO')
    ->searchable()
    ->toggleable()
    ->placeholder('—'),

    Tables\Columns\TextColumn::make('invoice_info')
    ->label('Invoice')
    ->searchable()
    ->toggleable()
    ->placeholder('—')
    ->formatStateUsing(function ($record) {
        if ($record->referensi_type === 'invoice' && $record->referensi_id) {
            $invoice = \App\Models\Invoice::find($record->referensi_id);
            if ($invoice) {
                return $invoice->nomor_invoice;
            }
        }
        return '—';
    }),
    Tables\Columns\BadgeColumn::make('referensi_type')
    ->label('Reference Type')
    ->colors([
        'primary' => 'po_supplier',
        'success' => 'invoice',
        'gray' => 'manual',
    ])
    ->formatStateUsing(fn (string $state): string => match ($state) {
        'po_supplier' => 'PO Supplier',
        'invoice' => 'Invoice',
        'manual' => 'Manual',
        default => 'Unknown',
    })
    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Description')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Transaction Type')
                    ->options([
                        'pemasukan' => 'Income',
                        'pengeluaran' => 'Expense',
                    ]),

                SelectFilter::make('id_rekening')
                    ->label('Bank Account')
                    ->relationship('rekening', 'nama_bank')
                    ->searchable(),

                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_dari')
                            ->label('From Date'),
                        DatePicker::make('tanggal_sampai')
                            ->label('To Date'),
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
                            $indicators[] = 'From: ' . \Carbon\Carbon::parse($data['tanggal_dari'])->format('d/m/Y');
                        }
                        if ($data['tanggal_sampai'] ?? null) {
                            $indicators[] = 'To: ' . \Carbon\Carbon::parse($data['tanggal_sampai'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Filter::make('periode')
                    ->form([
                        Forms\Components\Select::make('periode')
                            ->label('Period')
                            ->options([
                                'minggu_ini' => 'This Week',
                                'bulan_ini' => 'This Month',
                                'tahun_ini' => 'This Year',
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
                            'minggu_ini' => 'Period: This Week',
                            'bulan_ini' => 'Period: This Month',
                            'tahun_ini' => 'Period: This Year',
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
    ->label('Export Selected to Excel')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->form([
        Forms\Components\Section::make('Export Options')
            ->schema([
                Forms\Components\Select::make('sort_by')
                    ->label('Sort By')
                    ->options([
                        'tanggal_asc' => 'Date (Oldest First)',
                        'tanggal_desc' => 'Date (Newest First)',
                        'jumlah_asc' => 'Amount (Smallest First)',
                        'jumlah_desc' => 'Amount (Largest First)',
                    ])
                    ->default('tanggal_asc'),
            ])
    ])
    ->action(function (Collection $records, array $data) {
        // Sort records based on selection
        switch ($data['sort_by']) {
            case 'tanggal_desc':
                $records = $records->sortByDesc('tanggal');
                break;
            case 'jumlah_asc':
                $records = $records->sortBy('jumlah');
                break;
            case 'jumlah_desc':
                $records = $records->sortByDesc('jumlah');
                break;
            default:
                $records = $records->sortBy('tanggal');
        }

        $fileName = 'financial-transactions-selected-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        Notification::make()
            ->title('Export successful')
            ->body("File {$fileName} is being downloaded with " . $records->count() . " transactions")
            ->success()
            ->send();

        // Always include summary and use professional format
        $exportOptions = [
            'include_summary' => true,
            'professional_format' => true,
            'sort_by' => $data['sort_by']
        ];

        return Excel::download(
            new TransaksiKeuanganExport($records, $exportOptions),
            $fileName
        );
    }),
    ]),
])
            ->headerActions([
    Tables\Actions\Action::make('export_all')
    ->label('Export All')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->form([
        Forms\Components\Section::make('Export Configuration')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->helperText('Leave empty to include all dates'),

                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->helperText('Leave empty to include all dates'),
                    ]),

                Forms\Components\Select::make('account_id')
                    ->label('Bank Account')
                    ->options(RekeningBank::all()->pluck('nama_bank', 'id'))
                    ->searchable()
                    ->placeholder('All Accounts')
                    ->helperText('Filter by specific bank account'),

                Forms\Components\Select::make('transaction_type')
                    ->label('Transaction Type')
                    ->options([
                        'all' => 'All Transactions',
                        'pemasukan' => 'Income Only',
                        'pengeluaran' => 'Expense Only',
                    ])
                    ->default('all'),
            ]),
    ])
    ->action(function (array $data) {
        // Build query based on filters
        $query = TransaksiKeuangan::with(['rekening', 'poSupplier.supplier'])
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc');

        // Apply date filters
        if (!empty($data['date_from'])) {
            $query->whereDate('tanggal', '>=', $data['date_from']);
        }

        if (!empty($data['date_to'])) {
            $query->whereDate('tanggal', '<=', $data['date_to']);
        }

        // Apply account filter
        if (!empty($data['account_id'])) {
            $query->where('id_rekening', $data['account_id']);
        }

        // Apply transaction type filter
        if ($data['transaction_type'] !== 'all') {
            $query->where('jenis', $data['transaction_type']);
        }

        $records = $query->get();

        // Generate filename with filters info
        $filenameParts = ['financial-transactions'];

        if (!empty($data['date_from']) || !empty($data['date_to'])) {
            $from = $data['date_from'] ? \Carbon\Carbon::parse($data['date_from'])->format('Y-m-d') : 'start';
            $to = $data['date_to'] ? \Carbon\Carbon::parse($data['date_to'])->format('Y-m-d') : 'end';
            $filenameParts[] = "from-{$from}-to-{$to}";
        }

        if (!empty($data['account_id'])) {
            $account = RekeningBank::find($data['account_id']);
            if ($account) {
                $filenameParts[] = 'account-' . str_replace(' ', '-', strtolower($account->nama_bank));
            }
        }

        if ($data['transaction_type'] !== 'all') {
            $filenameParts[] = $data['transaction_type'];
        }

        $filenameParts[] = now()->format('Y-m-d-H-i-s');
        $fileName = implode('-', $filenameParts) . '.xlsx';

        Notification::make()
            ->title('Export successful')
            ->body("File {$fileName} is being downloaded with {$records->count()} transactions")
            ->success()
            ->duration(5000)
            ->send();

        // Always include summary and use professional format
        $exportOptions = array_merge($data, [
            'include_summary' => true,
            'professional_format' => true
        ]);

        return Excel::download(new TransaksiKeuanganExport($records, $exportOptions), $fileName);
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
