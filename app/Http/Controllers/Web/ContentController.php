<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    /**
     * Get published knowledge articles.
     */
    protected function getArticles(): array
    {
        return [
            'understanding-cement-grades-opc-vs-ppc' => [
                'slug' => 'understanding-cement-grades-opc-vs-ppc',
                'title' => 'Understanding Cement Grades: OPC 53 vs. PPC in Modern Construction',
                'category' => 'Civil & Structural',
                'read_time' => '5 min read',
                'summary' => 'Discover the key compressive strength, heat of hydration, and curing differences between Ordinary Portland Cement (OPC) and Portland Pozzolana Cement (PPC).',
                'published_at' => '2026-03-15',
                'content' => '
                    <p class="mb-4">Selecting the correct grade of cement is critical to the structural longevity and crack resistance of any civil structure. In modern Indian and global civil engineering standards, two primary types dominate structural and plastering applications: Ordinary Portland Cement (OPC 53 Grade) and Portland Pozzolana Cement (PPC).</p>

                    <h3 class="text-base font-bold text-slate-900 mt-6 mb-2">1. Ordinary Portland Cement (OPC 53)</h3>
                    <p class="mb-3">OPC 53 grade achieves a 28-day characteristic compressive strength of 53 MPa (megapascals). Its primary advantages include fast setting time and high early strength gain, making it the ideal choice for high-rise columns, post-tensioned slabs, precast concrete, and heavy infrastructure projects where formwork removal needs to be accelerated.</p>
                    <p class="mb-4">However, OPC generates a high heat of hydration during setting, which increases the likelihood of thermal shrinkage cracks if intensive water curing is not maintained for at least 14 days.</p>

                    <h3 class="text-base font-bold text-slate-900 mt-6 mb-2">2. Portland Pozzolana Cement (PPC)</h3>
                    <p class="mb-3">PPC incorporates fly ash (pozzolanic material) from thermal power stations (typically 15% to 35% by mass). While PPC gains early strength slightly slower than OPC 53, its 28-day and ultimate 90-day compressive strength matches or exceeds OPC due to secondary C-S-H (Calcium Silicate Hydrate) gel formation.</p>
                    <p class="mb-4">PPC exhibits significantly lower heat of hydration, pore refinement that blocks water permeation, and exceptional chemical resistance against sulfates and chloride attacks in coastal or groundwater-exposed foundations.</p>

                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl my-6 text-xs text-amber-950 space-y-1">
                        <strong>Engineering Rule of Thumb:</strong>
                        <p>Use <strong>PPC</strong> for residential roof slabs, mass foundations, brick masonry mortar, and internal/external plastering to minimize shrinkage cracks. Use <strong>OPC 53</strong> for heavily loaded commercial RCC columns, shear walls, and precast girders.</p>
                    </div>
                ',
                'related_category' => 'cement-aggregates',
            ],
            'tmt-steel-bars-fe-500d-vs-fe-550d-selection-guide' => [
                'slug' => 'tmt-steel-bars-fe-500d-vs-fe-550d-selection-guide',
                'title' => 'TMT Steel Rebar: Fe-500D vs. Fe-550D Selection Guide for Seismic Zones',
                'category' => 'Structural Steel',
                'read_time' => '6 min read',
                'summary' => 'A technical comparative evaluation of yield stress, percentage elongation, and seismic earthquake resilience between Fe-500D and Fe-550D reinforcement rebar.',
                'published_at' => '2026-03-20',
                'content' => '
                    <p class="mb-4">Thermo-Mechanically Treated (TMT) steel rebar forms the tensile backbone of reinforced cement concrete (RCC). Selecting the appropriate grade according to IS 1786 specifications ensures both load-bearing capacity and ductile ductility during extreme wind or earthquake events.</p>

                    <h3 class="text-base font-bold text-slate-900 mt-6 mb-2">The Importance of the "D" Suffix (Ductility)</h3>
                    <p class="mb-4">The "D" in Fe-500D and Fe-550D stands for high ductility. In seismic zones (Zones III, IV, and V), standard Fe-500 rebar provides only 12% minimum elongation before fracture. In contrast, "D" series rebar guarantees a minimum elongation of 16% for Fe-500D and 14.5% for Fe-550D, absorbing cyclic shock energy and preventing sudden brittle failure during earthquakes.</p>

                    <h3 class="text-base font-bold text-slate-900 mt-6 mb-2">Yield Stress Comparison</h3>
                    <ul class="list-disc pl-5 mb-4 space-y-2 text-xs">
                        <li><strong>Fe-500D:</strong> Minimum yield stress of 500 N/mm² (MPa), minimum ultimate tensile strength of 565 N/mm². Higher elongation (16%) ensures superior bendability and seismic safety for residential and institutional frames.</li>
                        <li><strong>Fe-550D:</strong> Minimum yield stress of 550 N/mm² (MPa). Yields 10% higher load capacity per unit cross-sectional area, reducing overall steel tonnage by 4% to 8% in heavy commercial bridges, high-rises, and industrial warehouses.</li>
                    </ul>

                    <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl my-6 text-xs text-indigo-950">
                        <strong>Certification Checklist:</strong> Always verify that rebar bears embossed manufacturer marks, IS 1786 compliance, and comes with a manufacturer test certificate (MTC) verifying sulfur and phosphorus limits below 0.040%.
                    </div>
                ',
                'related_category' => 'steel-rebar',
            ],
            'essential-waterproofing-techniques-for-roof-slabs' => [
                'slug' => 'essential-waterproofing-techniques-for-roof-slabs',
                'title' => 'Essential Waterproofing Techniques for Concrete Roof Slabs & Basements',
                'category' => 'Chemicals & Coatings',
                'read_time' => '4 min read',
                'summary' => 'Step-by-step methods for surface preparation, elastomeric coatings, polymer-modified mortars, and waterproofing membrane installation.',
                'published_at' => '2026-04-02',
                'content' => '
                    <p class="mb-4">Water ingress is the single greatest catalyst for rebar corrosion, concrete spalling, and aesthetic degradation in residential buildings. Modern polymer technology has evolved beyond basic tar felts into advanced polyurethane and acrylic elastomeric systems.</p>

                    <h3 class="text-base font-bold text-slate-900 mt-6 mb-2">1. Surface Preparation & Crack Repair</h3>
                    <p class="mb-4">90% of waterproofing failures stem from inadequate surface preparation. All dust, laitance, oil, and loose aggregate must be mechanical ground or pressure-washed. Non-structural hairline cracks should be opened into a "V" groove and sealed with a polymer-modified mortar or polyurethane sealant.</p>

                    <h3 class="text-base font-bold text-slate-900 mt-6 mb-2">2. Multi-Coat Elastomeric Application</h3>
                    <p class="mb-4">Apply a deep-penetrating primer coat followed by two perpendicular coats of high-build elastomeric waterproof coating reinforced with non-woven polypropylene mesh at critical 90-degree parapet wall corners. Ensure a minimum dry film thickness (DFT) of 1.2 mm to accommodate thermal expansion cycles.</p>
                ',
                'related_category' => 'paints-waterproofing',
            ],
        ];
    }

    /**
     * Display the FAQ page with Schema.org FAQPage structured data.
     */
    public function faq(): View
    {
        $faqs = [
            [
                'question' => 'How does bulk tiered pricing work on Universal Commerce?',
                'answer' => 'Our platform calculates server-authoritative quantity discounts automatically based on the volume ordered. As your order quantity reaches preset volume tiers (e.g. 10+, 50+, or 100+ units), the lower unit rate is instantly applied to your active cart.',
                'category' => 'Ordering & Pricing',
            ],
            [
                'question' => 'Are product prices inclusive of GST and local taxes?',
                'answer' => 'Yes. All displayed prices in the catalog and checkout include applicable GST (such as 18% for steel or 28% for cement). A formal, tax-compliant GST tax invoice with HSN/SAC codes is generated upon order confirmation.',
            ],
            [
                'question' => 'How does multi-vendor order fulfillment operate?',
                'answer' => 'You can add items from multiple approved vendors to a single cart and check out in one payment. Behind the scenes, our engine automatically splits the order into sub-orders for each vendor, ensuring direct dispatch from the closest logistics depot while maintaining one unified order tracking timeline for you.',
                'category' => 'Vendor Marketplace',
            ],
            [
                'question' => 'What delivery zones and pincodes are currently serviceable?',
                'answer' => 'We currently provide scheduled and express logistics across major metropolitan pincodes (including 560001, 560002, 560025, 560038, 560068). You can change your delivery location anytime via the header pincode switcher to check real-time stock availability.',
                'category' => 'Delivery & Logistics',
            ],
            [
                'question' => 'What payment methods are supported for site deliveries?',
                'answer' => 'We support Cash on Delivery (COD), platform Digital Wallet settlement with 2% automatic loyalty cashback, and direct commercial accounts for approved contractors.',
                'category' => 'Ordering & Pricing',
            ],
            [
                'question' => 'Can I request delivery by crane or hydraulic forklift for heavy materials?',
                'answer' => 'Yes. For bulk palletized deliveries of bricks, cement bags, or structural steel, you can select specialized heavy site unloading options during checkout or qualify for free heavy delivery with our spending goal promotions.',
                'category' => 'Delivery & Logistics',
            ],
        ];

        return view('content.faq', compact('faqs'));
    }

    /**
     * Display corporate About page.
     */
    public function about(): View
    {
        return view('content.about');
    }

    /**
     * Display Contact and Depot locations page.
     */
    public function contact(): View
    {
        return view('content.contact');
    }

    /**
     * Handle contact inquiry form submission.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        return back()->with('success', 'Your inquiry has been received. A representative will contact you within 2 business hours.');
    }

    /**
     * Display Interactive Material Estimators & Calculators.
     */
    public function calculators(): View
    {
        return view('content.calculators');
    }

    /**
     * Display the Knowledge Hub index.
     */
    public function knowledgeIndex(): View
    {
        $articles = $this->getArticles();

        return view('content.knowledge_index', compact('articles'));
    }

    /**
     * Display an individual technical knowledge article.
     */
    public function knowledgeArticle(string $slug): View
    {
        $articles = $this->getArticles();

        if (! isset($articles[$slug])) {
            abort(404, 'Knowledge guide article not found');
        }

        $article = $articles[$slug];

        return view('content.knowledge_article', compact('article'));
    }
}
