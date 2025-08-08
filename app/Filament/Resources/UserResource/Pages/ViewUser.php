<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit User')
                ->icon('heroicon-o-pencil-square')
                ->modalWidth('2xl'),

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

            Actions\Action::make('sendVerificationEmail')
                ->label('Kirim Verifikasi Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Kirim Email Verifikasi')
                ->modalDescription('Email verifikasi akan dikirim ke alamat email user.')
                ->modalSubmitActionLabel('Ya, Kirim Email')
                ->visible(fn ($record) => !$record->email_verified_at)
                ->action(function ($record) {
                    try {
                        // Send email verification notification
                        $record->sendEmailVerificationNotification();

                        // Log activity
                        ActivityLog::create([
                            'subject_type' => get_class($record),
                            'subject_id' => $record->id,
                            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                            'causer_id' => Auth::id(),
                            'event' => 'verification_email_sent',
                            'description' => 'Email verification sent to user',
                            'properties' => json_encode([
                                'email' => $record->email,
                                'sent_by' => Auth::user()?->name ?? 'System',
                                'sent_at' => now(),
                            ]),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Email verifikasi berhasil dikirim')
                            ->body('User akan menerima email untuk memverifikasi alamat email mereka.')
                            ->duration(5000)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal mengirim email')
                            ->body('Terjadi kesalahan saat mengirim email verifikasi.')
                            ->duration(5000)
                            ->send();
                    }
                }),

            Actions\Action::make('verifyEmailManually')
                ->label('Verifikasi Manual')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verifikasi Email Manual')
                ->modalDescription('Email user akan ditandai sebagai terverifikasi secara manual.')
                ->modalSubmitActionLabel('Ya, Verifikasi')
                ->visible(fn ($record) => !$record->email_verified_at)
                ->action(function ($record) {
                    $record->update([
                        'email_verified_at' => now()
                    ]);

                    // Log activity
                    ActivityLog::create([
                        'subject_type' => get_class($record),
                        'subject_id' => $record->id,
                        'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                        'causer_id' => Auth::id(),
                        'event' => 'email_verified_manually',
                        'description' => 'Email verified manually by admin',
                        'properties' => json_encode([
                            'email' => $record->email,
                            'verified_by' => Auth::user()?->name ?? 'System',
                            'verified_at' => now(),
                        ]),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Email berhasil diverifikasi')
                        ->body('Email user telah berhasil diverifikasi secara manual.')
                        ->duration(5000)
                        ->send();
                }),
        ];
    }


}
