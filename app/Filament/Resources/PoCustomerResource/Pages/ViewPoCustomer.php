<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use App\Enums\PoStatus; // Tambahkan import ini
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPoCustomer extends ViewRecord
{
    protected static string $resource = PoCustomerResource::class;

    protected static ?string $title = 'Detail Purchase Order Customer';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->canBeEdited()),

            Actions\Action::make('approve')
                ->label('Approve PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve PO Customer')
                ->modalDescription('Apakah Anda yakin ingin menyetujui PO ini?')
                ->visible(fn (): bool => $this->record->status_po === PoStatus::PENDING->value) // Fix: gunakan enum
                ->action(function () {
                    $this->record->update(['status_po' => PoStatus::APPROVED->value]); // Fix: gunakan enum
                    \Filament\Notifications\Notification::make()
                        ->title('PO berhasil disetujui')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Reject PO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reject PO Customer')
                ->modalDescription('Apakah Anda yakin ingin menolak PO ini?')
                ->visible(fn (): bool => $this->record->status_po === PoStatus::PENDING->value) // Fix: gunakan enum
                ->action(function () {
                    $this->record->update(['status_po' => PoStatus::REJECTED->value]); // Fix: gunakan enum
                    \Filament\Notifications\Notification::make()
                        ->title('PO berhasil ditolak')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi PO')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nomor_po')
                                    ->label('Nomor PO'),

                                TextEntry::make('customer.nama')
                                    ->label('Customer'),

                                TextEntry::make('tanggal_po')
                                    ->label('Tanggal PO')
                                    ->date(),

                                TextEntry::make('jenis_po')
                                    ->label('Jenis PO')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('status_po')
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

                                TextEntry::make('user.name')
                                    ->label('Dibuat Oleh'),
                            ]),
                    ]),

                Section::make('Detail Item')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->schema([
                                TextEntry::make('deskripsi')
                                    ->label('Deskripsi')
                                    ->columnSpanFull(),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('jumlah')
                                            ->label('Jumlah'),

                                        TextEntry::make('harga_satuan')
                                            ->label('Harga Satuan')
                                            ->money('IDR'),

                                        TextEntry::make('total')
                                            ->label('Total')
                                            ->money('IDR')
                                            ->weight('bold'),
                                    ]),
                            ])
                            ->columns(1),
                    ]),

                Section::make('Total & Pajak')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_sebelum_pajak')
                                    ->label('Total Sebelum Pajak')
                                    ->money('IDR')
                                    ->size('lg'),

                                TextEntry::make('total_pajak')
                                    ->label('Pajak (11%)')
                                    ->money('IDR')
                                    ->size('lg'),

                                TextEntry::make('total')
                                    ->label('Total Keseluruhan')
                                    ->money('IDR')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),
            ]);
    }
}