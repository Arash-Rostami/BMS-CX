// show table minus of summary in red color
window.addEventListener('DOMContentLoaded', function () {
    if (!window.location.href.includes('orders')) return;

    setTimeout(() => {
        document.querySelectorAll('.fi-ta-text-summary span').forEach(span => {
            span.innerHTML = span.innerHTML.replace(/(-\d+(\.\d+)?)/g, '<span style="color: red;">$1</span>');
        });
    }, 2000);

});

// overlay logic
window.addEventListener('DOMContentLoaded', function () {

    // loadSortable(initializeSortable);

    function checkOrientation() {
        const orientationOverlay = document.getElementById('orientation-overlay');
        if (!orientationOverlay) {
            return;
        }

        const isLoginPage = window.location.href.toLowerCase().includes('login');
        const isPrinting = window.matchMedia('print').matches;

        if (isLoginPage || isPrinting) {
            orientationOverlay.style.display = 'none';
            return;
        }

        const isPortrait = window.matchMedia("(orientation: portrait)").matches;

        orientationOverlay.style.display = isPortrait ? 'flex' : 'none';
    }


    checkOrientation();
    window.addEventListener('resize', checkOrientation);
    window.addEventListener('orientationchange', checkOrientation);

    window.addEventListener('beforeprint', function () {
        const orientationOverlay = document.getElementById('orientation-overlay');
        if (orientationOverlay) {
            orientationOverlay.style.display = 'none';
        }
    });

    window.addEventListener('afterprint', checkOrientation);
});

// scrolling tables

window.addEventListener('livewire:initialized', function () {
    let scrollTable = (direction) => {
        let scrollableWrapper = document.querySelector('.fi-ta-content');
        if (!scrollableWrapper) return;
        let distance = scrollableWrapper.clientWidth * 2;
        scrollableWrapper.scrollBy({left: direction * distance, behavior: 'smooth'});
    };

    let toggleFullScreen = () => {
        let table = document.querySelector('div[x-data="table"].fi-ta');
        if (!table) return;
        if (!document.fullscreenElement) {
            table.requestFullscreen().catch(() => console.error('Fullscreen request failed.'));
        } else {
            document.exitFullscreen();
        }
    };

    // Attach event listeners
    window.addEventListener('scrollLeft', () => scrollTable(-1));
    window.addEventListener('scrollRight', () => scrollTable(1));
    window.addEventListener('toggleFullScreen', toggleFullScreen);
})

// Banner r-to-l
document.addEventListener('DOMContentLoaded', () => {
    const richEditor = document.getElementById('rich-editor');
    // text editor
    if (richEditor) {
        richEditor.addEventListener('input', () => {
            const text = richEditor.innerText || richEditor.textContent;

            if (isFarsi(text)) {
                richEditor.style.direction = 'rtl';
            } else {
                richEditor.style.direction = 'ltr';
            }
        });
    }

    function isFarsi(text) {
        const farsiRegex = /[\u0600-\u06FF]/;
        return farsiRegex.test(text);
    }
});

// PR auto processing wizard
document.addEventListener('DOMContentLoaded', () => {
    let nextWizard = () => {
        let nextButton = document.querySelector('#next-step-button');
        if (nextButton) {
            nextButton.click();
        }
    }
    window.addEventListener('triggerNext', () => nextWizard());
});

// Allow download of Payment Attachments
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('open-new-tab', event => {
        const urls = Array.isArray(event.detail) ? event.detail.flat() : [];
        let index = 0;

        function openNextUrl() {
            if (index >= urls.length) return;
            window.open(urls[index], '_blank');
            index++;
            setTimeout(openNextUrl, 1000);
        }

        openNextUrl();
    });
});


// Listen for the "refreshPage" event and reload the page
window.addEventListener('refreshPage', function () {
    window.location.reload(true);
});


