import './bootstrap';

const TOAST_CONTAINER_ID = 'wrk-global-toast-container';

function ensureToastContainer() {
    let container = document.getElementById(TOAST_CONTAINER_ID);
    if (container) {
        return container;
    }

    container = document.createElement('div');
    container.id = TOAST_CONTAINER_ID;
    container.className = 'fixed top-4 right-4 z-[9999] flex w-[min(26rem,calc(100vw-2rem))] flex-col gap-2 pointer-events-none';
    document.body.appendChild(container);

    return container;
}

function typeStyles(type) {
    switch (type) {
        case 'error':
            return 'border-red-200 bg-red-50 text-red-800';
        case 'warning':
            return 'border-amber-200 bg-amber-50 text-amber-900';
        case 'success':
            return 'border-emerald-200 bg-emerald-50 text-emerald-800';
        default:
            return 'border-gray-200 bg-white text-gray-800';
    }
}

function dismissToast(toast) {
    toast.classList.add('opacity-0', 'translate-y-1');
    window.setTimeout(() => {
        toast.remove();
    }, 200);
}

function showToast(type, message) {
    if (typeof message !== 'string' || message.trim() === '') {
        return;
    }

    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = `pointer-events-auto rounded-lg border px-3 py-2 text-sm shadow-lg transition duration-200 opacity-0 translate-y-1 ${typeStyles(type)}`;

    const text = document.createElement('p');
    text.className = 'leading-snug';
    text.textContent = message;
    toast.appendChild(text);

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'mt-2 inline-flex rounded border border-current/20 px-2 py-1 text-xs font-semibold hover:bg-black/5';
    closeButton.textContent = 'Dismiss';
    closeButton.addEventListener('click', () => dismissToast(toast));
    toast.appendChild(closeButton);

    container.appendChild(toast);
    window.requestAnimationFrame(() => {
        toast.classList.remove('opacity-0', 'translate-y-1');
    });

    window.setTimeout(() => dismissToast(toast), 7000);
}

window.addEventListener('notify', (event) => {
    const detail = Array.isArray(event?.detail) ? event.detail[0] : event?.detail;
    showToast(detail?.type ?? 'info', detail?.message ?? '');
});

let livewireHookRegistered = false;

document.addEventListener('livewire:init', () => {
    if (livewireHookRegistered || !window.Livewire || typeof window.Livewire.hook !== 'function') {
        return;
    }
    livewireHookRegistered = true;

    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status }) => {
            if (status === 0 || status === 419 || status >= 500) {
                showToast(
                    'error',
                    'Request failed on the server. Please retry. If it keeps failing, refresh the page and share the error details.'
                );
            }
        });
    });
});
