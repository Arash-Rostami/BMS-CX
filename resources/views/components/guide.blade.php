<div x-data="{
    show: true,
    pos: {},
    init() {
        this.$nextTick(() => this.calc());
    },
    calc() {
        const r = this.$refs.anchor.getBoundingClientRect();
        this.pos = { top: (r.bottom + 18) + 'px', left: (r.left - 180) + 'px' };
    }
}" class="flex items-center">

    <div x-ref="anchor" @click="show = false">
        {{ $this->userGuideAction }}
        <x-filament-actions::modals />
    </div>

    <template x-teleport="body">
        <div
            x-show="show"
            x-transition.duration.300ms
            :style="`position:fixed;top:${pos.top};left:${pos.left};z-index:99999;pointer-events:none`"
            class="w-52"
        >
            <div class="absolute -top-1.5 right-6 size-3 rotate-45 !bg-primary-600"></div>
            <div class="bg-primary-600 text-white text-xs font-semibold px-3 py-2 rounded-lg shadow-lg whitespace-nowrap text-center">
                ✨ Check this <span style="color:#FEAD58">new</span>  BMS guide
            </div>
        </div>
    </template>
</div>
