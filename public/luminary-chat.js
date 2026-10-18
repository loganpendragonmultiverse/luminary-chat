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

    function focusComposer() {
        requestAnimationFrame(() => {
            const composer = getComposer();
            if (!composer || composer.disabled) {
                return;
            }

            composer.focus({ preventScroll: true });
            composer.setSelectionRange(composer.value.length, composer.value.length);
        });
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
        const shell = getShell();
        const stream = shell?.querySelector('.original-message-stream, .message-stream');
        const latestMessage = shell?.querySelector('.original-message-stack > article:last-child, .message-stream > article:last-child');

        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }

        if (latestMessage) {
            latestMessage.scrollIntoView({ block: 'end', behavior: 'smooth' });
        } else if (stream) {
            stream.scrollIntoView({ block: 'end', behavior: 'smooth' });
        }
    }

    function queueScrollToBottom() {
        requestAnimationFrame(() => {
            scrollConversationToBottom();
            setTimeout(scrollConversationToBottom, 80);
        });
    }

    function appendPendingExchange(messageText) {
        const workspace = document.querySelector('[data-chat-workspace]');
        const stack = workspace?.querySelector('.original-message-stack');
        if (!stack || !messageText.trim()) {
            return null;
        }

        const characterName = workspace.dataset.activeCharacterName || 'Character';
        const userDisplayName = workspace.dataset.userDisplayName || 'the user';
        const pendingId = `pending-${Date.now()}`;
        const userMessage = document.createElement('article');
        userMessage.className = 'message-bubble message-user message-pending';
        userMessage.dataset.pendingExchange = pendingId;

        const userHead = document.createElement('div');
        userHead.className = 'message-head';
        const userIdentity = document.createElement('div');
        const userRole = document.createElement('p');
        userRole.className = 'message-role';
        userRole.textContent = userDisplayName.toUpperCase();
        userIdentity.append(userRole);
        userHead.append(userIdentity);

        const userContent = document.createElement('div');
        userContent.className = 'message-content pending-message-content';
        userContent.textContent = messageText;
        userMessage.append(userHead, userContent);

        const typingMessage = document.createElement('article');
        typingMessage.className = 'message-bubble message-assistant message-typing';
        typingMessage.dataset.pendingExchange = pendingId;
        typingMessage.setAttribute('role', 'status');
        typingMessage.setAttribute('aria-live', 'polite');
        typingMessage.setAttribute('aria-label', `${characterName} is typing`);

        const typingHead = document.createElement('div');
        typingHead.className = 'message-head';
        const typingIdentity = document.createElement('div');
        const typingRole = document.createElement('p');
        typingRole.className = 'message-role';
        typingRole.textContent = characterName.toUpperCase();
        const typingMeta = document.createElement('p');
        typingMeta.className = 'message-meta';
        typingMeta.textContent = 'Typing…';
        typingIdentity.append(typingRole, typingMeta);
        typingHead.append(typingIdentity);

        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.setAttribute('aria-hidden', 'true');
        indicator.append(document.createElement('span'), document.createElement('span'), document.createElement('span'));
        typingMessage.append(typingHead, indicator);

        stack.append(userMessage, typingMessage);
        queueScrollToBottom();

        return { userMessage, typingMessage };
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
            queueScrollToBottom();
        }
    }

    async function submitAsyncForm(form) {
        if (form.dataset.asyncBusy === 'true') {
            return;
        }

        const requestBody = new FormData(form);
        const requestStartedAt = performance.now();
        form.dataset.asyncBusy = 'true';
        getShell()?.setAttribute('aria-busy', 'true');
        const submitter = form.querySelector('[type="submit"]');
        const composer = form.querySelector('#chat-composer');
        const status = form.querySelector('[data-composer-status]');
        const originalLabel = submitter?.getAttribute('aria-label') || '';
        const pendingExchange = composer ? appendPendingExchange(composer.value) : null;
        const submittedText = composer?.value || '';
        if (submitter) {
            submitter.disabled = true;
            submitter.setAttribute('aria-label', 'Sending message');
        }
        if (composer) {
            composer.value = '';
            composer.style.height = 'auto';
            composer.disabled = true;
        }
        if (status) {
            status.textContent = 'Sending… waiting for the model response.';
        }

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: requestBody,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                redirect: 'follow',
            });

            const html = await response.text();
            if (!response.ok) {
                throw new Error(`Message request failed with status ${response.status}`);
            }
            const responseReceivedAt = performance.now();
            await refreshFromHtml(html);
            const visibleAt = performance.now();
            console.info('Luminary chat timing', {
                request_to_response_ms: Math.round(responseReceivedAt - requestStartedAt),
                request_to_visible_ms: Math.round(visibleAt - requestStartedAt),
            });
        } catch (error) {
            pendingExchange?.typingMessage.remove();
            pendingExchange?.userMessage.classList.add('message-send-uncertain');
            const region = getErrorRegion();
            if (region) {
                region.innerHTML = '<div class="error-stack"><p>The response could not be confirmed. Refresh this conversation before trying again.</p></div>';
                region.focus?.();
            }
            if (composer && !composer.value) {
                composer.value = submittedText;
            }
        } finally {
            form.dataset.asyncBusy = 'false';
            getShell()?.removeAttribute('aria-busy');
            if (submitter) {
                submitter.disabled = false;
                submitter.setAttribute('aria-label', originalLabel || 'Send message');
            }
            const activeComposer = getComposer();
            if (activeComposer) {
                activeComposer.disabled = false;
                focusComposer();
            }
            if (status) {
                status.textContent = '';
            }
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && form.matches('[data-single-submit]')) {
            if (form.dataset.submitBusy === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitBusy = 'true';
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('[type="submit"]').forEach((button) => {
                button.disabled = true;
            });
        }

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

        if (target.closest('[data-sidebar-toggle]')) {
            if (matchMedia('(max-width:800px)').matches) document.body.classList.toggle('mobile-sidebar-open'); else document.body.classList.toggle('chat-sidebar-collapsed');
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
            queueScrollToBottom();
        }
    });

    document.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLTextAreaElement) || target.id !== 'chat-composer') {
            return;
        }

        target.style.height = 'auto';
        target.style.height = `${Math.min(target.scrollHeight, 190)}px`;
    });

    document.addEventListener('keydown', (event) => {
        const composer = getComposer();
        if (!composer) {
            return;
        }

        if (document.activeElement === composer && event.key === 'Enter' && !event.shiftKey && !event.isComposing && !composer.disabled) {
            const form = composer.closest('form');
            if (form instanceof HTMLFormElement) {
                event.preventDefault();
                if (form.matches('[data-async="chat-shell"]')) {
                    submitAsyncForm(form);
                } else {
                    form.requestSubmit();
                }
            }
            return;
        }

        if (document.activeElement === composer && !event.shiftKey && !event.isComposing && !composer.disabled && (event.ctrlKey || event.metaKey) && event.key === 'Enter') {
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
        queueScrollToBottom();
    }
})();
