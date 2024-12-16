<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <div class="flex-1 flex">
                <span class="inline-block" style="cursor:help">
                    <img class="inline-block" src="{{Vite::asset('resources/images/logos/bms-logo-v5.png')}}" width="60">
                   <span @click="window.open('https://time-gr.com/cv')"
                         title="Business Management Software, crafted with ❤ by Arash Rostami (Last Update: {{ config('app.update') }} )">{{ config('app.name') }}</span>
                </span>
            </div>

            <div class="flex flex-col items-end gap-y-1">
                <a href="{{  route('filament.admin.resources.users.version') }}"
                   class="text-center cursor-pointer text-xs flex-grow"
                   title="Crafted with  ❤ by Arash Rostami (Last Update: {{ config('app.update') }} )">
                    © {{ date('M d, Y') }} - V{{ config('app.version') }} All rights reserved.
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
