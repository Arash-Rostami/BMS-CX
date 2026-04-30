@if(request()->is('case-summary'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ── Material Icons ──────────────────────────────────
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            // link.href = 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined';
            link.href = '{{ asset('build/assets/material-symbols/rounded.css') }}';
            document.head.appendChild(link);

            // ── Dark Mode ───────────────────────────────────────
            const body = document.body;
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const darkModeIcon = document.getElementById('dark-mode-icon');

            const setDarkMode = isEnabled => {
                body.classList.toggle('dark-mode', isEnabled);
                if (darkModeIcon) darkModeIcon.textContent = isEnabled ? 'brightness_7' : 'brightness_4';
                if (darkModeToggle) darkModeToggle.setAttribute('title', isEnabled ? 'Bright mode' : 'Dark mode');
                localStorage.setItem('dark-mode', isEnabled ? 'enabled' : 'disabled');
            };

            if (localStorage.getItem('dark-mode') === 'enabled') {
                setDarkMode(true);
            }

            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', () => {
                    setDarkMode(!body.classList.contains('dark-mode'));
                });
            }

            // ── Contract Detail Filter ──────────────────────────
            const filterContract = (() => {
                const HIGHLIGHT_CLASS = 'filter-highlight';
                const FADED_CLASS = 'filter-faded';
                const stash = new WeakMap();

                const remember = el => {
                    if (!stash.has(el)) stash.set(el, el.getAttribute('style') || '');
                };

                const forget = el => {
                    const s = stash.get(el);
                    s !== undefined ? el.setAttribute('style', s) : el.removeAttribute('style');
                    el.classList.remove(FADED_CLASS);
                };

                const clearHighlights = root => {
                    root.querySelectorAll(`.${HIGHLIGHT_CLASS}`).forEach(mark => {
                        const p = mark.parentNode;
                        if (!p) return;
                        p.insertBefore(document.createTextNode(mark.textContent), mark);
                        p.removeChild(mark);
                        p.normalize();
                    });
                };

                const paint = (textNode, query) => {
                    const txt = textNode.textContent;
                    const idx = txt.toLowerCase().indexOf(query.toLowerCase());
                    if (idx === -1 || textNode.parentElement?.closest?.(`.${HIGHLIGHT_CLASS}`)) return false;

                    const parent = textNode.parentNode;
                    const mark = document.createElement('mark');
                    mark.className = HIGHLIGHT_CLASS;
                    mark.textContent = txt.substring(idx, idx + query.length);

                    if (txt.substring(0, idx)) parent.insertBefore(document.createTextNode(txt.substring(0, idx)), textNode);
                    parent.insertBefore(mark, textNode);
                    if (txt.substring(idx + query.length)) parent.insertBefore(document.createTextNode(txt.substring(idx + query.length)), textNode);
                    parent.removeChild(textNode);
                    return true;
                };

                return query => {
                    const root = document.querySelector('.tab-content');
                    if (!root) return;

                    const q = query.trim();
                    clearHighlights(root);
                    root.querySelectorAll('[x-data]').forEach(forget);
                    if (!q) return;

                    const qL = q.toLowerCase();

                    root.querySelectorAll('[x-data]').forEach(acc => {
                        if (!acc.textContent.toLowerCase().includes(qL)) {
                            remember(acc);
                            acc.classList.add(FADED_CLASS);
                            acc.style.cssText += ';transition:opacity .35s ease,max-height .4s ease,margin .35s ease,padding .35s ease,border-width .35s ease;overflow:hidden;opacity:0.06;pointer-events:none';
                            acc.style.maxHeight = acc.scrollHeight + 'px';
                            requestAnimationFrame(() => requestAnimationFrame(() => {
                                acc.style.maxHeight = '0px';
                                acc.style.margin = '0';
                                acc.style.padding = '0';
                                acc.style.borderWidth = '0';
                            }));
                            return;
                        }

                        if (window.Alpine) {
                            const d = Alpine.$data(acc);
                            if (d && 'open' in d) d.open = true;
                        }

                        const grid = acc.querySelector('.proforma-details-grid') || acc;
                        const walker = document.createTreeWalker(grid, NodeFilter.SHOW_TEXT, {
                            acceptNode: n =>
                                n.textContent.trim() &&
                                !n.parentElement?.closest?.('button') &&
                                !n.parentElement?.closest?.(`.${HIGHLIGHT_CLASS}`)
                                    ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
                        });

                        const nodes = [];
                        while (walker.nextNode())
                            if (walker.currentNode.textContent.toLowerCase().includes(qL))
                                nodes.push(walker.currentNode);

                        nodes.reverse().forEach(n => paint(n, q));
                    });
                };
            })();

            // ── Filter Input & Clear Button Wiring ──────────────
            let debounce;

            if (!window.contractFilterLoaded) {
                document.addEventListener('input', e => {
                    if (e.target.id !== 'contract-filter') return;

                    clearTimeout(debounce);
                    const val = e.target.value;

                    debounce = setTimeout(() => filterContract(val), 120);

                    const clearBtn = document.querySelector('#contract-filter-clear');
                    if (clearBtn) {
                        clearBtn.classList.toggle('hidden', val.length === 0);
                    }
                });

                document.addEventListener('click', e => {
                    const clearBtn = e.target.closest('#contract-filter-clear');
                    if (!clearBtn) return;

                    const input = document.querySelector('#contract-filter');
                    if (input) {
                        input.value = '';
                        clearBtn.classList.add('hidden');
                        input.focus();
                        filterContract('');
                    }
                });

                window.contractFilterLoaded = true;
            }

            document.addEventListener('livewire:navigated', () => {
                const input = document.querySelector('#contract-filter');
                const clearBtn = document.querySelector('#contract-filter-clear');

                if (input) input.value = '';
                if (clearBtn) clearBtn.classList.add('hidden');

                filterContract('');
            });
        });
    </script>
@endif
