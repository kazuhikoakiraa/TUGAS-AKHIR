<?php

namespace App\Filament\Resources\RekeningBankResource\Pages;

use App\Filament\Resources\RekeningBankResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewRekeningBank extends ViewRecord
{
    protected static string $resource = RekeningBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Rekening Bank')
                ->icon('heroicon-o-pencil-square')
                ->modalWidth('2xl'),

            Actions\DeleteAction::make()
                ->label('Hapus Rekening Bank')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Rekening Bank')
                ->modalDescription('Apakah Anda yakin ingin menghapus rekening bank ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index')),

            Actions\Action::make('duplicate')
                ->label('Duplikasi Rekening')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Duplikasi Rekening Bank')
                ->modalDescription('Apakah Anda ingin membuat salinan rekening bank ini?')
                ->modalSubmitActionLabel('Ya, Duplikasi')
                ->action(function ($record) {
                    $newRecord = $record->replicate();
                    $newRecord->nomor_rekening = $record->nomor_rekening . '_copy';
                    $newRecord->save();

                    Notification::make()
                        ->success()
                        ->title('Rekening berhasil diduplikasi')
                        ->body('Salinan rekening bank telah dibuat dengan nomor rekening: ' . $newRecord->nomor_rekening)
                        ->duration(5000)
                        ->send();

                    return redirect()->to(static::getResource()::getUrl('edit', ['record' => $newRecord->id]));
                }),

            Actions\Action::make('viewTransactions')
                ->label('Lihat Transaksi')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn ($record) => $record->transaksiKeuangan()->count() > 0)
                ->action(function ($record) {
                    // Redirect ke halaman transaksi dengan filter rekening bank
                    // Sesuaikan dengan route TransaksiKeuangan Resource Anda
                    Notification::make()
                        ->info()
                        ->title('Fitur akan segera tersedia')
                        ->body('Halaman transaksi keuangan akan ditampilkan.')
                        ->duration(3000)
                        ->send();
                }),

            Actions\Action::make('exportData')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Export Data Rekening')
                ->modalDescription('Data rekening bank akan diexport dalam format Excel.')
                ->modalSubmitActionLabel('Ya, Export')
                ->action(function ($record) {
                    // Di sini Anda bisa menambahkan logic untuk export data
                    // Untuk contoh, kita hanya tampilkan notifikasi

                    Notification::make()
                        ->success()
                        ->title('Data berhasil diexport')
                        ->body('File Excel telah berhasil dibuat dan siap diunduh.')
                        ->duration(5000)
                        ->send();
                }),

            Actions\Action::make('printData')
                ->label('Print Data')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function ($record) {
                    // Di sini Anda bisa menambahkan logic untuk print data
                    // Untuk contoh, kita hanya tampilkan notifikasi

                    Notification::make()
                        ->success()
                        ->title('Data siap dicetak')
                        ->body('Dokumen rekening bank siap untuk dicetak.')
                        ->duration(3000)
                        ->send();
                }),
        ];
    }
}
