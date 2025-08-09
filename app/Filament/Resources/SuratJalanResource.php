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
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class SuratJalanResource extends Resource
{
    protected static ?string $model = SuratJalan::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Surat Jalan';

    protected static ?string $pluralLabel = 'Surat Jalan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Surat Jalan')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_surat_jalan')
                            ->label('Nomor Surat Jalan')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Akan di-generate otomatis'),

                        Forms\Components\Select::make('id_po_customer')
                            ->label('PO Customer')
                            ->relationship(
                                'poCustomer',
                                'nomor_po',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->where('jenis_po', 'Produk')
                                    ->where('status_po', PoStatus::APPROVED)
                                    ->whereDoesntHave('suratJalan') // Hanya tampilkan PO yang belum memiliki surat jalan
                                    ->with('customer')
                            )
                            ->getOptionLabelFromRecordUsing(fn (PoCustomer $record): string =>
                                "{$record->nomor_po} - {$record->customer->nama}"
                            )
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Forms\Set $set) {
                                $poId = $get('id_po_customer');
                                if ($poId) {
                                    $po = PoCustomer::with('customer')->find($poId);
                                    if ($po && $po->customer) {
                                        $set('alamat_pengiriman', $po->customer->alamat ?? '');
                                    }
                                }
                            })
                            ->rules([
                                fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // Validasi untuk create (tidak ada record ID)
                                    if (!$get('../../id')) {
                                        $exists = SuratJalan::where('id_po_customer', $value)->exists();
                                        if ($exists) {
                                            $fail('PO Customer ini sudah memiliki surat jalan. Silakan pilih PO Customer lain.');
                                        }
                                    }
                                },
                            ]),

                        Forms\Components\Hidden::make('id_user')
                            ->default(optional(\Illuminate\Support\Facades\Auth::user())->id),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pengiriman')
                            ->required()
                            ->default(now()),

                        Forms\Components\Textarea::make('alamat_pengiriman')
                            ->label('Alamat Pengiriman')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_surat_jalan')
                    ->label('Nomor Surat Jalan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('poCustomer.nomor_po')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('poCustomer.customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal Pengiriman')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('alamat_pengiriman')
                    ->label('Alamat Pengiriman')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
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
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('print_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (SuratJalan $record): string => route('surat-jalan.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListSuratJalans::route('/'),
            'create' => Pages\CreateSuratJalan::route('/create'),
            'view' => Pages\ViewSuratJalan::route('/{record}'),
            'edit' => Pages\EditSuratJalan::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['poCustomer.customer', 'user']);
    }
}
