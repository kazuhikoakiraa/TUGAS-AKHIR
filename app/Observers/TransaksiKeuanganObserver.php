<?php

namespace App\Observers;

use App\Models\TransaksiKeuangan;
use App\Models\PoSupplier;
use App\Models\Invoice;
use App\Models\RekeningBank;
use App\Enums\PoStatus;
use Illuminate\Support\Facades\Log;

class TransaksiKeuanganObserver
{
    /**
     * Observer for PoSupplier - when status changes to approved
     */
    public static function handlePoSupplierApproved(PoSupplier $poSupplier)
    {
        try {
            // Check if transaction has already been recorded
            $existingTransaction = TransaksiKeuangan::where('id_po_supplier', $poSupplier->id)->first();

            if ($existingTransaction) {
                Log::info('Transaction for PO Supplier already exists', [
                    'po_supplier_id' => $poSupplier->id,
                    'transaction_id' => $existingTransaction->id
                ]);
                return;
            }

            // Create expense transaction using model method
            TransaksiKeuangan::createFromPoSupplier($poSupplier);

            Log::info('Expense transaction successfully recorded for PO Supplier', [
                'po_supplier_id' => $poSupplier->id,
                'total' => $poSupplier->total
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record transaction for PO Supplier', [
                'po_supplier_id' => $poSupplier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Observer for Invoice - when status changes to paid
     */
    public static function handleInvoicePaid(Invoice $invoice)
    {
        try {
            // Check if transaction has already been recorded using referensi system
            $existingTransaction = TransaksiKeuangan::where('referensi_type', 'invoice')
                                                   ->where('referensi_id', $invoice->id)
                                                   ->first();

            if ($existingTransaction) {
                Log::info('Transaction for Invoice already exists', [
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $existingTransaction->id,
                    'nomor_invoice' => $invoice->nomor_invoice
                ]);
                return;
            }

            // Create income transaction using model method
            $transaction = TransaksiKeuangan::createFromInvoice($invoice);

            Log::info('Income transaction successfully recorded for Invoice', [
                'invoice_id' => $invoice->id,
                'transaction_id' => $transaction->id,
                'nomor_invoice' => $invoice->nomor_invoice,
                'total' => $invoice->grand_total,
                'account_id' => $transaction->id_rekening
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record transaction for Invoice', [
                'invoice_id' => $invoice->id,
                'nomor_invoice' => $invoice->nomor_invoice,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Optionally, you can throw the exception again if you want to prevent
            // the invoice status from changing when transaction recording fails
            // throw $e;
        }
    }

    /**
     * Method untuk cleanup transaksi jika invoice status berubah dari paid ke lainnya
     */
    public static function handleInvoiceUnpaid(Invoice $invoice)
    {
        try {
            $existingTransaction = TransaksiKeuangan::where('referensi_type', 'invoice')
                                                   ->where('referensi_id', $invoice->id)
                                                   ->first();

            if ($existingTransaction) {
                $existingTransaction->delete();

                Log::info('Transaction removed for unpaid invoice', [
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $existingTransaction->id,
                    'nomor_invoice' => $invoice->nomor_invoice
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to remove transaction for unpaid invoice', [
                'invoice_id' => $invoice->id,
                'nomor_invoice' => $invoice->nomor_invoice,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Method helper untuk validasi bank account
     */
    private static function getDefaultBankAccount(): ?RekeningBank
    {
        return RekeningBank::first();
    }

    /**
     * Method untuk bulk create transactions (jika diperlukan)
     */
    public static function bulkCreateFromInvoices(array $invoiceIds): int
    {
        $created = 0;

        foreach ($invoiceIds as $invoiceId) {
            $invoice = Invoice::find($invoiceId);

            if ($invoice && $invoice->isPaid()) {
                try {
                    self::handleInvoicePaid($invoice);
                    $created++;
                } catch (\Exception $e) {
                    Log::error('Bulk create failed for invoice', [
                        'invoice_id' => $invoiceId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $created;
    }
}
