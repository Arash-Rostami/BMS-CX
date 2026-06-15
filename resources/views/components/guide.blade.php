<div x-data="{
        showGuideCue: false,
        init() {
            if (!localStorage.getItem('guide_cue_dismissed')) {
                setTimeout(() => {
                    this.showGuideCue = true;
                }, 500);

                setTimeout(() => {
                    this.dismissCue();
                }, 10500);
            }
        },
        dismissCue() {
            this.showGuideCue = false;
            localStorage.setItem('guide_cue_dismissed', 'true');
        }
    }"
    class="relative flex items-center"
>
    <div @click="dismissCue">
        {{ $this->userGuideAction }}
    </div>

    <x-filament-actions::modals />

    <div x-show="showGuideCue"
         x-transition:enter="transition ease-out duration-700"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="absolute right-0 top-full mt-1 w-64 z-50 flex flex-col items-end pointer-events-none"
    >
        <!-- Curved Arrow SVG pointing UP towards the button -->
        <div class="mr-6 -mt-2 animate-[bounce_2s_infinite]">
            <svg class="w-8 h-8 text-primary-500 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7L18 4L21 7 M18 4V12 A 6 6 0 0 1 12 18 H 6"></path>
            </svg>
        </div>

        <!-- Tooltip -->
        <div class="mt-1 bg-white dark:bg-gray-800 border border-primary-100 dark:border-primary-900/50 shadow-xl rounded-lg p-3 flex items-start space-x-3 pointer-events-auto relative">
            <div class="flex-1 text-sm text-gray-700 dark:text-gray-300 font-medium">
                New: check the Help / User Guide here
            </div>
            <button @click.prevent="dismissCue" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors focus:outline-none flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
