@extends('layouts.storefront')

@section('title', $article['title'] . ' — Knowledge Hub')
@section('meta_description', $article['summary'])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumbs -->
    <nav class="flex text-xs text-slate-500 mb-6 gap-2">
        <a href="{{ route('storefront.home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <a href="{{ route('content.knowledge.index') }}" class="hover:text-indigo-600">Knowledge Hub</a>
        <span>/</span>
        <span class="text-slate-900 font-semibold truncate">{{ $article['title'] }}</span>
    </nav>

    <!-- Article Header -->
    <header class="mb-8">
        <div class="flex items-center gap-3 mb-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700">
                {{ $article['category'] }}
            </span>
            <span class="text-xs text-slate-400">•</span>
            <span class="text-xs text-slate-500">{{ $article['read_time'] }}</span>
            <span class="text-xs text-slate-400">•</span>
            <span class="text-xs text-slate-500">{{ date('M d, Y', strtotime($article['published_at'])) }}</span>
        </div>

        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
            {{ $article['title'] }}
        </h1>

        <p class="text-sm text-slate-600 mt-4 leading-relaxed font-medium">
            {{ $article['summary'] }}
        </p>
    </header>

    <!-- Main Content Body -->
    <article class="prose prose-slate max-w-none text-xs leading-relaxed text-slate-700 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
        {!! $article['content'] !!}

        <div class="mt-8 pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h4 class="font-bold text-slate-900 text-sm">Looking for certified commercial materials?</h4>
                <p class="text-xs text-slate-500">Shop verified brands with downloadable test certificates and bulk volume discounts.</p>
            </div>
            <a href="{{ route('storefront.catalog') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition whitespace-nowrap">
                Browse Platform Catalog &rarr;
            </a>
        </div>
    </article>

    <!-- Navigation Footer -->
    <div class="mt-8 flex justify-between items-center text-xs">
        <a href="{{ route('content.knowledge.index') }}" class="font-bold text-slate-500 hover:text-slate-900">
            &larr; Back to all guides
        </a>
        <a href="{{ route('content.calculators') }}" class="font-bold text-indigo-600 hover:text-indigo-800">
            Try Material Calculators &rarr;
        </a>
    </div>
</div>
@endsection

@push('schema')
@php
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'TechArticle',
        'headline' => $article['title'],
        'description' => $article['summary'],
        'datePublished' => $article['published_at'],
        'author' => [
            '@type' => 'Organization',
            'name' => 'Universal Commerce Engineering Team',
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
