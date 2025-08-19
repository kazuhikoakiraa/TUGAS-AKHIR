<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenawaranResource\Pages;
use App\Models\Penawaran;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Mail;

class PenawaranResource extends Resource
{
    protected static ?string $model = Penawaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Quotations';

    protected static ?string $modelLabel = 'Quotation';

    protected static ?string $pluralModelLabel = 'Quotations';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Quotation Information')
                    ->description('Create a new quotation for customer')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('nomor_penawaran')
                                    ->label('Quotation Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated on save')
                                    ->prefixIcon('heroicon-m-hashtag'),

                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Quotation Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-calendar-days'),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'accepted' => 'Accepted',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-flag'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('id_customer')
                                    ->label('Customer')
                                    ->relationship('customer', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->prefixIcon('heroicon-m-user')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nama')
                                            ->label('Customer Name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('alamat')
                                            ->label('Address')
                                            ->required()
                                            ->rows(3),
                                        Forms\Components\TextInput::make('telepon')
                                            ->label('Phone Number')
                                            ->required()
                                            ->tel(),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->required()
                                            ->email(),
                                    ]),

                                Forms\Components\Select::make('id_user')
                                    ->label('Sales Person')
                                    ->relationship('user', 'name')
                                    ->default(\Illuminate\Support\Facades\Auth::user()?->id)
                                    ->required()
                                    ->searchable()
                                    ->prefixIcon('heroicon-m-user-circle'),
                            ]),

                        Forms\Components\TextInput::make('tax_rate')
                            ->label('Tax Rate (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(11.00)
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            }),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Product Details')
                    ->description('Add products to this quotation')
                    ->schema([
                        Repeater::make('details')
                            ->relationship('details')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('📦 Select Product')
                                    ->options(function () {
                                        return Product::active()
                                            ->get()
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('nama_produk', $product->name);
                                                $set('harga_satuan', $product->unit_price);
                                                $set('satuan', $product->unit);
                                                $set('deskripsi', $product->description ?? '');

                                                // Calculate total with current quantity
                                                $qty = (float) ($get('jumlah') ?? 1);
                                                $set('total', $qty * $product->unit_price);
                                            }
                                        } else {
                                            $set('nama_produk', '');
                                            $set('harga_satuan', 0);
                                            $set('satuan', 'pcs');
                                            $set('deskripsi', '');
                                            $set('total', 0);
                                        }

                                        // Update form totals
                                        self::updateTotalsFromRepeater($get, $set);
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->step(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $jumlah = (float) ($state ?? 1);
                                                $harga = (float) ($get('harga_satuan') ?? 0);
                                                $total = $jumlah * $harga;
                                                $set('total', $total);

                                                // Update form totals
                                                self::updateTotalsFromRepeater($get, $set);
                                            })
                                            ->required(),

                                        Forms\Components\TextInput::make('satuan')
                                            ->label('Unit')
                                            ->placeholder('e.g., pcs, kg, m²')
                                            ->readOnly()
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('harga_satuan')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->step(0.01)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $harga = (float) ($state ?? 0);
                                                $jumlah = (float) ($get('jumlah') ?? 1);
                                                $total = $jumlah * $harga;
                                                $set('total', $total);

                                                // Update form totals
                                                self::updateTotalsFromRepeater($get, $set);
                                            })
                                            ->readOnly(fn (Get $get) => !empty($get('product_id')))
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('total')
                                            ->label('💰 Total')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->readOnly()
                                            ->dehydrated()
                                            ->extraAttributes(['class' => 'font-bold text-green-600']),
                                    ]),

                                Forms\Components\Textarea::make('deskripsi')
                                    ->label('Description')
                                    ->rows(2)
                                    ->placeholder('Product description will be filled automatically')
                                    ->readOnly(fn (Get $get) => !empty($get('product_id')))
                                    ->dehydrated()
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('keterangan')
                                    ->label('Additional Notes')
                                    ->rows(2)
                                    ->placeholder('Add any additional notes')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->addActionLabel('📦 Add Product')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(function (array $state): ?string {
                                $nama = $state['nama_produk'] ?? 'Unnamed Product';
                                $qty = $state['jumlah'] ?? 1;
                                $unit = $state['satuan'] ?? 'pcs';
                                return "📦 {$nama} ({$qty} {$unit})";
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            })
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->after(
                                    fn (Get $get, Set $set) => self::updateTotals($get, $set)
                                )
                            ),
                    ]),

                Section::make('💰 Total & Tax Calculation')
                    ->schema([
                        Forms\Components\TextInput::make('total_sebelum_pajak')
                            ->label('Subtotal (Before Tax)')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->extraAttributes(['class' => 'font-semibold']),

                        Forms\Components\TextInput::make('total_pajak')
                            ->label('Tax Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->extraAttributes(['class' => 'font-semibold']),

                        Forms\Components\Placeholder::make('total_keseluruhan')
                            ->label('🏆 Grand Total')
                            ->content(function (Get $get): string {
                                $totalSebelumPajak = (float) ($get('total_sebelum_pajak') ?? 0);
                                $totalPajak = (float) ($get('total_pajak') ?? 0);
                                $grandTotal = $totalSebelumPajak + $totalPajak;
                                return 'Rp ' . number_format($grandTotal, 0, ',', '.');
                            })
                            ->extraAttributes(['class' => 'text-xl font-bold text-green-600']),
                    ])
                    ->columns(3),

                Section::make('Terms & Conditions')
                    ->description('Add terms and conditions for this quotation')
                    ->schema([
                        Forms\Components\Textarea::make('terms_conditions')
                            ->label('Terms & Conditions')
                            ->rows(6)
                            ->placeholder('Enter terms and conditions...')
                            ->default("1. Harga belum termasuk PPN 11%\n2. Metode Pembayaran : Cash / Tunai\n3. Delivery Time : 4 – 7 hari setelah pembayaran\n4. Quotation valid for 30 days")
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    // Helper method untuk update totals dari dalam repeater
    protected static function updateTotalsFromRepeater(Get $get, Set $set): void
    {
        // Get all details from the parent context
        $allDetails = $get('../../details') ?? [];
        $taxRate = (float) ($get('../../tax_rate') ?? 11) / 100;

        // Calculate subtotal
        $subtotal = 0;
        foreach ($allDetails as $detail) {
            $subtotal += (float) ($detail['total'] ?? 0);
        }

        $pajak = $subtotal * $taxRate;
        $grandTotal = $subtotal + $pajak;

        $set('../../total_sebelum_pajak', $subtotal);
        $set('../../total_pajak', $pajak);
        $set('../../harga', $grandTotal);
    }

    // Helper method utama untuk update totals
    protected static function updateTotals(Get|array $get, Set $set): void
    {
        if (is_array($get)) {
            // Handle empty array case
            $details = [];
            $taxRate = 11;
        } else {
            $details = $get('details') ?? [];
            $taxRate = (float) ($get('tax_rate') ?? 11);
        }

        $subtotal = 0;

        foreach ($details as $detail) {
            $subtotal += (float) ($detail['total'] ?? 0);
        }

        $taxRate = $taxRate / 100; // Convert ke desimal
        $pajak = $subtotal * $taxRate;
        $grandTotal = $subtotal + $pajak;

        $set('total_sebelum_pajak', $subtotal);
        $set('total_pajak', $pajak);
        $set('harga', $grandTotal);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_penawaran')
                    ->label('Quotation Number')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->copyable()
                    ->copyMessage('Quotation number copied successfully')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar-days'),

                Tables\Columns\TextColumn::make('harga')
                    ->label('Price')
                    ->money('IDR')
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'sent',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-paper-airplane' => 'sent',
                        'heroicon-o-check-circle' => 'accepted',
                        'heroicon-o-x-circle' => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Sales Person')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple()
                    ->placeholder('Filter by status'),

                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'nama')
                    ->searchable()
                    ->preload()
                    ->placeholder('Filter by customer'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until')
                            ->native(false),
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
                    ->columns(2),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->nomor_penawaran = null; // Will be auto-generated
                            $newRecord->status = 'draft';
                            $newRecord->save();

                            // Duplicate details
                            foreach ($record->details as $detail) {
                                $newDetail = $detail->replicate();
                                $newDetail->penawaran_id = $newRecord->id;
                                $newDetail->save();
                            }

                            return redirect(static::getUrl('edit', ['record' => $newRecord]));
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Quotation')
                        ->modalDescription('Are you sure you want to duplicate this quotation?'),
                    Tables\Actions\Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn ($record) => route('quotation.pdf', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No quotations yet')
            ->emptyStateDescription('Start by creating your first quotation.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Quotation Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('nomor_penawaran')
                                    ->label('Quotation Number')
                                    ->weight(FontWeight::SemiBold)
                                    ->copyable()
                                    ->icon('heroicon-m-hashtag'),

                                Infolists\Components\TextEntry::make('tanggal')
                                    ->label('Date')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'sent' => 'warning',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                    }),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Sales Person')
                                    ->icon('heroicon-m-user-circle'),

                                Infolists\Components\TextEntry::make('tax_rate')
                                    ->label('Tax Rate')
                                    ->suffix('%')
                                    ->icon('heroicon-m-calculator'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.nama')
                                    ->label('Customer Name')
                                    ->weight(FontWeight::Medium)
                                    ->icon('heroicon-m-user'),

                                Infolists\Components\TextEntry::make('customer.email')
                                    ->label('Email')
                                    ->copyable()
                                    ->icon('heroicon-m-envelope'),

                                Infolists\Components\TextEntry::make('customer.telepon')
                                    ->label('Phone')
                                    ->copyable()
                                    ->icon('heroicon-m-phone'),

                                Infolists\Components\TextEntry::make('customer.alamat')
                                    ->label('Address')
                                    ->icon('heroicon-m-map-pin'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Product Details')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(6)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('nama_produk')
                                            ->label('Product Name')
                                            ->weight(FontWeight::Medium)
                                            ->columnSpan(2),

                                        Infolists\Components\TextEntry::make('jumlah')
                                            ->label('Qty')
                                            ->numeric()
                                            ->columnSpan(1),

                                        Infolists\Components\TextEntry::make('satuan')
                                            ->label('Unit')
                                            ->columnSpan(1),

                                        Infolists\Components\TextEntry::make('harga_satuan')
                                            ->label('Unit Price')
                                            ->money('IDR')
                                            ->columnSpan(1),

                                        Infolists\Components\TextEntry::make('total')
                                            ->label('Total')
                                            ->money('IDR')
                                            ->weight(FontWeight::SemiBold)
                                            ->color('success')
                                            ->columnSpan(1),
                                    ]),

                                Infolists\Components\TextEntry::make('deskripsi')
                                    ->label('Description')
                                    ->prose()
                                    ->columnSpanFull()
                                    ->visible(fn ($state) => !empty($state)),

                                Infolists\Components\TextEntry::make('keterangan')
                                    ->label('Additional Notes')
                                    ->prose()
                                    ->columnSpanFull()
                                    ->visible(fn ($state) => !empty($state)),
                            ])
                            ->contained(false)
                            ->columns(1),
                    ]),

                Infolists\Components\Section::make('💰 Total Summary')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_sebelum_pajak')
                                    ->label('Subtotal (Before Tax)')
                                    ->money('IDR')
                                    ->weight(FontWeight::Medium),

                                Infolists\Components\TextEntry::make('total_pajak')
                                    ->label('Tax Amount')
                                    ->money('IDR')
                                    ->weight(FontWeight::Medium),

                                Infolists\Components\TextEntry::make('harga')
                                    ->label('🏆 Grand Total')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->color('success'),
                            ]),
                    ])
                    ->compact(),

                Infolists\Components\Section::make('Terms & Conditions')
                    ->schema([
                        Infolists\Components\TextEntry::make('terms_conditions')
                            ->label('')
                            ->prose()
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return 'No terms and conditions specified.';

                                // Convert line breaks to proper list format
                                $lines = explode("\n", $state);
                                $formatted = [];
                                foreach ($lines as $line) {
                                    $line = trim($line);
                                    if (!empty($line)) {
                                        $formatted[] = $line;
                                    }
                                }
                                return implode("\n", $formatted);
                            }),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Activity Log')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d F Y, H:i')
                                    ->icon('heroicon-m-plus-circle'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('d F Y, H:i')
                                    ->icon('heroicon-m-pencil-square'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
            'index' => Pages\ListPenawaran::route('/'),
            'create' => Pages\CreatePenawaran::route('/create'),
            'view' => Pages\ViewPenawaran::route('/{record}'),
            'edit' => Pages\EditPenawaran::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'draft')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nomor_penawaran', 'customer.nama'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Customer' => $record->customer->nama,
            'Price' => 'IDR ' . number_format($record->harga, 0, ',', '.'),
            'Status' => ucfirst($record->status),
        ];
    }
}
