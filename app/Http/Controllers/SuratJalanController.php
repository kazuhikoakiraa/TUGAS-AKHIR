<?php

namespace App\Http\Controllers;

use App\Models\SuratJalan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class SuratJalanController extends Controller
{
    /**
     * Generate PDF untuk surat jalan
     */
    public function generatePdf(SuratJalan $suratJalan)
    {
        try {
            // Load relasi yang diperlukan
            $suratJalan->load(['poCustomer.customer', 'user']);

            // Pastikan data lengkap
            if (!$suratJalan->poCustomer || !$suratJalan->poCustomer->customer) {
                abort(404, 'Data PO Customer atau Customer tidak ditemukan');
            }

            $data = [
                'suratJalan' => $suratJalan,
                'customer' => $suratJalan->poCustomer->customer,
                'po' => $suratJalan->poCustomer,
                'user' => $suratJalan->user,
                'company' => [
                    'name' => config('app.name', 'PT. Your Company'),
                    'address' => 'Alamat Perusahaan Anda',
                    'phone' => 'Telepon Perusahaan',
                    'email' => 'email@perusahaan.com',
                ],
                'generated_at' => now()->format('d F Y H:i:s'),
            ];

            $pdf = Pdf::loadView('pdf.surat-jalan', $data);
            $pdf->setPaper('A4', 'portrait');

            $filename = 'Surat_Jalan_' . str_replace(['/', ' '], '_', $suratJalan->nomor_surat_jalan) . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            abort(500, 'Gagal generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Preview PDF di browser
     */
    public function previewPdf(SuratJalan $suratJalan)
    {
        try {
            $suratJalan->load(['poCustomer.customer', 'user']);

            if (!$suratJalan->poCustomer || !$suratJalan->poCustomer->customer) {
                abort(404, 'Data PO Customer atau Customer tidak ditemukan');
            }

            $data = [
                'suratJalan' => $suratJalan,
                'customer' => $suratJalan->poCustomer->customer,
                'po' => $suratJalan->poCustomer,
                'user' => $suratJalan->user,
                'company' => [
                    'name' => config('app.name', 'PT. Your Company'),
                    'address' => 'Alamat Perusahaan Anda',
                    'phone' => 'Telepon Perusahaan',
                    'email' => 'email@perusahaan.com',
                ],
                'generated_at' => now()->format('d F Y H:i:s'),
            ];

            $pdf = Pdf::loadView('pdf.surat-jalan', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('preview_surat_jalan.pdf');

        } catch (\Exception $e) {
            Log::error('Error previewing PDF: ' . $e->getMessage());
            abort(500, 'Gagal preview PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get available PO Customers for API
     */
    public function getAvailablePoCustomers(Request $request)
    {
        try {
            $query = \App\Models\PoCustomer::with('customer')
                ->where('jenis_po', 'Produk')
                ->where('status_po', \App\Enums\PoStatus::APPROVED)
                ->whereDoesntHave('suratJalan');

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('nomor_po', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($customerQuery) use ($search) {
                          $customerQuery->where('nama', 'like', "%{$search}%");
                      });
                });
            }

            $poCustomers = $query->limit(20)->get();

            return response()->json([
                'success' => true,
                'data' => $poCustomers->map(function ($po) {
                    return [
                        'id' => $po->id,
                        'nomor_po' => $po->nomor_po,
                        'customer_nama' => $po->customer->nama ?? '',
                        'customer_alamat' => $po->customer->alamat ?? '',
                        'label' => "{$po->nomor_po} - {$po->customer->nama}",
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
