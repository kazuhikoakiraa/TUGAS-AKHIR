<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use App\Enums\PoStatus;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewPoSupplier extends ViewRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected static ?string $title = 'Supplier Purchase Order Details';

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
                ->modalHeading('Approve Supplier PO')
                ->modalDescription('Are you sure you want to approve this PO?')
                ->visible(fn (): bool => $this->record->status_po === PoStatus::PENDING->value)
                ->action(function () {
                    // Pastikan total sudah benar sebelum approve
                    $this->recalculateTotal();

                    $this->record->update(['status_po' => PoStatus::APPROVED->value]);
                    \Filament\Notifications\Notification::make()
                        ->title('PO approved successfully')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Reject PO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reject Supplier PO')
                ->modalDescription('Are you sure you want to reject this PO?')
                ->visible(fn (): bool => $this->record->status_po === PoStatus::PENDING->value)
                ->action(function () {
                    $this->record->update(['status_po' => PoStatus::REJECTED->value]);
                    \Filament\Notifications\Notification::make()
                        ->title('PO rejected successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Recalculate total sebelum update status
     */
    private function recalculateTotal(): void
    {
        $totalSebelumPajak = $this->record->details()->sum(DB::raw('jumlah * harga_satuan'));
        $totalPajak = $totalSebelumPajak * 0.11;

        $this->record->update([
            'total_sebelum_pajak' => $totalSebelumPajak,
            'total_pajak' => $totalPajak,
        ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('PO Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nomor_po')
                                    ->label('PO Number'),

                                TextEntry::make('supplier.nama')
                                    ->label('Supplier'),

                                TextEntry::make('tanggal_po')
                                    ->label('PO Date')
                                    ->date(),

                                TextEntry::make('jenis_po')
                                    ->label('PO Type')
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
                                    ->label('Created By'),
                            ]),
                    ]),

                Section::make('Item Details')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->schema([
                                TextEntry::make('deskripsi')
                                    ->label('Description')
                                    ->columnSpanFull(),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('jumlah')
                                            ->label('Quantity'),

                                        TextEntry::make('harga_satuan')
                                            ->label('Unit Price')
                                            ->money('IDR'),

                                        TextEntry::make('total')
                                            ->label('Total')
                                            ->money('IDR')
                                            ->weight('bold')
                                            ->formatStateUsing(function ($state, $record): string {
                                                // Hitung total dari jumlah * harga_satuan
                                                $total = $record->jumlah * $record->harga_satuan;
                                                return number_format($total, 0, ',', '.');
                                            }),
                                    ]),
                            ])
                            ->columns(1),
                    ]),

                Section::make('Total & Tax')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_sebelum_pajak')
                                    ->label('Subtotal')
                                    ->money('IDR')
                                    ->size('lg'),

                                TextEntry::make('total_pajak')
                                    ->label('Tax (11%)')
                                    ->money('IDR')
                                    ->size('lg'),

                                TextEntry::make('total')
                                    ->label('Grand Total')
                                    ->money('IDR')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success')
                                    ->formatStateUsing(function ($state, $record): string {
                                        $total = $record->total_sebelum_pajak + $record->total_pajak;
                                        return number_format($total, 0, ',', '.');
                                    }),
                            ]),
                    ]),
            ]);
    }
}
