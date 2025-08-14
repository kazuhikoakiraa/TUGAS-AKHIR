<?php

namespace App\Http\Controllers;

use App\Models\SuratJalan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SuratJalanController extends Controller
{
    /**
     * Generate PDF for Surat Jalan
     */
    public function generatePDF(SuratJalan $suratJalan)
    {
        try {
            // Load relationships dengan error handling
            $suratJalan->load([
                'poCustomer' => function($query) {
                    $query->with(['customer', 'details']);
                },
                'user'
            ]);

            $po = $suratJalan->poCustomer;
            $customer = $po ? $po->customer : null;
            $user = $suratJalan->user;

            // Validate required data
            if (!$po) {
                throw new \Exception('PO Customer data not found');
            }

            if (!$customer) {
                throw new \Exception('Customer data not found');
            }

            // Company data (you can move this to config or database)
            $company = [
                'name' => config('app.company_name', 'PT. NAMA PERUSAHAAN'),
                'address' => config('app.company_address', 'Alamat Perusahaan, Kota, Provinsi, Kode Pos'),
                'phone' => config('app.company_phone', '+62 21 1234567'),
                'email' => config('app.company_email', 'info@company.com'),
                'website' => config('app.company_website', 'www.company.com'),
                'npwp' => config('app.company_npwp', '00.000.000.0-000.000'),
            ];

            $data = [
                'suratJalan' => $suratJalan,
                'po' => $po,
                'customer' => $customer,
                'user' => $user,
                'company' => $company,
                'generated_at' => Carbon::now()->format('d F Y H:i:s') . ' WIB'
            ];

            // Generate PDF dengan error handling
            $pdf = Pdf::loadView('pdf.surat-jalan', $data)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false, // Disable remote untuk keamanan
                    'defaultFont' => 'DejaVu Sans', // Font yang lebih kompatibel
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 15,
                    'margin_left' => 10,
                ]);

            // Clean filename
            $cleanNomor = preg_replace('/[^A-Za-z0-9\-]/', '-', $suratJalan->nomor_surat_jalan);
            $filename = 'Surat-Jalan-' . $cleanNomor . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            // Log error dengan detail
            Log::error('Error generating Surat Jalan PDF', [
                'surat_jalan_id' => $suratJalan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat PDF: ' . $e->getMessage());
        }
    }

    /**
     * Preview PDF in browser
     */
    public function previewPDF(SuratJalan $suratJalan)
    {
        try {
            // Load relationships
            $suratJalan->load([
                'poCustomer.customer',
                'poCustomer.details',
                'user'
            ]);

            $po = $suratJalan->poCustomer;
            $customer = $po->customer;
            $user = $suratJalan->user;

            // Company data
            $company = [
                'name' => config('app.company_name', 'PT. NAMA PERUSAHAAN'),
                'address' => config('app.company_address', 'Alamat Perusahaan, Kota, Provinsi, Kode Pos'),
                'phone' => config('app.company_phone', '+62 21 1234567'),
                'email' => config('app.company_email', 'info@company.com'),
                'website' => config('app.company_website', 'www.company.com'),
                'npwp' => config('app.company_npwp', '00.000.000.0-000.000'),
            ];

            $data = [
                'suratJalan' => $suratJalan,
                'po' => $po,
                'customer' => $customer,
                'user' => $user,
                'company' => $company,
                'generated_at' => Carbon::now()->format('d F Y H:i:s') . ' WIB'
            ];

            // Return view for preview
            return view('pdf.surat-jalan', $data);

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat membuat preview: ' . $e->getMessage());
        }
    }
}