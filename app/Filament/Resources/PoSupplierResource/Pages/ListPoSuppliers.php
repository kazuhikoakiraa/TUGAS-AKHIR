<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use App\Enums\PoStatus;
use App\Models\PoSupplier;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPoSuppliers extends ListRecords
{
    protected static string $resource = PoSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Supplier PO')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge($this->getTabCount())
                ->badgeColor('primary'),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_po', PoStatus::DRAFT->value))
                ->badge($this->getTabCount(PoStatus::DRAFT))
                ->badgeColor('gray'),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_po', PoStatus::PENDING->value))
                ->badge($this->getTabCount(PoStatus::PENDING))
                ->badgeColor('warning'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_po', PoStatus::APPROVED->value))
                ->badge($this->getTabCount(PoStatus::APPROVED))
                ->badgeColor('success'),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_po', PoStatus::REJECTED->value))
                ->badge($this->getTabCount(PoStatus::REJECTED))
                ->badgeColor('danger'),
        ];
    }

    /**
     * Get count for tab badge - Optimized version
     */
    private function getTabCount(?PoStatus $status = null): int
    {
        // Get all counts at once for better performance
        $counts = $this->getAllStatusCounts();

        if (!$status) {
            return array_sum($counts);
        }

        return $counts[$status->value] ?? 0;
    }

    /**
     * Get all status counts with single query
     */
    private function getAllStatusCounts(): array
    {
        static $counts = null;

        if ($counts === null) {
            $counts = PoSupplier::query()
                ->selectRaw('status_po, COUNT(*) as count')
                ->groupBy('status_po')
                ->pluck('count', 'status_po')
                ->toArray();
        }

        return $counts;
    }

    /**
     * Alternative with caching for even better performance
     * Uncomment and use this version if you have high traffic
     */
    /*
    private function getAllStatusCounts(): array
    {
        return cache()->remember('po_supplier_status_counts', now()->addMinutes(5), function () {
            return PoSupplier::query()
                ->selectRaw('status_po, COUNT(*) as count')
                ->groupBy('status_po')
                ->pluck('count', 'status_po')
                ->toArray();
        });
    }
    */
}
