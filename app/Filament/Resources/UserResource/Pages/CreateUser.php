<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // Disable default notification, kita buat custom di afterCreate
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = trim($data['name']);
        $data['email'] = strtolower(trim($data['email']));
        unset($data['password_confirmation']);
        return $data;
    }

    protected function afterCreate(): void
    {
        // Kirim email verifikasi otomatis setelah user dibuat
        $emailSent = $this->record->sendEmailVerificationNotification();

        if ($emailSent) {
            Notification::make()
                ->success()
                ->title('User berhasil ditambahkan')
                ->body('Data user baru telah disimpan dan email verifikasi telah dikirim ke ' . $this->record->email)
                ->duration(5000)
                ->send();
        } else {
            Notification::make()
                ->warning()
                ->title('User berhasil ditambahkan')
                ->body('User berhasil dibuat, namun email verifikasi gagal dikirim. Anda dapat mengirim ulang dari halaman detail user.')
                ->duration(7000)
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Simpan User'),
            $this->getCreateAnotherFormAction()->label('Simpan & Tambah Lagi'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}