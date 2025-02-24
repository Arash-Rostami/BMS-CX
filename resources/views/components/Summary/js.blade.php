@if(request()->path() === 'case-summary')
    <script>
        (function loadMaterialIcons() {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined';
            document.head.appendChild(link);
        })();

        const body = document.body;
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const darkModeIcon = document.getElementById('dark-mode-icon');

        function setDarkMode(isEnabled) {
            body.classList.toggle('dark-mode', isEnabled);
            darkModeIcon.textContent = isEnabled ? 'brightness_7' : 'brightness_4';
            darkModeToggle.setAttribute('title', isEnabled ? 'Bright mode' : 'Dark mode');
            localStorage.setItem('dark-mode', isEnabled ? 'enabled' : 'disabled');
        }

        const savedDarkMode = localStorage.getItem('dark-mode');
        if (savedDarkMode === 'enabled') {
            setDarkMode(true);
        }

        darkModeToggle.addEventListener('click', () => {
            setDarkMode(!body.classList.contains('dark-mode'));
        });
    </script>
@endif
