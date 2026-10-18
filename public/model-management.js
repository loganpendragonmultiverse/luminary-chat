+(function () {
    function syncFavoriteAvailability(visibleInput) {
        const row = visibleInput.closest('[data-model-row]');
        const favoriteInput = row?.querySelector('[data-model-favorite]');
        if (!(favoriteInput instanceof HTMLInputElement)) {
            return;
        }

        favoriteInput.disabled = !visibleInput.checked;
        if (!visibleInput.checked) {
            favoriteInput.checked = false;
        }
    }

    document.addEventListener('input', (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.matches('[data-model-search]')) {
            const query = target.value.trim().toLowerCase();
            document.querySelectorAll('[data-model-row]').forEach((row) => {
                row.hidden = query !== '' && !String(row.dataset.modelSearchText || '').includes(query);
            });
            return;
        }

        if (target instanceof HTMLInputElement && target.matches('[data-model-visible]')) {
            syncFavoriteAvailability(target);
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const showAll = target.closest('[data-show-provider-models]');
        if (!(showAll instanceof HTMLElement)) {
            return;
        }

        const provider = showAll.closest('[data-model-provider]');
        provider?.querySelectorAll('[data-model-visible]').forEach((input) => {
            if (input instanceof HTMLInputElement) {
                input.checked = true;
                syncFavoriteAvailability(input);
            }
        });
    });
})();
