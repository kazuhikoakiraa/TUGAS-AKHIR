<?php

namespace App\Providers;
use App\Models\PoSupplier;
use App\Models\Invoice;
use App\Observers\TransaksiKeuanganObserver;
use App\Enums\PoStatus;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        PoSupplier::updated(function (PoSupplier $poSupplier) {
            // Cek apakah status berubah menjadi approved
            if ($poSupplier->isDirty('status_po') && $poSupplier->status_po === PoStatus::APPROVED) {
                TransaksiKeuanganObserver::handlePoSupplierApproved($poSupplier);
            }
        });

        // Observer untuk Invoice - ketika status berubah ke paid
        Invoice::updated(function (Invoice $invoice) {
            // Cek apakah status berubah menjadi paid
            if ($invoice->isDirty('status') && $invoice->status === 'paid') {
                TransaksiKeuanganObserver::handleInvoicePaid($invoice);
            }
        });

        // Observer untuk Invoice - ketika langsung dibuat dengan status paid
        Invoice::created(function (Invoice $invoice) {
            if ($invoice->status === 'paid') {
                TransaksiKeuanganObserver::handleInvoicePaid($invoice);
            }
        });
    }
}
