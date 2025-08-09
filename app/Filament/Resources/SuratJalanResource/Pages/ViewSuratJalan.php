<?php

namespace App\Filament\Resources\SuratJalanResource\Pages;

use App\Filament\Resources\SuratJalanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSuratJalan extends ViewRecord
{
    protected static string $resource = SuratJalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('warning')
                ->icon('heroicon-o-pencil'),

            Actions\Action::make('print_pdf')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('surat-jalan.pdf', $this->record))
                ->openUrlInNewTab()
                ->tooltip('Print Delivery Note in PDF format'),

            Actions\DeleteAction::make()
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Delivery Note')
                ->modalDescription('Are you sure you want to delete this delivery note? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successRedirectUrl($this->getResource()::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Delivery Note Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_surat_jalan')
                            ->label('Delivery Note Number')
                            ->badge()
                            ->color('primary')
                            ->size('lg')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('tanggal')
                            ->label('Delivery Date')
                            ->date('d F Y')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Customer PO Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('poCustomer.nomor_po')
                            ->label('PO Number')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('poCustomer.customer.nama')
                            ->label('Customer Name')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('poCustomer.customer.email')
                            ->label('Customer Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('poCustomer.customer.telepon')
                            ->label('Customer Phone')
                            ->icon('heroicon-o-phone')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('poCustomer.tanggal_po')
                            ->label('PO Date')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('poCustomer.status_po')
                            ->label('PO Status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                \App\Enums\PoStatus::PENDING => 'warning',
                                \App\Enums\PoStatus::APPROVED => 'success',
                                \App\Enums\PoStatus::REJECTED => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Delivery Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('alamat_pengiriman')
                            ->label('Delivery Address')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Infolists\Components\Section::make('System Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Created By')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-clock'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}
