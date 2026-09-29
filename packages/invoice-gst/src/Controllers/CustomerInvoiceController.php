<?php

namespace Packages\InvoiceGst\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\InvoiceGst\Models\GstInvoice;
use Packages\InvoiceGst\Services\GstInvoiceService;

class CustomerInvoiceController extends Controller
{
    public function show(Request $request, string $invoiceNumber): View
    {
        $invoice = GstInvoice::where('invoice_number', $invoiceNumber)
            ->with(['items.orderItem', 'order.user'])
            ->first();

        if (! $invoice) {
            // Check if this is an internal order invoice number from App\Models\Invoice
            $basicInvoice = Invoice::where('invoice_number', $invoiceNumber)
                ->with(['order.items.variant.product', 'order.user'])
                ->first();

            if ($basicInvoice && $basicInvoice->order) {
                // Generate or retrieve the statutory GST invoice for this order
                $invoice = app(GstInvoiceService::class)->generateForOrder($basicInvoice->order);
                $invoice->load(['items.orderItem', 'order.user']);
            }
        }

        if (! $invoice) {
            abort(404, 'Tax invoice not found.');
        }

        $user = $request->user();
        $isAdmin = $user && (
            $user->hasAnyRole([
                'admin', 'super-admin', 'super_admin', 'operations_manager',
                'order-manager', 'inventory-manager', 'delivery-manager', 'catalog-manager', 'staff',
            ]) ||
            $user->can('orders.view') ||
            $user->hasRole('admin')
        );

        // Security check: Only the order owner or authorized administrators can view the invoice
        if (($invoice->order->user_id !== $user?->id || ! $user) && ! $isAdmin) {
            abort(403, 'Unauthorized access to this tax invoice.');
        }

        return view('invoice-gst::tax-invoice', compact('invoice'));
    }
}
