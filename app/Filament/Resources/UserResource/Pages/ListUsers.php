<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah User')
                ->icon('heroicon-o-plus')
                ->modalWidth('2xl'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua User')
                ->badge(fn () => $this->getModel()::count()),

            'recent' => Tab::make('Terbaru')
                ->badge(fn () => $this->getModel()::where('created_at', '>=', now()->subDays(7))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),

            'verified' => Tab::make('Terverifikasi')
                ->badge(fn () => $this->getModel()::whereNotNull('email_verified_at')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('email_verified_at')),
        ];
    }
}