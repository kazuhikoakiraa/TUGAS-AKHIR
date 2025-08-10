<?php

namespace App\Filament\Resources\TransaksiKeuanganResource\Pages;

use App\Filament\Resources\TransaksiKeuanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

class ViewTransaksiKeuangan extends ViewRecord
{
    protected static string $resource = TransaksiKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Transaction')
                ->modalDescription('Are you sure you want to delete this transaction? This action cannot be undone.')
                ->modalSubmitActionLabel('Delete'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Transaction Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Transaction Date')
                                    ->date('d F Y'),

                                TextEntry::make('jenis')
                                    ->label('Transaction Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pemasukan' => 'success',
                                        'pengeluaran' => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pemasukan' => 'Income',
                                        'pengeluaran' => 'Expense',
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('rekening.nama_bank')
                                    ->label('Bank'),

                                TextEntry::make('rekening.nomor_rekening')
                                    ->label('Account Number'),
                            ]),

                        TextEntry::make('jumlah')
                            ->label('Amount')
                            ->money('IDR')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(fn ($record): string =>
                                $record->jenis === 'pemasukan' ? 'success' : 'danger'),

                        TextEntry::make('keterangan')
                            ->label('Description')
                            ->placeholder('No description'),
                    ]),

                Section::make('Reference')
                    ->schema([
                        TextEntry::make('poSupplier.nomor_po')
                            ->label('Supplier PO Number')
                            ->url(fn ($record) => $record->poSupplier ?
                                route('filament.admin.resources.po-suppliers.view', $record->poSupplier) : null)
                            ->color('primary')
                            ->placeholder('No PO reference')
                            ->visible(fn ($record) => $record->jenis === 'pengeluaran'),

                        TextEntry::make('poSupplier.supplier.nama')
                            ->label('Supplier Name')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->jenis === 'pengeluaran' && $record->poSupplier),

                        TextEntry::make('invoice.nomor_invoice')
                            ->label('Invoice Number')
                            ->url(fn ($record) => $record->invoice ?
                                route('filament.admin.resources.invoices.view', $record->invoice) : null)
                            ->color('primary')
                            ->placeholder('No invoice reference')
                            ->visible(fn ($record) => $record->jenis === 'pemasukan'),

                        TextEntry::make('invoice.poCustomer.customer.nama')
                            ->label('Customer Name')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->jenis === 'pemasukan' && $record->invoice),
                    ])
                    ->visible(fn ($record) => $record->poSupplier || $record->invoice),

                Section::make('System Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d F Y, H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
