@if(request()->is('case-summary'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Load Material Icons stylesheet
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            // link.href = 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined';
            link.href = '{{ asset('build/assets/material-icons-outlined.css') }}';
            document.head.appendChild(link);

            const body = document.body;
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const darkModeIcon = document.getElementById('dark-mode-icon');

            const setDarkMode = isEnabled => {
                body.classList.toggle('dark-mode', isEnabled);
                if (darkModeIcon) darkModeIcon.textContent = isEnabled ? 'brightness_7' : 'brightness_4';
                if (darkModeToggle) darkModeToggle.setAttribute('title', isEnabled ? 'Bright mode' : 'Dark mode');
                localStorage.setItem('dark-mode', isEnabled ? 'enabled' : 'disabled');
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
        });
    </script>
@endif
