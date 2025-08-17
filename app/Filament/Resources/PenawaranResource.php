<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenawaranResource\Pages;
use App\Models\Penawaran;
use App\Models\Customer;
use App\Models\User;
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
                Forms\Components\Section::make('Quotation Information')
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
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Quotation Details')
                    ->description('Enter quotation description and pricing')
                    ->schema([
                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Description')
                            ->required()
                            ->rows(4)
                            ->placeholder('Enter detailed description of products/services being quoted...')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('harga')
                            ->label('Total Price')
                            ->required()
                            ->numeric()
                            ->prefix('IDR')
                            ->step(0.01)
                            ->minValue(0)
                            ->placeholder('0.00')
                            ->prefixIcon('heroicon-m-currency-dollar'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
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

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
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

    Tables\Filters\Filter::make('price_range')
        ->form([
            Forms\Components\TextInput::make('price_from')
                ->label('Price from')
                ->numeric()
                ->prefix('IDR'),
            Forms\Components\TextInput::make('price_until')
                ->label('Price until')
                ->numeric()
                ->prefix('IDR'),
        ])
        ->query(function (Builder $query, array $data): Builder {
            return $query
                ->when(
                    $data['price_from'],
                    fn (Builder $query, $price): Builder => $query->where('harga', '>=', $price),
                )
                ->when(
                    $data['price_until'],
                    fn (Builder $query, $price): Builder => $query->where('harga', '<=', $price),
                );
        })
        ->columns(2),

    Tables\Filters\SelectFilter::make('user')
        ->relationship('user', 'name')
        ->label('Sales Person')
        ->searchable()
        ->preload()
        ->placeholder('Filter by sales person'),
])
->filtersFormColumns(2)
->filtersFormMaxHeight('400px')
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

                            return redirect(static::getUrl('edit', ['record' => $newRecord]));
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Quotation')
                        ->modalDescription('Are you sure you want to duplicate this quotation?'),
                    Tables\Actions\Action::make('send')
                        ->label('Send to Customer')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->action(function ($record) {
                            // Update status first
                            $record->update(['status' => 'sent']);

                            // Send email with PDF
                            try {
                                Mail::to($record->customer->email)->send(new \App\Mail\QuotationSent($record));

                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Quotation sent successfully')
                                    ->body("Quotation has been sent to {$record->customer->nama} ({$record->customer->email})")
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Failed to send email')
                                    ->body('Quotation status updated but email failed to send: ' . $e->getMessage())
                                    ->send();
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->modalHeading('Send Quotation')
                        ->modalDescription('Are you sure you want to send this quotation to customer via email?'),
                    Tables\Actions\Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn ($record) => route('quotation.pdf', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Quotation')
                        ->modalDescription('Are you sure you want to delete this quotation? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'draft') {
                                    $record->delete();
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Quotations')
                        ->modalDescription('Are you sure you want to delete all selected quotations? Only draft quotations will be deleted.')
                        ->modalSubmitActionLabel('Yes, Delete All'),
                    Tables\Actions\BulkAction::make('mark_as_sent')
                        ->label('Send to Customers')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->action(function ($records) {
                            $sent = 0;
                            $failed = 0;

                            $records->each(function ($record) use (&$sent, &$failed) {
                                if ($record->status === 'draft') {
                                    try {
                                        $record->update(['status' => 'sent']);
                                        Mail::to($record->customer->email)->send(new \App\Mail\QuotationSent($record));
                                        $sent++;
                                    } catch (\Exception $e) {
                                        $failed++;
                                    }
                                }
                            });

                            if ($sent > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title("{$sent} quotations sent successfully")
                                    ->body($failed > 0 ? "{$failed} quotations failed to send email" : '')
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Selected Quotations')
                        ->modalDescription('Are you sure you want to send selected quotations to customers via email?'),
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

                                Infolists\Components\TextEntry::make('harga')
                                    ->label('Total Price')
                                    ->money('IDR')
                                    ->weight(FontWeight::SemiBold)
                                    ->icon('heroicon-m-currency-dollar'),
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
                            ]),

                        Infolists\Components\TextEntry::make('customer.alamat')
                            ->label('Address')
                            ->columnSpanFull()
                            ->icon('heroicon-m-map-pin'),
                    ]),

                Infolists\Components\Section::make('Description')
                    ->schema([
                        Infolists\Components\TextEntry::make('deskripsi')
                            ->label('Quotation Description')
                            ->prose()
                            ->hiddenLabel(),
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
        return ['nomor_penawaran', 'customer.nama', 'deskripsi'];
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
