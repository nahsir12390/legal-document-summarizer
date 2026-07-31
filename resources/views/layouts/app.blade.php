<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))@if(config('app.region')) - {{ config('app.region') }}@endif</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Space+Mono&display=swap" rel="stylesheet">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Space Grotesk', 'ui-sans-serif', 'system-ui'],
                        mono: ['Space Mono', 'ui-monospace', 'SFMono-Regular'],
                    },
                    colors: {
                        brand: {
                            50: '#f1f8ff',
                            100: '#dcecff',
                            200: '#b8d9ff',
                            300: '#8bc0ff',
                            400: '#5aa2ff',
                            500: '#2f7fff',
                            600: '#1e64f0',
                            700: '#1c4fbe',
                            800: '#1d449a',
                            900: '#1c3a7d',
                        },
                        ink: '#0b1220'
                    },
                    boxShadow: {
                        soft: '0 20px 60px rgba(15, 23, 42, 0.08)',
                        glass: '0 10px 30px rgba(15, 23, 42, 0.12)'
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --brand: #2f7fff;
            --brand-deep: #1e64f0;
            --bg: #f6f7fb;
            --card: rgba(255, 255, 255, 0.92);
        }
        .bg-grid {
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.06) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .glass {
            backdrop-filter: blur(12px);
            background: var(--card);
            border: 1px solid rgba(15, 23, 42, 0.08);
        }
    </style>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="font-sans antialiased bg-[color:var(--bg)] text-ink min-h-screen">
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-slate-50 to-white"></div>
        <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-blue-200/60 blur-3xl"></div>
        <div class="absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-sky-200/60 blur-3xl"></div>
        <div class="absolute inset-0 bg-grid opacity-40"></div>
    </div>

    <!-- Navigation Bar -->
    <nav class="sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="glass rounded-2xl shadow-glass px-4 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between py-4">
                    <div class="flex items-center gap-3">
                        <div class="h-11 w-11 rounded-xl bg-brand-600 text-white flex items-center justify-center shadow">
                            <i class="fas fa-gavel text-lg"></i>
                        </div>
                        <div>
                            <a href="{{ route('documents.upload') }}" class="text-lg sm:text-xl font-semibold text-ink hover:text-brand-700">
                                {{ config('app.name') }}
                            </a>
                            <p class="text-xs text-slate-500">{{ config('app.region') }} Legal Insight Hub</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('documents.upload') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-white/70 hover:bg-white shadow-sm border border-slate-200">
                            <i class="fas fa-upload"></i>{{ __('ui.nav.upload') }}
                        </a>
                        <a href="{{ route('documents.index') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-brand-600 text-white hover:bg-brand-700 shadow">
                            <i class="fas fa-history"></i>{{ __('ui.nav.history') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 px-4">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="glass rounded-2xl px-6 py-4 text-center text-sm text-slate-600">
                {!! __('ui.footer.rights', ['year' => date('Y'), 'app' => config('app.name')]) !!}
            </div>
        </div>
    </footer>
</body>
</html>
