<?php

namespace App\Http\Controllers\Web;

use App\Domain\Catalog\BulkCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBulkCatalogController extends Controller
{
    public function __construct(
        protected BulkCatalogService $bulkCatalogService
    ) {}

    public function index(): View
    {
        return view('admin.catalog.bulk');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|max:5120',
        ]);

        $file = $request->file('csv_file');
        $content = file_get_contents($file->getRealPath());

        $preview = $this->bulkCatalogService->previewCsv($content);

        // Store valid rows in session for the confirmation step
        session(['bulk_import_valid_rows' => $preview['valid_rows']]);

        return view('admin.catalog.bulk', compact('preview'));
    }

    public function commit(Request $request): RedirectResponse
    {
        $validRows = session('bulk_import_valid_rows');

        if (empty($validRows)) {
            return redirect()->route('admin.catalog.bulk')
                ->with('error', 'No pending preview found to commit. Please upload and preview a CSV file first.');
        }

        $result = $this->bulkCatalogService->commitCsv($validRows);
        session()->forget('bulk_import_valid_rows');

        return redirect()->route('admin.catalog.bulk')
            ->with('success', "Catalog bulk import successfully processed {$result['processed_count']} items with atomic transactional safety.");
    }

    public function sample(): Response
    {
        $csv = $this->bulkCatalogService->generateSampleCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="universal_catalog_template.csv"',
        ]);
    }

    public function export(): StreamedResponse
    {
        $csv = $this->bulkCatalogService->exportCatalogCsv();

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'catalog_export_'.date('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
