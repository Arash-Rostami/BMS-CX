<div x-data="{
    showTooltip: false,
    tooltipText: @js($tooltip ?? ''),
    tooltipId: 'tooltip-' + Math.random().toString(36).slice(2, 9)
}">
    <a
        {{ $attributes->except(['tooltip']) }}
        @mouseover="showTooltip = true"
        @mouseleave="showTooltip = false"
        class="focus:outline-none"
        :id="tooltipId + '-trigger'"
    >
        {{ $slot }}
    </a>
    <template x-teleport="body">
        <div
            x-show="showTooltip"
            class="fixed tooltip border border-gray-300 shadow-md z-[9999] bg-gray-800 text-white text-sm font-normal px-2 py-1 pointer-events-none"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-init="$watch('showTooltip', value => {
                if (value) {
                    const trigger = document.getElementById(tooltipId + '-trigger');
                    const rect = trigger.getBoundingClientRect();
                    $el.style.left = rect.left + 'px';
                    $el.style.top = (rect.bottom + 5) + 'px';
                }
            })"
        >
            <span x-text="tooltipText" class="text-sm font-normal text-white"></span>
        </div>
    </template>
</div>
