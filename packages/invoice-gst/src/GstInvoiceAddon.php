<?php

namespace Packages\InvoiceGst;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;

class GstInvoiceAddon implements AddonInterface
{
    public function id(): string
    {
        return 'invoice-gst';
    }

    public function name(): string
    {
        return 'GST Tax Invoice & E-Invoicing Engine';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Statutory GST Tax Invoicing (Rule 46), Intra vs Inter-state tax splitting (CGST/SGST vs IGST), HSN/SAC code summary aggregation, B2B company GSTIN billing, and print-ready digital Tax Invoices.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.invoice_gst.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        // Addon boot hooks
    }
}
