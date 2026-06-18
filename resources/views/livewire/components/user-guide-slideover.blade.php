<div x-data="{ expanded: null }" class="flex flex-col h-full space-y-4">

    <!-- Intro Text -->
    <div class="pb-4 border-b border-gray-200 dark:border-white/10">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Explore step-by-step documentation, videos, and infographics for optimized enterprise workflows.
        </p>
    </div>

    <!-- Accordion List -->
    <div class="space-y-3">
        @php
            $workflows = [
                ['id' => 'w1', 'title' => 'Logistics & Shipping', 'pdf' => '/guides/logistics.pdf', 'video' => 'logistics_vid', 'info' => 'logistics_info'],
                ['id' => 'w2', 'title' => 'Financial Quarter Closing', 'pdf' => '/guides/financials.pdf', 'video' => 'financials_vid', 'info' => 'financials_info'],
                ['id' => 'w3', 'title' => 'Customs Clearance', 'pdf' => '/guides/customs.pdf', 'video' => 'customs_vid', 'info' => 'customs_info'],
            ];
        @endphp

        @foreach($workflows as $workflow)
        <div class="border border-gray-200 dark:border-white/10 rounded-lg overflow-hidden bg-white dark:bg-gray-900 shadow-sm transition-all duration-200"
             :class="{ 'ring-1 ring-primary-500 border-primary-500': expanded === '{{ $workflow['id'] }}' }">

            <button @click="expanded = expanded === '{{ $workflow['id'] }}' ? null : '{{ $workflow['id'] }}'"
                    class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10 transition-colors text-left"
                    :class="{ 'bg-primary-50/50 dark:bg-primary-500/10': expanded === '{{ $workflow['id'] }}' }">
                <span class="text-sm font-semibold text-gray-900 dark:text-white"
                      :class="{ 'text-primary-600 dark:text-primary-400': expanded === '{{ $workflow['id'] }}' }">
                    {{ $workflow['title'] }}
                </span>
                <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-90 text-primary-500': expanded === '{{ $workflow['id'] }}' }" />
            </button>

            <div x-show="expanded === '{{ $workflow['id'] }}'"
                 x-collapse
                 class="p-4 bg-white dark:bg-gray-900 space-y-3 border-t border-gray-200 dark:border-white/10">

                <!-- View Infographic -->
                <button wire:click="openInfographic('{{ $workflow['info'] }}')"
                        class="w-full flex items-center gap-3 p-3 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group border border-transparent hover:border-gray-200 dark:hover:border-white/10 text-left">
                    <div class="w-10 h-10 rounded-md bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400">
                        <x-heroicon-o-photo class="w-6 h-6" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">View Infographic</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Visual workflow map</p>
                    </div>
                    <x-heroicon-m-arrows-pointing-out class="w-5 h-5 text-gray-400 group-hover:text-primary-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                </button>

                <!-- Watch Video -->
                <button wire:click="openVideo('{{ $workflow['video'] }}')"
                        class="w-full flex items-center gap-3 p-3 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group border border-transparent hover:border-gray-200 dark:hover:border-white/10 text-left">
                    <div class="w-10 h-10 rounded-md bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <x-heroicon-o-play-circle class="w-6 h-6" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">Watch Video</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Step-by-step walkthrough</p>
                    </div>
                    <x-heroicon-m-play class="w-5 h-5 text-gray-400 group-hover:text-primary-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                </button>

                <!-- Read PDF -->
                <a href="{{ $workflow['pdf'] }}" target="_blank"
                   class="w-full flex items-center gap-3 p-3 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group border border-transparent hover:border-gray-200 dark:hover:border-white/10">
                    <div class="w-10 h-10 rounded-md bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-document-text class="w-6 h-6" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">Read PDF</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Detailed handbook</p>
                    </div>
                    <x-heroicon-m-arrow-top-right-on-square class="w-5 h-5 text-gray-400 group-hover:text-primary-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
