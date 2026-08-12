document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-training-status-summary]').forEach((summary) => {
        const scope = summary.closest('main') || document;
        const anchor = scope.querySelector('[data-training-status-anchor]');

        if (anchor && anchor.nextElementSibling !== summary) {
            anchor.insertAdjacentElement('afterend', summary);
        }

        const track = summary.querySelector('.d-flex');
        if (track) {
            track.tabIndex = 0;
            track.setAttribute(
                'aria-label',
                'Ringkasan status training. Geser ke samping untuk melihat seluruh status.',
            );
        }
    });
});
