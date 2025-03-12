<x-filament-widgets::widget class="fi-filament-info-widget bg-white dark:bg-gray-800 p-4 rounded-md relative">
    <x-filament::section >
        <!-- Back to Top Button -->
        <button
            onclick="let currentPosition = window.pageYOffset; let scrollInterval = setInterval(() => { if (currentPosition > 0) { currentPosition -= 10; window.scrollTo(0, currentPosition); } else { clearInterval(scrollInterval); } }, 10);"
            class="flex items-center px-3 py-2 text-sm text-gray-600 dark:text-white-400 dark:font-bold bg-primary-200 dark:bg-primary-900
            rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition animate-pulse"
            aria-label="Back to Top" style="position: absolute; bottom:8%; right:1%!important;"
            title="Back to Top"
        >
            <x-heroicon-o-arrow-up-circle class="w-5 h-5 mr-2"/>
            Top
        </button>

        <div class="flex flex-col items-center justify-center space-y-2">
            <!-- Logo Section -->
            <div class="flex items-center space-x-2">
                <img src="{{ Vite::asset('resources/images/logos/bms-new-logo.png') }}" alt="BMS Logo" width="60"
                     class="object-contain">
                <span
                    title="Business Management Software, crafted with ❤ by Arash Rostami (Last Update: {{ config('app.update') }})"
                    class="text-gray-700 dark:text-gray-300 cursor-pointer hover:underline transition"
                >
                </span>
            </div>

            <a
                href="{{ route('filament.admin.resources.users.version') }}"
                class="text-center text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white transition"
                title="Crafted with ❤ by Arash Rostami (Last Update: {{ config('app.update') }})"
            >
                © {{ date('M d, Y') }} - V{{ config('app.version') }} All rights reserved.
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
