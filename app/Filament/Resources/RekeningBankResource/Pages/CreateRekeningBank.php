<?php

namespace App\Filament\Resources\RekeningBankResource\Pages;

use App\Filament\Resources\RekeningBankResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateRekeningBank extends CreateRecord
{
    protected static string $resource = RekeningBankResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Bank account successfully added')
            ->body('New bank account data has been successfully saved to the system.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
            $this->getCreateFormAction()
                ->label('Save Bank Account'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Add Another'),
            $this->getCancelFormAction()
                ->label('Cancel'),
        ];
    }
}
