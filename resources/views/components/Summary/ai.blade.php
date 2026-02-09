<div
    x-data="chatWidget()"
    class="cw-root"
    :class="{ 'cw-root--no-transform': maximized }"
    @keydown.window.escape="if (maximized) maximized = false"
>
    <button
        @click.stop="chatOpen = !chatOpen; if(!chatOpen) maximized = false"
        :aria-pressed="chatOpen"
        :class="chatOpen ? 'cw-toggle my-dark-class u-btn u-btn--icon x-active' : 'cw-toggle my-dark-class u-btn u-btn--icon'"
        role="button"
        aria-label="Open chat"
        type="button"
    >
    <span class="cw-icon cw-icon--open" x-show="!chatOpen" aria-hidden>
      <span class="material-icons-outlined cw-material animate-spectrum">smart_toy</span>
    </span>
        <span class="cw-icon cw-icon--close" x-show="chatOpen" x-cloak aria-hidden>
      <span class="material-icons-outlined cw-material">close</span>
    </span>
    </button>

    <div
        x-show="chatOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-5 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-5 scale-95"
        class="cw-panel"
        :class="{ 'cw-panel--maximized': maximized }"
        @click.away="if(!maximized) { chatOpen = false }"
        x-ref="panel"
        x-cloak
    >
        <div class="cw-header" role="toolbar" aria-label="Chat header">
            <div class="cw-header-left">
                <div class="cw-status-dot"
                     :class="maintenance ? 'cw-status-dot--maintenance' : 'cw-status-dot--ok'"></div>
                <span class="cw-title" x-text="maintenance ? 'Maintenance' : 'AI Assistant'"></span>
            </div>

            <div class="cw-header-controls">
                <button
                    @click.stop="maximized = !maximized"
                    :aria-pressed="maximized"
                    :title="maximized ? 'Exit fullscreen' : 'Open fullscreen'"
                    class="cw-btn-max u-btn u-btn--icon"
                    x-show="!maintenance"
                    type="button"
                >
                        <span class="material-icons-outlined"
                              x-text="maximized ? 'close_fullscreen' : 'open_in_full'"></span>
                </button>

                <button
                    @click.stop="chatOpen = false; maximized = false"
                    class="cw-btn-close u-btn u-btn--icon"
                    title="Close"
                    type="button"
                >
                    <span class="material-icons-outlined">close</span>
                </button>
            </div>
        </div>

        <template x-if="chatOpen && !maintenance">
            <iframe
                src="https://persol-ai.cldv.dev/?user={{ auth()->id() }}"
                class="cw-iframe"
                allow="microphone; camera; clipboard-write; clipboard-read; fullscreen"
                loading="lazy"
                title="AI Assistant"
            ></iframe>
        </template>

        <div x-show="maintenance" class="cw-maintenance" x-cloak>
            <span class="material-icons-outlined cw-maint-icon animate-pulse">build</span>
            <h3 class="cw-maint-title">Updating</h3>
            <p class="cw-maint-sub">The AI assistant will return shortly</p>
            <p class="cw-maint-note">Please wait a few minutes</p>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        function chatWidget() {
            return {
                chatOpen: false,
                maximized: false,
                maintenance: false,
                _origParent: null,
                _panel: null,
                toggle() {
                    this.chatOpen = !this.chatOpen;
                    if (!this.chatOpen) this.maximized = false
                },
                close() {
                    this.chatOpen = false;
                    this.maximized = false
                },
                init() {
                    this.$nextTick(() => {
                        this._panel = this.$refs.panel;
                        this._origParent = this._panel.parentNode;
                        this.$watch('chatOpen', open => {
                            if (open) {
                                document.body.appendChild(this._panel);
                                document.body.classList.add('has-open-ai-panel');
                                this.$nextTick(() => this._panel.style.zIndex = 2147483640);
                            } else {
                                if (this._origParent) this._origParent.appendChild(this._panel);
                                document.body.classList.remove('has-open-ai-panel');
                                this._panel.style.zIndex = '';
                            }
                        });
                        this.$watch('maximized', v => {
                            if (v && !this.chatOpen) this.chatOpen = true
                        });
                    });
                }
            }
        }
    </script>
@endpush
