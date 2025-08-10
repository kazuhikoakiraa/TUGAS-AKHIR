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
            // Get default bank account or first account
            $account = RekeningBank::first();

            if (!$account) {
                Log::warning('No bank account available to record PO Supplier transaction', [
                    'po_supplier_id' => $poSupplier->id
                ]);
                return;
            }

            // Check if transaction has already been recorded
            $existingTransaction = TransaksiKeuangan::where('id_po_supplier', $poSupplier->id)->first();

            if ($existingTransaction) {
                Log::info('Transaction for PO Supplier already exists', [
                    'po_supplier_id' => $poSupplier->id,
                    'transaction_id' => $existingTransaction->id
                ]);
                return;
            }

            // Create expense transaction
            TransaksiKeuangan::create([
                'id_po_supplier' => $poSupplier->id,
                'id_rekening' => $account->id,
                'tanggal' => $poSupplier->tanggal_po,
                'jenis' => 'pengeluaran',
                'jumlah' => $poSupplier->total,
                'keterangan' => "Payment for Supplier PO {$poSupplier->nomor_po} - {$poSupplier->supplier->nama}"
            ]);

            Log::info('Expense transaction successfully recorded for PO Supplier', [
                'po_supplier_id' => $poSupplier->id,
                'total' => $poSupplier->total
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record transaction for PO Supplier', [
                'po_supplier_id' => $poSupplier->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Observer for Invoice - when status changes to paid
     */
    public static function handleInvoicePaid(Invoice $invoice)
    {
        try {
            // Use bank account selected in invoice or default
            $account = $invoice->rekeningBank ?? RekeningBank::first();

            if (!$account) {
                Log::warning('No bank account available to record Invoice transaction', [
                    'invoice_id' => $invoice->id
                ]);
                return;
            }

            // Check if transaction has already been recorded
            $existingTransaction = TransaksiKeuangan::whereHas('invoice', function ($query) use ($invoice) {
                $query->where('id', $invoice->id);
            })->first();

            if ($existingTransaction) {
                Log::info('Transaction for Invoice already exists', [
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $existingTransaction->id
                ]);
                return;
            }

            $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer not found';

            // Create income transaction
            TransaksiKeuangan::create([
                'id_po_supplier' => null, // Not related to PO Supplier
                'id_rekening' => $account->id,
                'tanggal' => $invoice->tanggal,
                'jenis' => 'pemasukan',
                'jumlah' => $invoice->grand_total,
                'keterangan' => "Payment for Invoice {$invoice->nomor_invoice} - {$customerName}"
            ]);

            Log::info('Income transaction successfully recorded for Invoice', [
                'invoice_id' => $invoice->id,
                'total' => $invoice->grand_total
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record transaction for Invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
