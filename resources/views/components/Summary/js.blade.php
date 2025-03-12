@if(request()->is('case-summary'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Load Material Icons stylesheet
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined';
            document.head.appendChild(link);

            const body = document.body;
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const darkModeIcon = document.getElementById('dark-mode-icon');

            const setDarkMode = isEnabled => {
                body.classList.toggle('dark-mode', isEnabled);
                if (darkModeIcon) darkModeIcon.textContent = isEnabled ? 'brightness_7' : 'brightness_4';
                if (darkModeToggle) darkModeToggle.setAttribute('title', isEnabled ? 'Bright mode' : 'Dark mode');
                localStorage.setItem('dark-mode', isEnabled ? 'enabled' : 'disabled');
                reloadBotpress();
            };

            let botId = @json(initializeBp('bot_id'));
            let clientId = @json(initializeBp('client_id'));


            const initBotpress = () => {
                const bp = document.createElement('script');
                const theme = localStorage.getItem('dark-mode') === 'enabled' ? 'dark' : 'light';
                const bpConfig = {
                    // botId: "0c9b1930-a01d-4402-a4f6-f8ce53560eef",
                    botId: botId,
                    configuration: {
                        botName: 'BMS AI',
                        showPoweredBy: false,
                        website: { url: 'export.communitasker.io' },
                        color: "#673AB7",
                        variant: "solid",
                        themeMode: theme,
                        customFontUrl: 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap',
                        radius: 1
                    },
                    // clientId: "64683c0e-ec44-4e8b-84e8-3e751b477b22",
                    clientId: clientId,
                    selector: "#webchat"
                };

                bp.type = 'text/javascript';
                bp.async = true;
                bp.src = 'https://cdn.botpress.cloud/webchat/v2.3/inject.js';
                bp.onload = () => {
                    window.botpress.init(bpConfig);
                    window.botpress.on("webchat:ready", () => window.botpress.open());
                };

                const firstScript = document.getElementsByTagName('script')[0];
                firstScript.parentNode.insertBefore(bp, firstScript);
            };

            const reloadBotpress = () => {
                if (window.botpress && typeof window.botpress.destroy === 'function') {
                    window.botpress.destroy();
                }
                const container = document.getElementById('webchat-container');
                if (container) {
                    container.innerHTML = '<div id="webchat"></div>';
                }
                initBotpress();
            };

            // Initialize dark mode based on localStorage
            if (localStorage.getItem('dark-mode') === 'enabled') {
                setDarkMode(true);
            }

            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', () => {
                    setDarkMode(!body.classList.contains('dark-mode'));
                });
            }

            initBotpress();
        });
    </script>
@endif
