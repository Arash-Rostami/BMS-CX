// show table minus of summary in red color
window.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        if (window.location.href.includes('orders')) {
            const spans = document.querySelectorAll('.fi-ta-text-summary span');

            spans.forEach(span => {
                // This regex matches negative floats or integers
                span.innerHTML = span.innerHTML.replace(/(-\d+(\.\d+)?)/g, '<span style="color: red;">$1</span>');
            });
        }
    }, 2000);
});


// overlay logic
function checkOrientation() {
    const orientationOverlay = document.getElementById('orientation-overlay');
    orientationOverlay.style.display = window.matchMedia("(orientation: portrait)").matches ? 'flex' : 'none';
}

checkOrientation();
window.addEventListener('resize', checkOrientation);
window.addEventListener('orientationchange', checkOrientation);
