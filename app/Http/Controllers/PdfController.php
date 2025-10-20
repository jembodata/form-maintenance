<?php

namespace App\Http\Controllers;

use App\Models\Checksheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function __invoke(Checksheet $order)
    {
        return Pdf::loadView('pdf', ['record' => $order])
            ->stream($order->tipe_proses. '.pdf');
    }
}
