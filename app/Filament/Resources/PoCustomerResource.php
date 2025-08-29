<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PoCustomerResource\Pages;
use App\Models\Customer;
use App\Models\PoCustomer;
use App\Models\Product;
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
use Illuminate\Support\Facades\Storage;

class PoCustomerResource extends Resource
{
    protected static ?string $model = PoCustomer::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Customer PO';

    protected static ?string $modelLabel = 'Customer PO';

    protected static ?string $pluralModelLabel = 'Customer POs';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status_po', PoStatus::PENDING->value)->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('PO Information')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_po')
                            ->label('PO Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Enter PO Number')
                            ->helperText('Enter unique PO number manually'),

                        Forms\Components\Select::make('id_customer')
                            ->label('Customer')
                            ->relationship('customer', 'nama')
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->preload(),

                        Forms\Components\Hidden::make('id_user')
                            ->default(optional(\Illuminate\Support\Facades\Auth::user())->id),

                        Forms\Components\DatePicker::make('tanggal_po')
                            ->label('PO Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('jenis_po')
                            ->label('PO Type')
                            ->options([
                                'Product' => 'Product PO',
                                'Service' => 'Service PO',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                // Reset details saat jenis PO berubah
                                $set('details', []);
                                self::updateTotals([], $set);
                            }),

                        Forms\Components\Select::make('status_po')
                            ->label('PO Status')
                            ->options(PoStatus::getOptions())
                            ->default(PoStatus::DRAFT->value)
                            ->required()
                            ->native(false),

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

                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Attachment (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('po-customer-attachments')
                            ->disk('public')
                            ->visibility('public')
                            ->downloadable()
                            ->previewable()
                            ->openable()
                            ->maxSize(10240)
                            ->helperText('Upload PDF file as reference (Max: 10MB)')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Notes/Remarks')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Item Details')
                    ->schema([
                        Forms\Components\Placeholder::make('jenis_po_info')
                            ->label('')
                            ->content(function (Get $get): string {
                                $jenisPo = $get('jenis_po');
                                if ($jenisPo === 'Product') {
                                    return '📦 Product Items - Select products from inventory with quantity';
                                } elseif ($jenisPo === 'Service') {
                                    return '🛠️ Service Items - Add custom services with pricing';
                                } else {
                                    return '⚠️ Please select PO Type first';
                                }
                            })
                            ->columnSpanFull(),

                        Repeater::make('details')
                            ->relationship('details')
                            ->schema([
                                // PRODUCT FIELDS - Tampil ketika jenis_po = Product
                                Forms\Components\Select::make('product_id')
                                    ->label('🔍 Select Product')
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
                                            $set('satuan', '');
                                            $set('deskripsi', '');
                                            $set('total', 0);
                                        }

                                        // Update form totals
                                        self::updateTotalsFromRepeater($get, $set);
                                    })
                                    ->visible(fn (Get $get) => $get('../../jenis_po') === 'Product')
                                    ->required(fn (Get $get) => $get('../../jenis_po') === 'Product')
                                    ->columnSpanFull(),

                                // SERVICE FIELDS - Tampil ketika jenis_po = Service
                                Forms\Components\TextInput::make('nama_produk')
                                    ->label('🛠️ Service Name')
                                    ->required()
                                    ->placeholder('Enter service name')
                                    ->visible(fn (Get $get) => $get('../../jenis_po') === 'Service')
                                    ->columnSpanFull(),

                                // PRODUCT SPECIFIC FIELDS - Fixed quantity handling
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
                                    ])
                                    ->visible(fn (Get $get) => $get('../../jenis_po') === 'Product'),

                                // SERVICE SPECIFIC FIELDS
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('harga_satuan')
                                            ->label('Service Price')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->step(0.01)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $harga = (float) ($state ?? 0);
                                                $set('total', $harga); // Service qty always 1

                                                // Update form totals
                                                self::updateTotalsFromRepeater($get, $set);
                                            })
                                            ->required(),

                                        Forms\Components\TextInput::make('total')
                                            ->label('💰 Total')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->readOnly()
                                            ->dehydrated()
                                            ->extraAttributes(['class' => 'font-bold text-green-600']),
                                    ])
                                    ->visible(fn (Get $get) => $get('../../jenis_po') === 'Service'),

                                Forms\Components\Textarea::make('deskripsi')
                                    ->label('Description')
                                    ->rows(2)
                                    ->placeholder(function (Get $get): string {
                                        return $get('../../jenis_po') === 'Product'
                                            ? 'Product description will be filled automatically'
                                            : 'Describe the service in detail';
                                    })
                                    ->readOnly(fn (Get $get) => $get('../../jenis_po') === 'Product' && !empty($get('product_id')))
                                    ->dehydrated()
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('keterangan')
                                    ->label('Additional Notes')
                                    ->rows(2)
                                    ->placeholder('Add any additional notes')
                                    ->columnSpanFull(),

                                // Hidden fields untuk service - Pindah ke bawah dan perbaiki logic
                                Forms\Components\Hidden::make('product_id')
                                    ->default(null)
                                    ->visible(fn (Get $get) => $get('../../jenis_po') === 'Service')
                                    ->dehydrated(fn (Get $get) => $get('../../jenis_po') === 'Service'),

                                Forms\Components\Hidden::make('satuan')
                                    ->default('service')
                                    ->visible(fn (Get $get) => $get('../../jenis_po') === 'Service')
                                    ->dehydrated(fn (Get $get) => $get('../../jenis_po') === 'Service'),

                                Forms\Components\Hidden::make('jumlah')
                                    ->default(1)
                                    ->visible(fn (Get $get) => $get('../../jenis_po') === 'Service')
                                    ->dehydrated(fn (Get $get) => $get('../../jenis_po') === 'Service'),
                            ])
                            ->columns(1)
                            ->addActionLabel(function (Get $get): string {
                                $jenisPo = $get('jenis_po');
                                if ($jenisPo === 'Product') {
                                    return '📦 Add Product';
                                } else if ($jenisPo === 'Service') {
                                    return '🛠️ Add Service';
                                } else {
                                    return '➕ Add Item';
                                }
                            })
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(function (array $state, Get $get): ?string {
                                $jenisPo = $get('jenis_po');
                                $nama = $state['nama_produk'] ?? 'Unnamed Item';

                                if ($jenisPo === 'Product') {
                                    $qty = $state['jumlah'] ?? 1;
                                    $unit = $state['satuan'] ?? 'pcs';
                                    return "📦 {$nama} ({$qty} {$unit})";
                                } else {
                                    return "🛠️ {$nama}";
                                }
                            })
                            ->live()
                            ->visible(fn (Get $get): bool => !empty($get('jenis_po')))
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
            ]);
    }

    // Helper method untuk update totals dari dalam repeater - FIXED
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

        $set('../../total_sebelum_pajak', $subtotal);
        $set('../../total_pajak', $pajak);
    }

    // Helper method utama untuk update totals - FIXED
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

        $set('total_sebelum_pajak', $subtotal);
        $set('total_pajak', $pajak);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_po')
                    ->label('PO Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis_po')
                    ->label('PO Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Product' => 'info',
                        'Service' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Product' => 'Product',
                        'Service' => 'Service',
                        default => $state,
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

                Tables\Columns\IconColumn::make('attachment_path')
                    ->label('Attachment')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('')
                    ->getStateUsing(fn ($record) => !empty($record->attachment_path)),

                Tables\Columns\TextColumn::make('tax_rate')
                    ->label('Tax Rate')
                    ->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_po')
                    ->label('Status')
                    ->options(PoStatus::getOptions()),

                Tables\Filters\SelectFilter::make('jenis_po')
                    ->label('PO Type')
                    ->options([
                        'Product' => 'Product',
                        'Service' => 'Service',
                    ]),

                Tables\Filters\Filter::make('has_attachment')
                    ->label('Has Attachment')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('attachment_path')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (PoCustomer $record): bool => $record->canBeEdited()),

                Tables\Actions\Action::make('download_attachment')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (PoCustomer $record): bool => $record->hasAttachment())
                    ->action(function (PoCustomer $record) {
                        $path = Storage::disk('public')->path($record->attachment_path);
                        $filename = $record->attachment_name ?? 'attachment.pdf';
                        return response()->download($path, $filename);
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Customer PO')
                    ->modalDescription('Are you sure you want to approve this PO?')
                    ->visible(fn (PoCustomer $record): bool => $record->isPending())
                    ->action(fn (PoCustomer $record) => $record->update(['status_po' => PoStatus::APPROVED->value]))
                    ->after(fn () => \Filament\Notifications\Notification::make()
                        ->title('PO approved successfully')
                        ->success()
                        ->send()),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Customer PO')
                    ->modalDescription('Are you sure you want to reject this PO?')
                    ->visible(fn (PoCustomer $record): bool => $record->isPending())
                    ->action(fn (PoCustomer $record) => $record->update(['status_po' => PoStatus::REJECTED->value]))
                    ->after(fn () => \Filament\Notifications\Notification::make()
                        ->title('PO rejected successfully')
                        ->success()
                        ->send()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (PoCustomer $record): bool => $record->canBeDeleted()),
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
            'index' => Pages\ListPoCustomers::route('/'),
            'create' => Pages\CreatePoCustomer::route('/create'),
            'view' => Pages\ViewPoCustomer::route('/{record}'),
            'edit' => Pages\EditPoCustomer::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['customer', 'user']);
    }
}