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
                ->modalHeading('Hapus Transaksi')
                ->modalDescription('Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Hapus'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Transaksi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->date('d F Y'),

                                TextEntry::make('jenis')
                                    ->label('Jenis Transaksi')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pemasukan' => 'success',
                                        'pengeluaran' => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pemasukan' => 'Pemasukan',
                                        'pengeluaran' => 'Pengeluaran',
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('rekening.nama_bank')
                                    ->label('Bank'),

                                TextEntry::make('rekening.nomor_rekening')
                                    ->label('Nomor Rekening'),
                            ]),

                        TextEntry::make('jumlah')
                            ->label('Jumlah')
                            ->money('IDR')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(fn ($record): string =>
                                $record->jenis === 'pemasukan' ? 'success' : 'danger'),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Tidak ada keterangan'),
                    ]),

                Section::make('Referensi')
                    ->schema([
                        TextEntry::make('poSupplier.nomor_po')
                            ->label('Nomor PO Supplier')
                            ->url(fn ($record) => $record->poSupplier ?
                                route('filament.admin.resources.po-suppliers.view', $record->poSupplier) : null)
                            ->color('primary')
                            ->placeholder('Tidak ada referensi PO')
                            ->visible(fn ($record) => $record->jenis === 'pengeluaran'),

                        TextEntry::make('poSupplier.supplier.nama')
                            ->label('Nama Supplier')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->jenis === 'pengeluaran' && $record->poSupplier),

                        TextEntry::make('invoice.nomor_invoice')
                            ->label('Nomor Invoice')
                            ->url(fn ($record) => $record->invoice ?
                                route('filament.admin.resources.invoices.view', $record->invoice) : null)
                            ->color('primary')
                            ->placeholder('Tidak ada referensi Invoice')
                            ->visible(fn ($record) => $record->jenis === 'pemasukan'),

                        TextEntry::make('invoice.poCustomer.customer.nama')
                            ->label('Nama Customer')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->jenis === 'pemasukan' && $record->invoice),
                    ])
                    ->visible(fn ($record) => $record->poSupplier || $record->invoice),

                Section::make('Informasi Sistem')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d F Y, H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Diperbarui Pada')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
