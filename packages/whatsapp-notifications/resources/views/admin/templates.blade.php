@extends('layouts.admin')

@section('title', 'Communication Templates')
@section('header_title', 'Communication Templates')
@section('header_subtitle', 'Manage transactional WhatsApp & SMS message templates and dynamic variables')

@section('content')
<div class="space-y-6">
    <!-- Back & Flash -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.communications.whatsapp.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
            &larr; Back to Communications Ledger
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($templates as $tpl)
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-bold">{{ $tpl->template_key }}</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $tpl->name }}</h3>
                    </div>
                    <div>
                        @if($tpl->channel === 'whatsapp')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                WhatsApp
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950/80 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800">
                                SMS
                            </span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.communications.whatsapp.templates.update', $tpl->id) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Template Name</label>
                            <input type="text" name="name" value="{{ old('name', $tpl->name) }}" required class="w-full px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Default Channel</label>
                            <select name="channel" class="w-full px-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="whatsapp" {{ $tpl->channel === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="sms" {{ $tpl->channel === 'sms' ? 'selected' : '' }}>SMS</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Message Body</label>
                        <textarea name="content" rows="5" required class="w-full p-3 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('content', $tpl->content) }}</textarea>
                    </div>

                    @if(!empty($tpl->variables))
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Available Placeholders</label>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($tpl->variables as $var)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        @php echo '{{' . e($var) . '}}'; @endphp
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $tpl->is_active ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Active</span>
                        </label>
                        <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
