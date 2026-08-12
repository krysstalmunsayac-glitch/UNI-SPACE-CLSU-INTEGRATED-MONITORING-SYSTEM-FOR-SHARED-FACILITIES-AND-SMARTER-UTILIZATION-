<div
    id="ui-confirm-dialog"
    class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="ui-confirm-title"
    aria-describedby="ui-confirm-message"
>
    <section class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-800">
        <h2 id="ui-confirm-title" class="text-2xl font-bold text-zinc-950 dark:text-white">Confirm action</h2>
        <p id="ui-confirm-message" class="mt-2 text-base leading-6 text-zinc-600 dark:text-zinc-300"></p>

        <div class="mt-7 grid gap-3 sm:grid-cols-2">
            <button id="ui-confirm-accept" type="button" class="min-h-12 rounded-xl bg-emerald-600 px-5 text-base font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                Confirm
            </button>
            <button id="ui-confirm-cancel" type="button" class="min-h-12 rounded-xl px-5 text-base font-bold text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:text-zinc-100 dark:hover:bg-zinc-700">
                Cancel
            </button>
        </div>
    </section>
</div>

<script data-navigate-once>
(() => {
    if (window.uiConfirmationInstalled) return;
    window.uiConfirmationInstalled = true;

    const approvedElements = new WeakSet();
    let pendingElement = null;
    let previousFocus = null;

    const elements = () => ({
        dialog: document.getElementById('ui-confirm-dialog'),
        title: document.getElementById('ui-confirm-title'),
        message: document.getElementById('ui-confirm-message'),
        accept: document.getElementById('ui-confirm-accept'),
        cancel: document.getElementById('ui-confirm-cancel'),
    });

    const close = () => {
        const { dialog } = elements();
        dialog?.classList.add('hidden');
        dialog?.classList.remove('flex');
        pendingElement = null;
        previousFocus?.focus?.();
        previousFocus = null;
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-ui-confirm]');
        if (!trigger) return;

        if (approvedElements.has(trigger)) {
            approvedElements.delete(trigger);
            return;
        }

        const form = trigger.form;
        if (form && !form.checkValidity()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            form.reportValidity();
            return;
        }

        const message = trigger.getAttribute('data-ui-confirm');
        if (!message) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        const { dialog, title, message: messageElement, accept } = elements();
        if (!dialog) return;

        pendingElement = trigger;
        previousFocus = document.activeElement;
        title.textContent = trigger.dataset.uiConfirmTitle
            ?? 'Confirm action';
        messageElement.textContent = message;
        accept.textContent = trigger.dataset.uiConfirmLabel
            ?? 'Confirm';
        accept.classList.toggle('bg-red-600', trigger.dataset.uiConfirmVariant === 'danger');
        accept.classList.toggle('hover:bg-red-700', trigger.dataset.uiConfirmVariant === 'danger');
        accept.classList.toggle('bg-emerald-600', trigger.dataset.uiConfirmVariant !== 'danger');
        accept.classList.toggle('hover:bg-emerald-700', trigger.dataset.uiConfirmVariant !== 'danger');
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        accept.focus();
    }, true);

    document.addEventListener('click', event => {
        if (event.target.closest('#ui-confirm-cancel')) {
            close();
            return;
        }

        if (!event.target.closest('#ui-confirm-accept') || !pendingElement) return;

        const trigger = pendingElement;
        approvedElements.add(trigger);
        close();

        trigger.click();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !elements().dialog?.classList.contains('hidden')) close();
    });
})();
</script>
