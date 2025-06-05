<div x-data="{ showTooltip: false, tooltipText: '{{ $tooltip }}' }" class="relative">
    <a
        {{ $attributes->except(['tooltip']) }}
        @mouseover="showTooltip = true"
        @mouseleave="showTooltip = false"
        class="focus:outline-none"
    >
        {{ $slot }}
    </a>
    <div
        x-show="showTooltip"
        class="absolute tooltip border border-gray-300 shadow-md z-50"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none"
    >
        <span x-text="tooltipText" class="text-sm font-normal text-white"></span>
    </div>
</div>
