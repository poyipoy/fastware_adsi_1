const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function toggleBookmark(button) {
    const bookmarked = button.dataset.bookmarked === 'true';
    const response = await fetch(bookmarked ? button.dataset.destroyUrl : button.dataset.storeUrl, {
        method: bookmarked ? 'DELETE' : 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
    });

    if (! response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.message ?? 'Status Baca Nanti tidak dapat diperbarui.');
    }

    return ! bookmarked;
}

function updateButton(button, bookmarked) {
    const label = bookmarked ? 'Hapus dari Baca Nanti' : 'Simpan ke Baca Nanti';
    const icon = button.querySelector('.km-bookmark-icon');

    button.dataset.bookmarked = String(bookmarked);
    button.setAttribute('aria-label', label);
    button.setAttribute('aria-pressed', String(bookmarked));
    button.title = label;

    if (icon) {
        icon.className = `km-bookmark-icon bi ${bookmarked ? 'bi-bookmark-fill' : 'bi-bookmark'}`;
    }
}

function showError(button, message) {
    const card = button.closest('.km-document-card');
    if (! card) {
        return;
    }

    let error = card.querySelector('.km-bookmark-error');
    if (! error) {
        error = document.createElement('div');
        error.className = 'km-bookmark-error alert alert-danger py-1 px-2 m-2 small';
        error.setAttribute('role', 'alert');
        card.querySelector('.card-body')?.prepend(error);
    }
    error.textContent = message;
}

export function initBookmarkButtons() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-km-bookmark]');
        if (! button || button.disabled) {
            return;
        }

        button.disabled = true;
        button.closest('.km-document-card')?.querySelector('.km-bookmark-error')?.remove();

        try {
            const bookmarked = await toggleBookmark(button);
            updateButton(button, bookmarked);
        } catch (error) {
            showError(button, error.message ?? 'Status Baca Nanti tidak dapat diperbarui.');
        } finally {
            button.disabled = false;
        }
    });
}
