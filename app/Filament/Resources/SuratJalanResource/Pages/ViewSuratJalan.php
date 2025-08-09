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
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('surat-jalan.pdf', $this->record))
                ->openUrlInNewTab()
                ->tooltip('Cetak Surat Jalan dalam format PDF'),

            Actions\DeleteAction::make()
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Surat Jalan')
                ->modalDescription('Apakah Anda yakin ingin menghapus surat jalan ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->successRedirectUrl($this->getResource()::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Surat Jalan')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_surat_jalan')
                            ->label('Nomor Surat Jalan')
                            ->badge()
                            ->color('primary')
                            ->size('lg')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('tanggal')
                            ->label('Tanggal Pengiriman')
                            ->date('d F Y')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Informasi PO Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('poCustomer.nomor_po')
                            ->label('Nomor PO')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('poCustomer.customer.nama')
                            ->label('Nama Customer')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('poCustomer.customer.email')
                            ->label('Email Customer')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('poCustomer.customer.telepon')
                            ->label('Telepon Customer')
                            ->icon('heroicon-o-phone')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('poCustomer.tanggal_po')
                            ->label('Tanggal PO')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('poCustomer.status_po')
                            ->label('Status PO')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                \App\Enums\PoStatus::PENDING => 'warning',
                                \App\Enums\PoStatus::APPROVED => 'success',
                                \App\Enums\PoStatus::REJECTED => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Informasi Pengiriman')
                    ->schema([
                        Infolists\Components\TextEntry::make('alamat_pengiriman')
                            ->label('Alamat Pengiriman')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Dibuat Oleh')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-clock'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}
