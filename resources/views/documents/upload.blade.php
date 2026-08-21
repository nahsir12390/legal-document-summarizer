@extends('layouts.app')

@section('title', __('ui.upload.page_title', ['app' => config('app.name')]))

@section('content')
<div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-start">
    <section class="space-y-6">
        <div class="glass rounded-3xl p-8 shadow-soft">
            <div class="flex items-center gap-3 mb-6">
                <div class="h-12 w-12 rounded-2xl bg-brand-600 text-white flex items-center justify-center">
                    <i class="fas fa-cloud-arrow-up"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-semibold text-ink">
                        {{ __('ui.upload.heading', ['region' => config('app.region')]) }}
                    </h1>
                    <p class="text-slate-600">{{ __('ui.upload.subheading') }}</p>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="font-medium">{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label class="text-sm font-medium text-slate-700">
                        {{ __('ui.upload.select_document') }}
                    </label>

                    <div class="mt-3" x-data="{ fileName: '' }">
                        <input type="file"
                               name="document"
                               class="sr-only"
                               id="file-upload"
                               accept=".pdf,.docx,.txt"
                               @change="fileName = $event.target.files[0]?.name || ''">

                        <label for="file-upload"
                               class="block w-full border-2 border-dashed border-slate-300 rounded-2xl p-8 text-left transition hover:border-brand-500 hover:bg-white cursor-pointer">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                                        AI Ready
                                    </span>
                                    <span class="text-xs text-slate-400">{{ __('ui.upload.supported_types', ['size' => config('app.upload_max_mb')]) }}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center">
                                        <i class="fas fa-file-arrow-up"></i>
                                    </div>
                                    <div>
                                        <p class="text-base font-semibold text-ink">
                                            <span class="text-brand-700">{{ __('ui.upload.click_to_upload') }}</span> {{ __('ui.upload.or_drag') }}
                                        </p>
                                        <p class="text-sm text-slate-500" x-show="fileName" x-text="fileName"></p>
                                        <p class="text-sm text-slate-400" x-show="!fileName">{{ __('ui.upload.file_selected') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    @error('document')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">
                        {{ __('ui.upload.summary_length') }}
                    </label>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
    <input type="radio" id="summary-short" name="summary_length" value="short" class="sr-only peer/short" {{ old('summary_length', 'medium') == 'short' ? 'checked' : '' }}>
    <label for="summary-short" class="glass rounded-2xl p-4 cursor-pointer border border-transparent hover:border-brand-300 peer-checked/short:border-brand-500 peer-checked/short:ring-2 peer-checked/short:ring-brand-100">
        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-ink">{{ __('ui.upload.length_short') }}</span>
            <i class="fas fa-compress-alt text-brand-600"></i>
        </div>
        <p class="mt-2 text-xs text-slate-500">{{ __('ui.upload.length_short_help') }}</p>
    </label>

    <input type="radio" id="summary-medium" name="summary_length" value="medium" class="sr-only peer/medium" {{ old('summary_length', 'medium') == 'medium' ? 'checked' : '' }}>
    <label for="summary-medium" class="glass rounded-2xl p-4 cursor-pointer border border-transparent hover:border-brand-300 peer-checked/medium:border-brand-500 peer-checked/medium:ring-2 peer-checked/medium:ring-brand-100">
        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-ink">{{ __('ui.upload.length_medium') }}</span>
            <i class="fas fa-equals text-emerald-500"></i>
        </div>
        <p class="mt-2 text-xs text-slate-500">{{ __('ui.upload.length_medium_help') }}</p>
    </label>

    <input type="radio" id="summary-detailed" name="summary_length" value="detailed" class="sr-only peer/detailed" {{ old('summary_length', 'medium') == 'detailed' ? 'checked' : '' }}>
    <label for="summary-detailed" class="glass rounded-2xl p-4 cursor-pointer border border-transparent hover:border-brand-300 peer-checked/detailed:border-brand-500 peer-checked/detailed:ring-2 peer-checked/detailed:ring-brand-100">
        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-ink">{{ __('ui.upload.length_detailed') }}</span>
            <i class="fas fa-expand-alt text-purple-500"></i>
        </div>
        <p class="mt-2 text-xs text-slate-500">{{ __('ui.upload.length_detailed_help') }}</p>
    </label>
</div>
</div>

<button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-brand-600 px-6 py-3 text-white font-semibold shadow hover:bg-brand-700">
                    <i class="fas fa-robot"></i>
                    {{ __('ui.upload.generate') }}
                </button>
            </form>
        </div>
    </section>

    <aside class="space-y-6">
        <div class="glass rounded-3xl p-6 shadow-soft">
            <h2 class="text-lg font-semibold text-ink mb-4">AI Summary Playbook</h2>
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-brand-600"></span>
                    Accurate summaries tuned for Nigerian legal language and structure.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                    Key points extracted for quick decision making.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-amber-500"></span>
                    Hosted Gemini processing without downloading a local AI model.
                </li>
            </ul>
        </div>

        <div class="glass rounded-3xl p-6 shadow-soft">
            <h2 class="text-lg font-semibold text-ink mb-3">Need the history?</h2>
            <p class="text-sm text-slate-600 mb-4">Jump back to prior summaries anytime.</p>
            <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-ink hover:bg-white">
                <i class="fas fa-history"></i>
                {{ __('ui.upload.view_history') }}
            </a>
        </div>
    </aside>
</div>

<!-- Add Alpine.js for the file name display (optional) -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection

