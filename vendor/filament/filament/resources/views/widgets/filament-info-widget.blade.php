<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <div class="flex-1 flex">
                <span class="inline-block" style="cursor: help">
                    <img class="inline-block" src="{{Vite::asset('resources/images/bms-main-logo.png')}}" width="35">
                   <span title="Business Management Software">BMS</span>
                </span>
            </div>

            <div class="flex flex-col items-end gap-y-1">
               <span class="text-center cursor-pointer text-xs flex-grow"
                     title="Crafted with  ❤ by Arash Rostami (Last Update: {{ config('app.update') }} )"
                     @click="window.open('https://time-gr.com/cv')"> © {{ date('M d, Y') }} - V{{ config('app.version') }} All rights reserved.</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
