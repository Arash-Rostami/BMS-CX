//
// function loadSortable(callback) {
//     console.log('hi from load sortable');
//
//     if (typeof Sortable !== 'undefined') {
//         console.log('Sortable.js is already loaded');
//         callback();
//         return;
//     }
//     var script = document.createElement('script');
//     script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
//     script.onload = function () {
//         console.log('Sortable.js has been loaded');
//         callback();
//     };
//     script.onerror = function () {
//         console.error('Failed to load Sortable.js');
//     };
//     document.head.appendChild(script);
// }
//
// function initializeSortable() {
//     var el = document.querySelector('tr');
//     if (el) {
//         var sortable = new Sortable(el, {
//             animation: 150,
//             ghostClass: 'sortable-ghost',
//             handle: '.draggable-column',
//             onEnd: function (evt) {
//                 var item = evt.item;
//                 console.log('Column moved', item);
//             },
//         });
//     } else {
//         console.error('Table header row not found');
//     }
// }

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
