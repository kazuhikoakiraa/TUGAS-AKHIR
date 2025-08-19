<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use App\Enums\PoStatus;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewPoCustomer extends ViewRecord
{
    protected static string $resource = PoCustomerResource::class;

    protected static ?string $title = 'Customer Purchase Order Details';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->canBeEdited()),

            Actions\Action::make('download_attachment')
                ->label('Download Attachment')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn (): bool => $this->record->hasAttachment())
                ->action(function () {
                    return Storage::download(
                        $this->record->attachment_path,
                        $this->record->attachment_name ?? 'po-attachment.pdf'
                    );
                }),

            Actions\Action::make('approve')
                ->label('Approve PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Customer PO')
                ->modalDescription('Are you sure you want to approve this PO?')
                ->visible(fn (): bool => $this->record->status_po === PoStatus::PENDING)
                ->action(function () {
                    $this->record->update(['status_po' => PoStatus::APPROVED]);
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
                ->modalHeading('Reject Customer PO')
                ->modalDescription('Are you sure you want to reject this PO?')
                ->visible(fn (): bool => $this->record->status_po === PoStatus::PENDING)
                ->action(function () {
                    $this->record->update(['status_po' => PoStatus::REJECTED]);
                    \Filament\Notifications\Notification::make()
                        ->title('PO rejected successfully')
                        ->success()
                        ->send();
                }),
        ];
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

                                TextEntry::make('customer.nama')
                                    ->label('Customer'),

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

                                TextEntry::make('tax_rate')
                                    ->label('Tax Rate')
                                    ->suffix('%'),

                                TextEntry::make('attachment_path')
                                    ->label('Attachment')
                                    ->formatStateUsing(function ($state): string {
                                        return $state ? 'Available' : 'No attachment';
                                    })
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ]),

                        TextEntry::make('keterangan')
                            ->label('Notes/Remarks')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ]),

                Section::make('Item Details')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('nama_produk')
                                            ->label('Product/Service Name')
                                            ->weight('bold'),

                                        TextEntry::make('jumlah')
                                            ->label('Quantity')
                                            ->suffix(fn ($record) => $record->satuan ? ' ' . $record->satuan : ''),

                                        TextEntry::make('harga_satuan')
                                            ->label('Unit Price')
                                            ->money('IDR'),

                                        TextEntry::make('total')
                                            ->label('Total')
                                            ->money('IDR')
                                            ->weight('bold'),
                                    ]),

                                TextEntry::make('deskripsi')
                                    ->label('Description')
                                    ->placeholder('No description')
                                    ->columnSpanFull(),

                                TextEntry::make('keterangan')
                                    ->label('Notes')
                                    ->placeholder('No notes')
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => !empty($record->keterangan)),

                                TextEntry::make('product.name')
                                    ->label('Product Reference')
                                    ->visible(fn ($record) => !empty($record->product_id))
                                    ->badge()
                                    ->color('primary')
                                    ->columnSpanFull(),
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
                                    ->label('Tax')
                                    ->money('IDR')
                                    ->size('lg')
                                    ->suffix(fn ($record) => ' (' . $record->tax_rate . '%)'),

                                TextEntry::make('total')
                                    ->label('Grand Total')
                                    ->money('IDR')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),
                        ]);
                }
}
