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
                ->label('Edit Bank Account')
                ->icon('heroicon-o-pencil-square')
                ->modalWidth('2xl'),

            Actions\DeleteAction::make()
                ->label('Delete Bank Account')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete Bank Account')
                ->modalDescription('Are you sure you want to delete this bank account? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index')),

            Actions\Action::make('duplicate')
                ->label('Duplicate Account')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Duplicate Bank Account')
                ->modalDescription('Do you want to create a copy of this bank account?')
                ->modalSubmitActionLabel('Yes, Duplicate')
                ->action(function ($record) {
                    $newRecord = $record->replicate();
                    $newRecord->nomor_rekening = $record->nomor_rekening . '_copy';
                    $newRecord->save();

                    Notification::make()
                        ->success()
                        ->title('Account successfully duplicated')
                        ->body('Copy of bank account has been created with account number: ' . $newRecord->nomor_rekening)
                        ->duration(5000)
                        ->send();

                    return redirect()->to(static::getResource()::getUrl('edit', ['record' => $newRecord->id]));
                }),
        ];
    }
}
