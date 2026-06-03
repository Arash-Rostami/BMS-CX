<div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-6 dark:bg-gray-950">
    <div class="w-full max-w-md">
        <x-filament::section shadow="xl">
            <x-slot name="heading">
                <div class="flex flex-col items-center justify-center space-y-4">
                    <img
                        src="{{ Vite::asset('resources/images/logos/bms-new-logo.png') }}"
                        alt="{{ config('app.name') }} Logo"
                        style="height: 6rem;"
                        class="w-auto"
                    >

                    <div class="text-center">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            {{ __('Client Portal') }}
                        </h2>
                    </div>
                </div>
            </x-slot>

            <x-slot name="description">
                <div class="text-center text-sm">
                    {{ __('Sign in to view your account statement or loan summary') }}
                </div>
            </x-slot>

            <form wire:submit.prevent="login" class="mt-4 space-y-6">
                @if($error)
                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 dark:border-danger-900 dark:bg-danger-950/50">
                        <div class="flex items-center gap-3">
                            <x-filament::icon
                                icon="heroicon-m-x-circle"
                                class="h-5 w-5 text-danger-600 dark:text-danger-400"
                            />
                            <p class="text-sm font-medium text-danger-600 dark:text-danger-400">
                                {{ $error }}
                            </p>
                        </div>
                    </div>
                @endif

                <x-filament-forms::field-wrapper id="username" label="Username">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            id="username"
                            type="text"
                            wire:model="username"
                            required
                            autofocus
                        />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="password" label="Password">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            id="password"
                            type="password"
                            wire:model="password"
                            required
                        />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                    <x-filament::button
                        type="submit"
                        class="w-full"
                        size="lg"
                        title="Click to log in"
                        style="background-color:gray"
                        icon="heroicon-o-arrow-right-end-on-rectangle"
                    >
                    </x-filament::button>
            </form>
            <p class="flex text-xs text-gray-500 dark:text-gray-400 justify-end pt-4">
                &copy; {{ date('Y') }} BMS. All rights reserved.
            </p>
        </x-filament::section>
    </div>
</div>
