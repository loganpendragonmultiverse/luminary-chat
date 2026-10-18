(function () {
    function getShell() {
        return document.querySelector('[data-chat-shell]');
    }

    function getFlashRegion() {
        return document.querySelector('[data-flash-region]');
    }

    function getErrorRegion() {
        return document.querySelector('[data-error-region]');
    }

    function getComposer() {
        return document.querySelector('#chat-composer');
    }

    const shell = getShell();

    if (!shell) {
        return;
    }

    const autoscrollKey = 'pendragon-chat-autoscroll';
    let autoscrollEnabled = localStorage.getItem(autoscrollKey) !== 'off';

    function updateAutoscrollLabel() {
        document.querySelectorAll('[data-autoscroll-toggle]').forEach((button) => {
            button.textContent = `Auto-scroll: ${autoscrollEnabled ? 'on' : 'off'}`;
        });
    }

    function scrollConversationToBottom() {
        const stream = getShell()?.querySelector('.message-stream');
        if (stream) {
            stream.scrollIntoView({ block: 'end', behavior: 'smooth' });
        }
    }

    function activatePanel(panelName) {
        document.querySelectorAll('[data-panel-target]').forEach((button) => {
            button.classList.toggle('active', button.dataset.panelTarget === panelName);
        });

        document.querySelectorAll('[data-panel-section]').forEach((section) => {
            section.hidden = section.dataset.panelSection !== panelName;
        });
    }

    async function refreshFromHtml(htmlText) {
        const parser = new DOMParser();
        const html = parser.parseFromString(htmlText, 'text/html');
        const newShell = html.querySelector('[data-chat-shell]');
        const newFlash = html.querySelector('[data-flash-region]');
        const newErrors = html.querySelector('[data-error-region]');

        if (newShell) {
            getShell()?.replaceWith(newShell);
        }
        if (newFlash && getFlashRegion()) {
            getFlashRegion()?.replaceWith(newFlash);
        }
        if (newErrors && getErrorRegion()) {
            getErrorRegion()?.replaceWith(newErrors);
        }

        if (autoscrollEnabled) {
            scrollConversationToBottom();
        }
    }

    async function submitAsyncForm(form) {
        form.dataset.asyncBusy = 'true';

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                redirect: 'follow',
            });

            const html = await response.text();
            await refreshFromHtml(html);
        } finally {
            form.dataset.asyncBusy = 'false';
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-async="chat-shell"]')) {
            return;
        }

        event.preventDefault();
        submitAsyncForm(form);
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const copyTrigger = target.closest('[data-copy-message]');
        if (copyTrigger instanceof HTMLElement) {
            const content = document.querySelector(`[data-message-content="${copyTrigger.dataset.copyMessage}"]`);
            if (content) {
                navigator.clipboard.writeText(content.textContent?.trim() || '');
            }
            return;
        }

        const quoteTrigger = target.closest('[data-quote-message]');
        const composer = getComposer();
        if (quoteTrigger instanceof HTMLElement && composer) {
            const content = document.querySelector(`[data-message-content="${quoteTrigger.dataset.quoteMessage}"]`);
            if (content) {
                const lines = (content.textContent || '').trim().split('\n').map((line) => `> ${line}`);
                composer.value = `${composer.value.trim()}\n\n${lines.join('\n')}\n\n`.trim();
                composer.focus();
                composer.setSelectionRange(composer.value.length, composer.value.length);
            }
            return;
        }

        const panelTrigger = target.closest('[data-panel-target]');
        if (panelTrigger instanceof HTMLElement) {
            activatePanel(panelTrigger.dataset.panelTarget || 'conversation');
            return;
        }

        if (target.closest('[data-autoscroll-toggle]')) {
            autoscrollEnabled = !autoscrollEnabled;
            localStorage.setItem(autoscrollKey, autoscrollEnabled ? 'on' : 'off');
            updateAutoscrollLabel();
            return;
        }

        if (target.closest('[data-scroll-bottom]')) {
            scrollConversationToBottom();
        }
    });

    document.addEventListener('keydown', (event) => {
        const composer = getComposer();
        if (!composer) {
            return;
        }

        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
            const form = composer.closest('form');
            if (form instanceof HTMLFormElement) {
                event.preventDefault();
                if (form.matches('[data-async="chat-shell"]')) {
                    submitAsyncForm(form);
                } else {
                    form.requestSubmit();
                }
            }
        }
    });

    updateAutoscrollLabel();
    activatePanel('conversation');

    if (autoscrollEnabled) {
        scrollConversationToBottom();
    }
})();
