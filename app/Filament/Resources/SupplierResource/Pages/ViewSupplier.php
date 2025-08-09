<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Supplier')
                ->icon('heroicon-o-pencil-square')
                ->modalWidth('2xl'),

            Actions\DeleteAction::make()
                ->label('Hapus Supplier')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Supplier')
                ->modalDescription('Apakah Anda yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index')),

            Actions\Action::make('viewPurchaseOrders')
                ->label('Lihat Purchase Orders')
                ->icon('heroicon-o-shopping-bag')
                ->color('info')
                ->url(fn ($record) => route('filament.admin.resources.po-suppliers.index', [
                    'tableFilters' => [
                        'supplier' => [
                            'value' => $record->id,
                        ],
                    ],
                ]))
                ->visible(fn ($record) => $record->poSuppliers()->exists()),
        ];
    }
}