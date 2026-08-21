@extends('layouts.app')

@section('title', __('ui.summary.page_title', ['app' => config('app.name')]))

@section('content')
<div class="space-y-6">
    <div class="glass rounded-3xl p-6 sm:p-8 shadow-soft flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold text-ink">{{ __('ui.summary.card_title') }}</h1>
            <p class="text-slate-600">{{ $document->file_name }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('documents.upload') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2 text-ink hover:bg-white">
                <i class="fas fa-upload"></i>{{ __('ui.summary.upload_new') }}
            </a>
            <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-2 text-white font-semibold shadow hover:bg-brand-700">
                <i class="fas fa-history"></i>{{ __('ui.summary.view_history') }}
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <div class="glass rounded-3xl p-6 shadow-soft">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('ui.summary.file_name') }}</p>
                        <p class="text-ink font-semibold">{{ $document->file_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('ui.summary.uploaded') }}</p>
                        <p class="text-ink font-semibold">{{ $document->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('ui.summary.file_size') }}</p>
                        <p class="text-ink font-semibold">{{ $document->formatted_file_size }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('ui.summary.summary_length') }}</p>
                        <p class="text-ink font-semibold capitalize">{{ $document->summary_length }}</p>
                    </div>
                </div>
            </div>

            @if($document->summary)
                <div class="glass rounded-3xl p-6 shadow-soft">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-align-left text-brand-600"></i>
                        <h2 class="text-lg font-semibold text-ink">{{ __('ui.summary.summary_heading') }}</h2>
                    </div>
                    <p class="text-slate-700 leading-relaxed">{{ $document->summary }}</p>
                </div>

                <div class="glass rounded-3xl p-6 shadow-soft">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-list-check text-emerald-500"></i>
                        <h2 class="text-lg font-semibold text-ink">{{ __('ui.summary.key_points') }}</h2>
                    </div>
                    <ul class="space-y-3">
                        @foreach($document->key_points as $index => $point)
                            <li class="flex items-start gap-3">
                                <span class="h-7 w-7 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center text-xs font-semibold">
                                    {{ $index + 1 }}
                                </span>
                                <span class="text-slate-700">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="glass rounded-3xl p-8 shadow-soft text-center">
                    <div class="mx-auto h-14 w-14 rounded-2xl bg-brand-600 text-white flex items-center justify-center mb-4">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <p class="text-lg font-semibold text-ink">{{ __('ui.summary.processing_title') }}</p>
                    <p class="text-sm text-slate-600 mt-1">{{ __('ui.summary.processing_subtitle', ['region' => config('app.region')]) }}</p>
                    <div class="mt-6 max-w-sm mx-auto">
                        <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-2 bg-brand-600 w-2/3 animate-pulse"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-3" id="refresh-status">Auto-refreshing in <span id="countdown">10</span> seconds...</p>
                    </div>
                    <button onclick="window.location.reload()"
                            class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-2 text-white font-semibold shadow hover:bg-brand-700">
                        <i class="fas fa-sync-alt"></i>{{ __('ui.summary.refresh_now') }}
                    </button>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="glass rounded-3xl p-6 shadow-soft">
                <h2 class="text-lg font-semibold text-ink mb-3">Summary Actions</h2>
                <div class="flex flex-col gap-3">
                    <a href="{{ route('documents.download', $document->id) }}"
                       class="inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-4 py-2 text-white font-semibold shadow hover:bg-emerald-600">
                        <i class="fas fa-download"></i>{{ __('ui.summary.download_original') }}
                    </a>
                    <form action="{{ route('documents.destroy', $document->id) }}" method="POST"
                          onsubmit="return confirm('{{ __('ui.history.confirm_delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-2 text-red-600 hover:bg-red-50">
                            <i class="fas fa-trash"></i>{{ __('ui.summary.delete') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="glass rounded-3xl p-6 shadow-soft">
                <h2 class="text-lg font-semibold text-ink mb-3">AI Insight Notes</h2>
                <p class="text-sm text-slate-600">Every summary is generated with Gemini's hosted AI, so no local model download is needed.</p>
            </div>
        </aside>
    </div>
</div>

@if(!$document->summary)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let countdown = 10;
            const countdownElement = document.getElementById('countdown');

            const interval = setInterval(function() {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = countdown;
                }

                if (countdown <= 0) {
                    clearInterval(interval);
                    document.getElementById('refresh-status').innerHTML = '{{ __('ui.summary.refreshing_now') }}';
                    window.location.reload();
                }
            }, 1000);
        });
    </script>
@endif
@endsection
