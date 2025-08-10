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
     * Observer untuk PoSupplier - ketika status berubah ke approved
     */
    public static function handlePoSupplierApproved(PoSupplier $poSupplier)
    {
        try {
            // Ambil rekening bank default atau rekening pertama
            $rekening = RekeningBank::first();

            if (!$rekening) {
                Log::warning('Tidak ada rekening bank untuk mencatat transaksi PO Supplier', [
                    'po_supplier_id' => $poSupplier->id
                ]);
                return;
            }

            // Cek apakah transaksi sudah pernah dicatat
            $existingTransaction = TransaksiKeuangan::where('id_po_supplier', $poSupplier->id)->first();

            if ($existingTransaction) {
                Log::info('Transaksi untuk PO Supplier sudah ada', [
                    'po_supplier_id' => $poSupplier->id,
                    'transaksi_id' => $existingTransaction->id
                ]);
                return;
            }

            // Buat transaksi pengeluaran
            TransaksiKeuangan::create([
                'id_po_supplier' => $poSupplier->id,
                'id_rekening' => $rekening->id,
                'tanggal' => $poSupplier->tanggal_po,
                'jenis' => 'pengeluaran',
                'jumlah' => $poSupplier->total,
                'keterangan' => "Pembayaran PO Supplier {$poSupplier->nomor_po} - {$poSupplier->supplier->nama}"
            ]);

            Log::info('Transaksi pengeluaran berhasil dicatat untuk PO Supplier', [
                'po_supplier_id' => $poSupplier->id,
                'total' => $poSupplier->total
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal mencatat transaksi untuk PO Supplier', [
                'po_supplier_id' => $poSupplier->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Observer untuk Invoice - ketika status berubah ke paid
     */
    public static function handleInvoicePaid(Invoice $invoice)
    {
        try {
            // Gunakan rekening bank yang dipilih di invoice atau default
            $rekening = $invoice->rekeningBank ?? RekeningBank::first();

            if (!$rekening) {
                Log::warning('Tidak ada rekening bank untuk mencatat transaksi Invoice', [
                    'invoice_id' => $invoice->id
                ]);
                return;
            }

            // Cek apakah transaksi sudah pernah dicatat
            $existingTransaction = TransaksiKeuangan::whereHas('invoice', function ($query) use ($invoice) {
                $query->where('id', $invoice->id);
            })->first();

            if ($existingTransaction) {
                Log::info('Transaksi untuk Invoice sudah ada', [
                    'invoice_id' => $invoice->id,
                    'transaksi_id' => $existingTransaction->id
                ]);
                return;
            }

            $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer tidak ditemukan';

            // Buat transaksi pemasukan
            TransaksiKeuangan::create([
                'id_po_supplier' => null, // Tidak terkait dengan PO Supplier
                'id_rekening' => $rekening->id,
                'tanggal' => $invoice->tanggal,
                'jenis' => 'pemasukan',
                'jumlah' => $invoice->grand_total,
                'keterangan' => "Pembayaran Invoice {$invoice->nomor_invoice} - {$customerName}"
            ]);

            Log::info('Transaksi pemasukan berhasil dicatat untuk Invoice', [
                'invoice_id' => $invoice->id,
                'total' => $invoice->grand_total
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal mencatat transaksi untuk Invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
