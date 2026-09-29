@extends('layouts.storefront')

@section('title', 'Frequently Asked Questions — Universal Commerce')
@section('meta_description', 'Find answers to common questions about bulk tier pricing, local depot delivery, multi-vendor orders, and GST invoicing.')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumbs -->
    <nav class="flex text-xs text-slate-500 mb-6 gap-2">
        <a href="{{ route('storefront.home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <span class="text-slate-900 font-semibold">Frequently Asked Questions</span>
    </nav>

    <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block mb-1">Help & Information</span>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Frequently Asked Questions</h1>
        <p class="text-xs text-slate-500 mt-2">
            Everything you need to know about our universal commerce platform, bulk pricing tiers, logistics, and multi-vendor fulfillment.
        </p>
    </div>

    <!-- Accordion List -->
    <div class="space-y-4" x-data="{ activeAccordion: null }">
        @foreach($faqs as $index => $faq)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition">
                <button type="button" @click="activeAccordion = activeAccordion === {{ $index }} ? null : {{ $index }}"
                        class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-slate-900 text-sm hover:text-indigo-600 transition">
                    <span class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-black flex-shrink-0">
                            {{ $index + 1 }}
                        </span>
                        {{ $faq['question'] }}
                    </span>
                    <svg class="w-5 h-5 text-slate-400 transform transition-transform duration-200"
                         :class="{ 'rotate-180 text-indigo-600': activeAccordion === {{ $index }} }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="activeAccordion === {{ $index }}" x-collapse class="px-5 pb-5 pt-1 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    {{ $faq['answer'] }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Contact Callout -->
    <div class="mt-12 p-8 bg-slate-900 rounded-2xl text-center text-white space-y-3">
        <h3 class="text-base font-bold">Have a custom question or large project requirement?</h3>
        <p class="text-xs text-slate-400 max-w-lg mx-auto">
            Our engineering and material logistics desk is available to assist contractors, builders, and developers with custom volume procurement.
        </p>
        <div class="pt-2">
            <a href="{{ route('content.contact') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow transition">
                Contact Procurement Desk &rarr;
            </a>
        </div>
    </div>
</div>
@endsection

@push('schema')
@php
    $mainEntities = [];
    foreach ($faqs as $faq) {
        $mainEntities[] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer'],
            ],
        ];
    }
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $mainEntities,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
