<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }} — {{ $title ?? 'Diginano Store' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-white text-gray-900 antialiased">
        <div class="flex min-h-full flex-col">
            <header class="border-b border-gray-200">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                    <a href="{{ route('catalog') }}" class="text-lg font-semibold tracking-tight">
                        {{ config('app.name', 'Diginano') }}
                    </a>
                    <nav class="flex items-center gap-4 text-sm text-gray-600">
                        <a href="{{ route('catalog') }}" class="hover:text-gray-900">Katalog</a>
                        <a href="{{ route('filament.admin.auth.login') }}" class="hover:text-gray-900">Masuk</a>
                    </nav>
                </div>
            </header>

            <main class="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                {{ $slot }}
            </main>

            <footer class="border-t border-gray-200 py-6">
                <div class="mx-auto max-w-6xl px-6 text-sm text-gray-500">
                    {{ config('app.name', 'Diginano') }} — toko produk digital.
                </div>
            </footer>
        </div>
    </body>
</html>
