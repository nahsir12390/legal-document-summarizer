@extends('layouts.app')

@section('title', __('ui.history.page_title', ['app' => config('app.name')]))

@section('content')
<div class="space-y-6">
    <div class="glass rounded-3xl p-6 sm:p-8 shadow-soft flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold text-ink">{{ __('ui.history.heading') }}</h1>
            <p class="text-slate-600">{{ __('ui.history.subheading', ['region' => config('app.region')]) }}</p>
        </div>
        <a href="{{ route('documents.upload') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-2 text-white font-semibold shadow hover:bg-brand-700">
            <i class="fas fa-upload"></i>{{ __('ui.history.upload_new') }}
        </a>
    </div>

    @if(session('success'))
        <div class="glass rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-emerald-700">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="glass rounded-3xl shadow-soft overflow-hidden">
        @if($documents->isEmpty())
            <div class="text-center py-16 px-6">
                <div class="mx-auto h-16 w-16 rounded-2xl bg-slate-900 text-white flex items-center justify-center mb-4">
                    <i class="fas fa-folder-open text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-ink mb-2">{{ __('ui.history.empty_title') }}</h3>
                <p class="text-slate-600 mb-6">{{ __('ui.history.empty_subtitle') }}</p>
                <a href="{{ route('documents.upload') }}"
                   class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-2 text-white font-semibold shadow hover:bg-brand-700">
                    <i class="fas fa-upload"></i>
                    {{ __('ui.history.upload_first') }}
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('ui.history.table_document') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('ui.history.table_type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('ui.history.table_uploaded') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('ui.history.table_status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('ui.history.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/60 divide-y divide-slate-200">
                        @foreach($documents as $doc)
                            <tr class="hover:bg-white/90 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl flex items-center justify-center bg-slate-900 text-white">
                                            <i class="fas fa-file-{{ $doc->file_icon }}"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-ink">
                                                {{ Str::limit($doc->file_name, 40) }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $doc->formatted_file_size }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-slate-900 text-white">
                                        {{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-ink">
                                        {{ $doc->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $doc->created_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($doc->summary)
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                            {{ __('ui.history.status_completed') }}
                                        </span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                                            {{ __('ui.history.status_processing') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('documents.show', $doc->id) }}"
                                           class="h-9 w-9 rounded-xl border border-slate-200 flex items-center justify-center text-ink hover:bg-white"
                                           title="{{ __('ui.history.action_view') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('documents.download', $doc->id) }}"
                                           class="h-9 w-9 rounded-xl border border-slate-200 flex items-center justify-center text-ink hover:bg-white"
                                           title="{{ __('ui.history.action_download') }}">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form action="{{ route('documents.destroy', $doc->id) }}" method="POST"
                                              class="inline"
                                              onsubmit="return confirm('{{ __('ui.history.confirm_delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-9 w-9 rounded-xl border border-red-200 text-red-600 flex items-center justify-center hover:bg-red-50" title="{{ __('ui.history.action_delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-white/70 border-t border-slate-200">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
