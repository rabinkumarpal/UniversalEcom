<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Packages\PromotionEngine\Models\Promotion;

class AdminPromotionsController extends Controller
{
    /** @var string[] */
    private const TYPES = [
        'percentage', 'fixed', 'bogo', 'bundle', 'mix_match',
        'buy_x_get_y_bundle', 'countdown', 'spending_goal',
        'free_shipping', 'stock_scarcity',
    ];

    /** @var string[] */
    private const STATUSES = ['draft', 'active', 'paused', 'archived'];

    public function index(Request $request): View
    {
        $query = Promotion::withCount('usages')->orderBy('priority');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $promotions = $query->paginate(20)->withQueryString();

        $statusCounts = Promotion::groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
        $totalPromotionsCount = Promotion::count();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'statusCounts' => $statusCounts,
            'totalPromotionsCount' => $totalPromotionsCount,
        ]);
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $promo = Promotion::findOrFail($id);
        $newStatus = match ($promo->status) {
            'active' => 'paused',
            'paused', 'draft' => 'active',
            default => 'active',
        };

        $promo->update(['status' => $newStatus]);

        return back()->with('success', "Promotion '{$promo->name}' status updated to {$newStatus}.");
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => null,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePromotion($request);

        Promotion::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(5),
            'code' => $validated['code'] ? strtoupper($validated['code']) : null,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'stackable' => $request->boolean('stackable'),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'usage_limit_per_customer' => $validated['usage_limit_per_customer'] ?? null,
            'configuration' => $this->parseConfiguration($validated['configuration'] ?? '{}'),
        ]);

        return redirect()->route('admin.promotions.index')
            ->with('success', "Promotion \"{$validated['name']}\" created successfully.");
    }

    public function edit(int $id): View
    {
        $promotion = Promotion::findOrFail($id);

        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $promotion = Promotion::findOrFail($id);
        $validated = $this->validatePromotion($request);

        $promotion->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ? strtoupper($validated['code']) : null,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'stackable' => $request->boolean('stackable'),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'usage_limit_per_customer' => $validated['usage_limit_per_customer'] ?? null,
            'configuration' => $this->parseConfiguration($validated['configuration'] ?? '{}'),
        ]);

        return redirect()->route('admin.promotions.index')
            ->with('success', "Promotion \"{$promotion->name}\" updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->update(['status' => 'archived']);

        return redirect()->route('admin.promotions.index')
            ->with('success', "Promotion \"{$promotion->name}\" archived.");
    }

    /**
     * Shared validation rules.
     *
     * @return array<string, mixed>
     */
    private function validatePromotion(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
            'status' => ['required', 'string', 'in:'.implode(',', self::STATUSES)],
            'priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'configuration' => ['nullable', 'string'],
        ]);
    }

    /**
     * Parse JSON configuration field; fall back to empty array on invalid JSON.
     *
     * @return array<string, mixed>
     */
    private function parseConfiguration(string $raw): array
    {
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
