<?php

// File: app/Http/Controllers/QuotationController.php

namespace App\Http\Controllers;

use App\Models\Penawaran;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    /**
     * Generate and download PDF for quotation
     */
    public function downloadPdf(Penawaran $quotation)
    {
        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'customer' => $quotation->customer,
        ]);

        $filename = "Quotation-{$quotation->nomor_penawaran}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Generate and stream PDF for quotation (for preview)
     */
    public function streamPdf(Penawaran $quotation)
    {
        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'customer' => $quotation->customer,
        ]);

        return $pdf->stream("Quotation-{$quotation->nomor_penawaran}.pdf");
    }
}
