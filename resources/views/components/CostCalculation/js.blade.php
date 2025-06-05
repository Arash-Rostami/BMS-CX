@if(request()->is('cost-calculation'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Load Material Icons if not already loaded
            if (!document.querySelector('link[href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined';
                document.head.appendChild(link);
            }

            const body = document.body;
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const darkModeIcon = document.getElementById('dark-mode-icon');

            const setDarkMode = (isEnabled) => {
                body.classList.toggle('dark-mode', isEnabled);
                if (darkModeIcon) {
                    darkModeIcon.textContent = isEnabled ? 'brightness_7' : 'brightness_4';
                }
                if (darkModeToggle) {
                    darkModeToggle.setAttribute('title', isEnabled ? 'Bright mode' : 'Dark mode');
                }
                localStorage.setItem('dark-mode', isEnabled ? 'enabled' : 'disabled');
            };

            // Set initial mode from localStorage
            setDarkMode(localStorage.getItem('dark-mode') === 'enabled');

            // Add toggle click handler
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', () => {
                    const isDark = body.classList.contains('dark-mode');
                    setDarkMode(!isDark);
                });
            }
        });
    </script>
@endif
