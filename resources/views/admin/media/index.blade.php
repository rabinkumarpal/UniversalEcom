@extends('layouts.admin')

@section('title', 'Media & Assets')
@section('header_title', 'Media & Asset Library')
@section('header_subtitle', 'Upload, search, organize, and copy URLs for all site assets across products, banners, and documents')

@section('content')
<div class="space-y-6">

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $err)
                <div>⚠ {{ $err }}</div>
            @endforeach
        </div>
    @endif

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Assets</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($totalCount) }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Stored in public storage</div>
        </div>
        <div class="bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Storage Used</div>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $formattedTotalSize }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">App public disk</div>
        </div>
        <div class="bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Images</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($imagesCount) }}</div>
            <div class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-1">PNG, JPG, WEBP, SVG</div>
        </div>
        <div class="bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Documents &amp; Other</div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($documentsCount) }}</div>
            <div class="text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-1">PDFs, certificates, specs</div>
        </div>
    </div>

    {{-- Upload Card --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors">
        <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Upload New Assets</h3>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Select Files (Images, PDFs, Documents up to 20MB)</label>
                    <input type="file" name="files[]" multiple required
                        class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900 cursor-pointer border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-900 p-1">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Category / Folder</label>
                    <select name="folder" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="general">General</option>
                        <option value="products">Products</option>
                        <option value="banners">Banners &amp; Promos</option>
                        <option value="documents">Documents &amp; Certificates</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload Selected Files
                </button>
            </div>
        </form>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="flex flex-col sm:flex-row justify-between gap-4">
        <form method="GET" action="{{ route('admin.media.index') }}" class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by filename…"
                class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none w-60">
            <select name="folder" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Folders</option>
                <option value="general" @selected(request('folder') === 'general')>General</option>
                <option value="products" @selected(request('folder') === 'products')>Products</option>
                <option value="banners" @selected(request('folder') === 'banners')>Banners</option>
                <option value="documents" @selected(request('folder') === 'documents')>Documents</option>
                @foreach($folders as $f)
                    @if(!in_array($f, ['general','products','banners','documents']))
                        <option value="{{ $f }}" @selected(request('folder') === $f)>{{ ucfirst($f) }}</option>
                    @endif
                @endforeach
            </select>
            <select name="type" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Types</option>
                <option value="images" @selected(request('type') === 'images')>Images Only</option>
                <option value="documents" @selected(request('type') === 'documents')>Documents Only</option>
            </select>
            @if(request('search') || request('folder') || request('type'))
                <a href="{{ route('admin.media.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-xl hover:bg-slate-200 transition">Reset</a>
            @endif
            <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-700 text-white text-xs rounded-xl hover:bg-slate-700 transition">Search</button>
        </form>
    </div>

    {{-- Media Assets Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @forelse($assets as $asset)
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition flex flex-col justify-between group">
                {{-- Preview Box --}}
                <div class="relative aspect-square bg-slate-100 dark:bg-slate-900 overflow-hidden flex items-center justify-center">
                    @if($asset->isImage())
                        <img src="{{ $asset->url }}" alt="{{ $asset->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                    @elseif($asset->isPdf())
                        <div class="flex flex-col items-center justify-center p-4 text-center">
                            <span class="text-3xl">📄</span>
                            <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400 mt-1">PDF Document</span>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center p-4 text-center">
                            <span class="text-3xl">📁</span>
                            <span class="text-[10px] font-mono text-slate-500 mt-1">{{ pathinfo($asset->name, PATHINFO_EXTENSION) }}</span>
                        </div>
                    @endif

                    {{-- Folder Badge --}}
                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wide bg-slate-900/80 text-white backdrop-blur-xs">
                        {{ $asset->folder }}
                    </span>
                </div>

                {{-- Asset Info --}}
                <div class="p-3 space-y-1.5 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white text-xs truncate" title="{{ $asset->name }}">
                            {{ $asset->name }}
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                            <span>{{ $asset->formattedSize() }}</span>
                            @if($asset->dimensions)
                                <span>•</span>
                                <span>{{ $asset->dimensions }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-900 flex items-center justify-between gap-2">
                        <button type="button" onclick="copyUrl('{{ $asset->url }}', this)" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                            <span>Copy URL</span>
                        </button>
                        <div class="flex items-center gap-2">
                            <a href="{{ $asset->url }}" target="_blank" class="text-[10px] text-slate-500 hover:text-slate-800 dark:hover:text-slate-200" title="Open in new tab">
                                ↗
                            </a>
                            <form method="POST" action="{{ route('admin.media.destroy', $asset->id) }}" onsubmit="return confirm('Delete this asset? It will be removed from disk.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-[10px] font-bold text-rose-600 dark:text-rose-400 hover:underline" title="Delete asset">
                                    ✕
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center shadow-xs">
                <div class="text-3xl mb-2">🖼</div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No media assets found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Upload images, documents, or banners using the upload box above.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($assets->hasPages())
        <div class="flex justify-center pt-4">
            {{ $assets->links() }}
        </div>
    @endif

</div>

<script>
function copyUrl(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400">✓ Copied!</span>';
        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 2000);
    }).catch(err => {
        alert('Could not copy URL: ' + url);
    });
}
</script>
@endsection
