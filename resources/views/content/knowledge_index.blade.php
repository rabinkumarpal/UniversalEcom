@extends('layouts.storefront')

@section('title', 'Knowledge Hub & Technical Construction Guides — Universal Commerce')
@section('meta_description', 'Explore authoritative technical engineering guides, material selection standards, BIS compliance checklists, and structural comparisons.')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumbs -->
    <nav class="flex text-xs text-slate-500 mb-6 gap-2">
        <a href="{{ route('storefront.home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <span class="text-slate-900 font-semibold">Knowledge Hub</span>
    </nav>

    <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block mb-1">Technical Library</span>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Construction & Material Knowledge Hub</h1>
        <p class="text-xs text-slate-500 mt-2">
            In-depth guides, IS code compliance checklists, and comparative benchmarks written for civil engineers, site supervisors, and builders.
        </p>
    </div>

    <!-- Guides Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($articles as $article)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700">
                            {{ $article['category'] }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">
                            {{ $article['read_time'] }}
                        </span>
                    </div>

                    <h2 class="text-base font-black text-slate-900 leading-snug hover:text-indigo-600 transition">
                        <a href="{{ route('content.knowledge.article', $article['slug']) }}">
                            {{ $article['title'] }}
                        </a>
                    </h2>

                    <p class="text-xs text-slate-500 mt-3 leading-relaxed">
                        {{ $article['summary'] }}
                    </p>
                </div>

                <div class="p-6 pt-0 border-t border-slate-100 flex items-center justify-between mt-4">
                    <span class="text-[11px] text-slate-400 font-mono">
                        Published {{ date('M Y', strtotime($article['published_at'])) }}
                    </span>
                    <a href="{{ route('content.knowledge.article', $article['slug']) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">
                        Read Guide &rarr;
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Interactive Calculators Banner -->
    <div class="mt-12 p-8 bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl flex flex-col md:flex-row items-center justify-between gap-6 shadow-md">
        <div>
            <h3 class="text-lg font-black">Plan Your Next Structural Pour</h3>
            <p class="text-xs text-slate-300 mt-1 max-w-xl">
                Use our automated material calculators to calculate exact cement bags, rebar, sand, and brickwork requirements before placing order.
            </p>
        </div>
        <a href="{{ route('content.calculators') }}" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow transition whitespace-nowrap">
            Launch Material Estimators &rarr;
        </a>
    </div>
</div>
@endsection
