<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat Detail')
                ->icon('heroicon-o-eye'),

            Actions\DeleteAction::make()
                ->label('Hapus User')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus User')
                ->modalDescription('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index')),

            Actions\Action::make('sendPasswordResetEmail')
                ->label('Kirim Reset Password')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kirim Email Reset Password')
                ->modalDescription('Email reset password akan dikirim ke alamat email user. User akan menerima link untuk mengubah password mereka sendiri.')
                ->modalSubmitActionLabel('Ya, Kirim Email')
                ->action(function ($record) {
                    // Send password reset notification
                    $status = Password::sendResetLink(
                        ['email' => $record->email]
                    );

                    if ($status === Password::RESET_LINK_SENT) {
                        // Log activity
                        ActivityLog::create([
                            'subject_type' => get_class($record),
                            'subject_id' => $record->id,
                            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                            'causer_id' => Auth::id(),
                            'event' => 'password_reset_sent',
                            'description' => 'Password reset email sent to user',
                            'properties' => json_encode([
                                'email' => $record->email,
                                'sent_by' => Auth::user()?->name ?? 'System',
                                'sent_at' => now(),
                            ]),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Email reset password berhasil dikirim')
                            ->body('User akan menerima email dengan link untuk reset password.')
                            ->duration(5000)
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Gagal mengirim email')
                            ->body('Terjadi kesalahan saat mengirim email reset password.')
                            ->duration(5000)
                            ->send();
                    }
                }),
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
            ->title('User berhasil diperbarui')
            ->body('Data user telah berhasil diperbarui.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Bersihkan dan format data sebelum disimpan
        $data['name'] = trim($data['name']);
        $data['email'] = strtolower(trim($data['email']));

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan Perubahan'),
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}