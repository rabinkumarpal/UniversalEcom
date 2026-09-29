<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        // Static core routes
        $urls[] = [
            'loc' => url('/'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        $urls[] = [
            'loc' => route('storefront.catalog'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];

        // Content & Static Pages
        $contentRoutes = ['content.faq', 'content.about', 'content.contact', 'content.calculators', 'content.knowledge.index'];
        foreach ($contentRoutes as $routeName) {
            $urls[] = [
                'loc' => route($routeName),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // Knowledge Articles
        $articles = [
            'understanding-cement-grades-opc-vs-ppc',
            'tmt-steel-bars-fe-500d-vs-fe-550d-selection-guide',
            'essential-waterproofing-techniques-for-roof-slabs',
        ];
        foreach ($articles as $slug) {
            $urls[] = [
                'loc' => route('content.knowledge.article', $slug),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        // Active / Published Products
        $products = Product::whereIn('status', ['published', 'active'])->orderBy('updated_at', 'desc')->get();
        foreach ($products as $product) {
            $urls[] = [
                'loc' => route('storefront.product', $product->slug),
                'lastmod' => $product->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
        }

        // Active Categories
        $categories = Category::where('status', 'active')->get();
        foreach ($categories as $category) {
            $urls[] = [
                'loc' => route('storefront.catalog', ['category' => $category->slug]),
                'lastmod' => $category->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $entry) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8').'</loc>';
            $xml .= '<lastmod>'.$entry['lastmod'].'</lastmod>';
            $xml .= '<changefreq>'.$entry['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$entry['priority'].'</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
