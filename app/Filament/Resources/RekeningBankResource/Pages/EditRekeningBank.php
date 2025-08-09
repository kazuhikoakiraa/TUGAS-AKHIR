<?php

namespace App\Filament\Resources\RekeningBankResource\Pages;

use App\Filament\Resources\RekeningBankResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRekeningBank extends EditRecord
{
    protected static string $resource = RekeningBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Details')
                ->icon('heroicon-o-eye'),

            Actions\DeleteAction::make()
                ->label('Delete Bank Account')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete Bank Account')
                ->modalDescription('Are you sure you want to delete this bank account? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index')),

        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Bank account successfully updated')
            ->body('Bank account data has been successfully updated.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clean and format data before saving
        $data['nama_bank'] = trim($data['nama_bank']);
        $data['nomor_rekening'] = preg_replace('/\D/', '', $data['nomor_rekening']); // Remove non-numeric characters
        $data['nama_pemilik'] = trim($data['nama_pemilik']);

        if (isset($data['keterangan'])) {
            $data['keterangan'] = trim($data['keterangan']);
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Save Changes'),
            $this->getCancelFormAction()
                ->label('Cancel'),
        ];
    }
}
