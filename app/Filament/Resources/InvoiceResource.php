<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\PoCustomer;
use App\Models\User;
use App\Enums\PoStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoice';

    protected static ?string $pluralModelLabel = 'Invoices';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\Select::make('id_po_customer')
                            ->label('PO Customer')
                            ->options(function () {
                                return PoCustomer::where('status_po', PoStatus::APPROVED)
                                    ->whereDoesntHave('invoice') // Only PO without invoice
                                    ->with('customer')
                                    ->get()
                                    ->mapWithKeys(function ($po) {
                                        $customerName = $po->customer ? $po->customer->nama : 'Customer not found';
                                        return [$po->id => $po->nomor_po . ' - ' . $customerName];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $po = PoCustomer::find($state);
                                    if ($po) {
                                        $set('total_sebelum_pajak', $po->total_sebelum_pajak ?? 0);
                                        $set('total_pajak', $po->total_pajak ?? 0);
                                        $set('grand_total', ($po->total_sebelum_pajak ?? 0) + ($po->total_pajak ?? 0));
                                    }
                                }
                            })
                            ->helperText('Only approved PO Customers without invoices will be displayed.'),

                        Forms\Components\TextInput::make('dibuat_oleh')
                            ->label('Created By')
                            ->default(\Illuminate\Support\Facades\Auth::user()?->name)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('nomor_invoice')
                            ->label('Invoice Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto Generate'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\Select::make('id_rekening_bank')
                            ->label('Bank Account')
                            ->options(function () {
                                return \App\Models\RekeningBank::all()
                                    ->mapWithKeys(function ($rekening) {
                                        return [
                                            $rekening->id => $rekening->nama_bank . ' - ' .
                                                           $rekening->nomor_rekening . ' (' .
                                                           $rekening->nama_pemilik . ')'
                                        ];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select bank account for payment'),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Invoice Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                            ])
                            ->required()
                            ->default('draft'),

                        Forms\Components\TextInput::make('total_sebelum_pajak')
                            ->label('Subtotal (Before Tax)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $pajak = $state * 0.11;
                                $set('total_pajak', $pajak);
                                $set('grand_total', $state + $pajak);
                            }),

                        Forms\Components\TextInput::make('total_pajak')
                            ->label('Tax Total (VAT 11%)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $total_sebelum = $get('total_sebelum_pajak') ?? 0;
                                $set('grand_total', $total_sebelum + $state);
                            }),

                        Forms\Components\TextInput::make('grand_total')
                            ->label('Grand Total')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_invoice')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('poCustomer.nomor_po')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('poCustomer.customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('rekeningBank.nama_bank')
                    ->label('Bank')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-paper-airplane' => 'sent',
                        'heroicon-o-check-circle' => 'paid',
                        'heroicon-o-exclamation-triangle' => 'overdue',
                    ]),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ]),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('To Date'),
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

                Tables\Actions\EditAction::make()
                    ->visible(fn (Invoice $record): bool => $record->canBeEdited()),

                Action::make('print_pdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (Invoice $record) {
                        return static::printInvoicePdf($record);
                    })
                    ->visible(fn (Invoice $record) => !$record->isDraft()),

                Action::make('mark_as_sent')
                    ->label('Mark as Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->action(function (Invoice $record) {
                        if ($record->markAsSent()) {
                            Notification::make()
                                ->success()
                                ->title('Status Updated')
                                ->body('Invoice successfully marked as sent.')
                                ->send();
                        }
                    })
                    ->visible(fn (Invoice $record): bool => $record->canBeSent())
                    ->requiresConfirmation(),

                Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Invoice $record) {
                        if ($record->markAsPaid()) {
                            Notification::make()
                                ->success()
                                ->title('Status Updated')
                                ->body('Invoice successfully marked as paid.')
                                ->send();
                        }
                    })
                    ->visible(fn (Invoice $record): bool => $record->canBePaid())
                    ->requiresConfirmation(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Invoice $record): bool => $record->canBeDeleted()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('mark_as_sent')
                        ->label('Mark as Sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = 0;
                            $records->each(function ($record) use (&$count) {
                                if ($record->canBeSent() && $record->markAsSent()) {
                                    $count++;
                                }
                            });

                            Notification::make()
                                ->success()
                                ->title('Bulk Update')
                                ->body("{$count} invoices successfully marked as sent.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('mark_as_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = 0;
                            $records->each(function ($record) use (&$count) {
                                if ($record->canBePaid() && $record->markAsPaid()) {
                                    $count++;
                                }
                            });

                            Notification::make()
                                ->success()
                                ->title('Bulk Update')
                                ->body("{$count} invoices successfully marked as paid.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Invoice Information')
                    ->schema([
                        TextEntry::make('nomor_invoice')
                            ->label('Invoice Number'),
                        TextEntry::make('poCustomer.nomor_po')
                            ->label('PO Number')
                            ->default('-'),
                        TextEntry::make('poCustomer.customer.nama')
                            ->label('Customer')
                            ->default('-'),
                        TextEntry::make('rekeningBank.nama_bank')
                            ->label('Bank Account')
                            ->default('-'),
                        TextEntry::make('user.name')
                            ->label('Created By'),
                        TextEntry::make('tanggal')
                            ->label('Date')
                            ->date('d F Y'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'sent' => 'warning',
                                'paid' => 'success',
                                'overdue' => 'danger',
                            }),
                    ])
                    ->columns(2),

                Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('total_sebelum_pajak')
                            ->label('Subtotal (Before Tax)')
                            ->money('IDR'),
                        TextEntry::make('total_pajak')
                            ->label('Tax Total (VAT)')
                            ->money('IDR'),
                        TextEntry::make('grand_total')
                            ->label('Grand Total')
                            ->money('IDR')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('keterangan')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->default('-'),
                    ])
                    ->columns(2),

                Section::make('PO Customer Details')
                    ->schema([
                        TextEntry::make('poCustomer.nomor_po')
                            ->label('PO Number'),
                        TextEntry::make('poCustomer.tanggal_po')
                            ->label('PO Date')
                            ->date('d F Y'),
                        TextEntry::make('poCustomer.jenis_po')
                            ->label('PO Type'),
                        TextEntry::make('poCustomer.status_po')
                            ->label('PO Status')
                            ->badge(),
                    ])
                    ->columns(2)
                    ->visible(fn (Invoice $record): bool => $record->poCustomer !== null),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function printInvoicePdf(Invoice $invoice)
    {
        try {
            $pdf = PDF::loadView('invoices.invoice-pdf', compact('invoice'));

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "Invoice-{$invoice->nomor_invoice}.pdf");
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to generate PDF: ' . $e->getMessage())
                ->send();

            return null;
        }
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'draft')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() >= 0 ? 'primary' : null;
    }
}
