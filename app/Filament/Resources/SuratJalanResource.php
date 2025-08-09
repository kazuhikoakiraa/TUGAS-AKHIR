<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuratJalanResource\Pages;
use App\Models\SuratJalan;
use App\Models\PoCustomer;
use App\Enums\PoStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SuratJalanResource extends Resource
{
    protected static ?string $model = SuratJalan::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Delivery Notes';

    protected static ?string $pluralLabel = 'Delivery Notes';

    protected static ?string $modelLabel = 'Delivery Note';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Delivery Note Information')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_surat_jalan')
                            ->label('Delivery Note Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Will be auto-generated')
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\Select::make('id_po_customer')
                            ->label('Customer PO')
                            ->relationship(
                                'poCustomer',
                                'nomor_po',
                                modifyQueryUsing: function (Builder $query, ?string $operation = null, $record = null) {
                                    $query->where('jenis_po', 'Produk')
                                          ->where('status_po', PoStatus::APPROVED)
                                          ->with('customer');

                                    // If editing, allow currently selected PO
                                    if ($operation === 'edit' && $record) {
                                        $query->where(function ($q) use ($record) {
                                            $q->whereDoesntHave('suratJalan')
                                              ->orWhere('id', $record->id_po_customer);
                                        });
                                    } else {
                                        // For create, only show POs without delivery notes
                                        $query->whereDoesntHave('suratJalan');
                                    }

                                    return $query;
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (PoCustomer $record): string =>
                                "{$record->nomor_po} - {$record->customer->nama}"
                            )
                            ->searchable(['nomor_po'])
                            ->required()
                            ->native(false)
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state && is_numeric($state)) {
                                    try {
                                        $po = PoCustomer::with('customer')->find((int) $state);
                                        if ($po && $po->customer && $po->customer->alamat) {
                                            $set('alamat_pengiriman', $po->customer->alamat);
                                        }
                                    } catch (\Exception $e) {
                                        // Log error if needed
                                        Log::error('Error fetching Customer PO data: ' . $e->getMessage());
                                    }
                                }
                            })
                            ->validationAttribute('Customer PO'),

                        Forms\Components\Hidden::make('id_user')
                            ->default(fn () => \Illuminate\Support\Facades\Auth::user()?->id),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Delivery Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(now()->subDays(7)) // Allow 7 days back
                            ->maxDate(now()->addMonths(3)), // Max 3 months ahead

                        Forms\Components\Textarea::make('alamat_pengiriman')
                            ->label('Delivery Address')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->placeholder('Address will be auto-filled based on selected Customer PO'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_surat_jalan')
                    ->label('Delivery Note Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('poCustomer.nomor_po')
                    ->label('PO Number')
                    ->searchable(['poCustomer.nomor_po'])
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('poCustomer.customer.nama')
                    ->label('Customer')
                    ->searchable(['poCustomer.customer.nama'])
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Delivery Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('alamat_pengiriman')
                    ->label('Delivery Address')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_range')
                    ->label('Date Filter')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('From Date')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('To Date')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators['dari_tanggal'] = 'From: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'To: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('customer')
                    ->label('Customer')
                    ->relationship('poCustomer.customer', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),

                Tables\Actions\EditAction::make()
                    ->color('warning'),

                Tables\Actions\Action::make('print_pdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (SuratJalan $record): string => route('surat-jalan.pdf', $record))
                    ->openUrlInNewTab()
                    ->tooltip('Print Delivery Note in PDF format'),

                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Delivery Note')
                    ->modalDescription('Are you sure you want to delete this delivery note? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Delivery Notes')
                        ->modalDescription('Are you sure you want to delete the selected delivery notes? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete All'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relations here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuratJalans::route('/'),
            'create' => Pages\CreateSuratJalan::route('/create'),
            'view' => Pages\ViewSuratJalan::route('/{record}'),
            'edit' => Pages\EditSuratJalan::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['poCustomer.customer', 'user']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'nomor_surat_jalan',
            'poCustomer.nomor_po',
            'poCustomer.customer.nama',
            'alamat_pengiriman',
        ];
    }

    // Navigation badge to show delivery notes count for this month
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereMonth('created_at', now()->month)
                                  ->whereYear('created_at', now()->year)
                                  ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
