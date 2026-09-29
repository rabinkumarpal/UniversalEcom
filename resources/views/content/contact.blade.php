@extends('layouts.storefront')

@section('title', 'Contact & Depot Logistics — Universal Commerce')
@section('meta_description', 'Contact our commercial procurement desk, technical engineering team, or local depot logistics operations.')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumbs -->
    <nav class="flex text-xs text-slate-500 mb-6 gap-2">
        <a href="{{ route('storefront.home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <span class="text-slate-900 font-semibold">Contact & Support</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Contact Information & Depots -->
        <div class="lg:col-span-5 space-y-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block mb-1">Get in Touch</span>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Support & Procurement Desk</h1>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Have questions about large commercial orders, project site scheduling, technical test certificates, or becoming a marketplace vendor? Reach out to our operations team.
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4 text-xs">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Email Support</h4>
                        <p class="text-slate-500">support@universal-ecom.test</p>
                        <p class="text-[11px] text-slate-400">Response within 2-4 business hours</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Site Logistics Hotline</h4>
                        <p class="text-slate-500 font-mono font-bold">+91 80 2345 6789</p>
                        <p class="text-[11px] text-slate-400">Monday – Saturday: 6:00 AM – 8:00 PM</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Central Depot Hub</h4>
                        <p class="text-slate-600">Plot 12-B, Industrial Logistics Yard, Outer Ring Road, Bengaluru - 560068</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="lg:col-span-7 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Send a Project Inquiry</h3>
            <p class="text-xs text-slate-500 mb-6">Fill in your requirements below and our commercial sales engineer will reach out.</p>

            <form method="POST" action="{{ route('content.contact.submit') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Your Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Inquiry Subject</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Bulk Cement Procurement"
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Project Requirements & Site Location</label>
                    <textarea name="message" rows="4" required placeholder="Describe product quantities, delivery location, and tentative schedule..."
                              class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('message') }}</textarea>
                </div>

                <div>
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs shadow-md transition">
                        Submit Inquiry &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
