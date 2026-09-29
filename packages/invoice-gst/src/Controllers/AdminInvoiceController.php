<?php

namespace Packages\InvoiceGst\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\InvoiceGst\Models\GstInvoice;
use Packages\InvoiceGst\Services\GstInvoiceService;

class AdminInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = GstInvoice::with(['order.user'])->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('supply_type')) {
            $query->where('supply_type', $request->query('supply_type'));
        }

        if ($request->filled('is_b2b')) {
            $query->where('is_b2b', $request->boolean('is_b2b'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('buyer_gstin', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->latest('invoice_date')->paginate(20);

        // Aggregate Financial Metrics
        $totalInvoices = GstInvoice::count();
        $totalTaxable = GstInvoice::where('status', 'issued')->sum('taxable_amount');
        $totalCgst = GstInvoice::where('status', 'issued')->sum('cgst_amount');
        $totalSgst = GstInvoice::where('status', 'issued')->sum('sgst_amount');
        $totalIgst = GstInvoice::where('status', 'issued')->sum('igst_amount');
        $totalTax = $totalCgst + $totalSgst + $totalIgst;
        $totalRevenue = GstInvoice::where('status', 'issued')->sum('total_amount');

        return view('invoice-gst::admin.index', compact(
            'invoices',
            'totalInvoices',
            'totalTaxable',
            'totalTax',
            'totalCgst',
            'totalSgst',
            'totalIgst',
            'totalRevenue'
        ));
    }

    public function cancel(Request $request, int $id, GstInvoiceService $invoiceService): RedirectResponse
    {
        $invoice = GstInvoice::findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $invoiceService->cancelInvoice($invoice, $validated['reason']);

        return back()->with('warning', "Invoice {$invoice->invoice_number} marked as cancelled / credit note issued.");
    }
}
