// Shared toast + AJAX form/action helpers, reused by every admin CRUD page
// (Menu, Inventory, Staff, etc.) so pages call one shared implementation
// instead of each page re-writing fetch/error-handling/toast logic itself.

// Chart.js (already committed) is
// imported once here and exposed on window, same as the helpers below, so
// the Analytics page's inline <script> block can call `new Chart(...)`
// directly without every chart-using page needing its own Vite entry point.
// Requires `npm install chart.js`, the 'chart.js/auto' import auto-registers
// every controller/element (line, bar, scatter, doughnut, tooltips, etc.) so
// individual pieces don't need to be registered by hand.
import Chart from 'chart.js/auto';
window.Chart = Chart;

// Escapes text before it's ever interpolated into an innerHTML template
// literal. This exists because several places in this app build a message
// server-side that embeds a user-submitted value verbatim, e.g.
// InventoryController::destroy()'s conflict message includes the actual
// ingredient name ("Can't delete "X", it has..."), and the notification
// bell's low-stock alerts include it too (NotificationController). Nothing
// currently stops an ingredient, menu item, or staff name from containing HTML.
// Every place in this file (and every page's own inline script, via
// window.escapeHtml below) that interpolates a value that ultimately traces
// back to something a user typed uses this first.
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}
window.escapeHtml = escapeHtml;

// Icon presets: each action gets a color + icon combination so the toast communicates
// *what* happened at a glance, not just whether it succeeded. Callers pass a `kind`
// (e.g. 'edit', 'delete', 'unavailable') instead of a generic success/error flag.
const TOAST_PRESETS = {
    success: { circle: 'bg-green-500', icon: 'check' },        // create / generic success
    edit: { circle: 'bg-green-500', icon: 'pencil' },
    available: { circle: 'bg-green-500', icon: 'check' },
    unavailable: { circle: 'bg-red-500', icon: 'eyeSlash' },
    delete: { circle: 'bg-red-500', icon: 'trash' },
    error: { circle: 'bg-red-500', icon: 'exclamation' },      // generic failure fallback
};

const TOAST_ICON_PATHS = {
    check: 'M4.5 12.75l6 6 9-13.5',
    pencil: 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125',
    trash: 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0',
    eyeSlash: 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88',
    exclamation: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
};

function showToast(message, kind = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const preset = TOAST_PRESETS[kind] || TOAST_PRESETS.success;
    const iconPath = TOAST_ICON_PATHS[preset.icon];

    const toast = document.createElement('div');
    toast.className = `bg-white border border-gray-200 rounded-lg shadow-lg px-4 py-3 flex items-center gap-3
        min-w-[280px] max-w-sm translate-x-full opacity-0 transition-all duration-300 ease-out pointer-events-auto`;

    toast.innerHTML = `
        <span class="w-7 h-7 rounded-full ${preset.circle} text-white flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="${iconPath}" />
            </svg>
        </span>
        <span class="text-sm text-gray-800 font-medium flex-1">${escapeHtml(message)}</span>
        <button type="button" class="toast-close text-gray-400 hover:text-gray-600 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;

    container.appendChild(toast);

    function dismiss() {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }

    requestAnimationFrame(() => requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }));

    const autoDismiss = setTimeout(dismiss, 3500);

    toast.querySelector('.toast-close').addEventListener('click', () => {
        clearTimeout(autoDismiss);
        dismiss();
    });
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

/**
 * Submits a <form> as FormData to `url`, using Laravel's POST + _method spoofing
 * for PUT/PATCH/DELETE (avoids the multipart-body parsing issues real HTTP PUT/DELETE
 * requests have in PHP). Renders 422 validation errors into `errorBox` if provided,
 * shows a toast on both success and failure, and hands the parsed JSON to `onSuccess`.
 
 * Uses XMLHttpRequest rather than fetch() specifically so upload progress can be
 * reported via the optional `onUploadProgress(percent, loadedBytes, totalBytes)`
 * callback, fetch has no cross-browser way to observe request-body upload progress,
 * only response-download progress, which is useless for file uploads.
 
 * Any failure, a validation error aside, always produces a visible toast/error
 * message, so a request can never fail completely silently.
 */
function submitAjaxForm({ form, url, method = 'POST', errorBox, toastKind, onUploadProgress, onSuccess, onValidationError }) {
    if (errorBox) {
        errorBox.classList.add('hidden');
        errorBox.innerHTML = '';
    }

    const formData = new FormData(form);
    if (method !== 'POST') {
        formData.append('_method', method);
    }

    function showGenericError() {
        if (errorBox) {
            errorBox.classList.remove('hidden');
            errorBox.textContent = 'Something went wrong. Please try again.';
        }
        showToast('Something went wrong. Please try again.', 'error');
    }

    function renderValidationErrors(data) {
        if (!errorBox) return;
        errorBox.classList.remove('hidden');
        if (data && data.errors && typeof data.errors === 'object') {
            errorBox.innerHTML = '<ul class="list-disc list-inside">' +
                Object.values(data.errors).flat().map((m) => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
        } else {
            // Response was a 422 but not in Laravel's usual {errors: {...}} shape,
            // fall back to whatever message is available instead of throwing.
            errorBox.textContent = (data && data.message) || 'Please check the form and try again.';
        }
    }

    return new Promise((resolve) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url);
        xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
        xhr.setRequestHeader('Accept', 'application/json');

        if (onUploadProgress) {
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    onUploadProgress(Math.round((e.loaded / e.total) * 100), e.loaded, e.total);
                }
            });
        }

        xhr.onload = () => {
            let data = null;
            try {
                data = xhr.responseText ? JSON.parse(xhr.responseText) : null;
            } catch (parseErr) {
                // Response wasn't JSON at all (e.g. an HTML error page), treat as failure
                // rather than letting the exception propagate and skip straight to "something
                // went wrong" with no context.
                console.error('Non-JSON response from', url, parseErr);
                showGenericError();
                resolve();
                return;
            }

            if (xhr.status === 422) {
                // A 422 NEVER shows a toast and NEVER calls onSuccess, the form stays
                // open exactly as the user left it so they can correct the input. If the
                // caller supplied onValidationError (e.g. to show a friendlier, field-
                // specific message for something like a duplicate name), use that;
                // otherwise fall back to the generic bulleted list of every error.
                if (onValidationError) {
                    onValidationError(data);
                } else {
                    renderValidationErrors(data);
                }
                resolve();
                return;
            }

            if (xhr.status < 200 || xhr.status >= 300) {
                console.error(`Request to ${url} failed with status ${xhr.status}`, data);
                showGenericError();
                resolve();
                return;
            }

            showToast((data && data.message) ?? 'Saved successfully.', toastKind ?? 'success');
            onSuccess?.(data);
            resolve();
        };

        xhr.onerror = () => {
            console.error(`Network error submitting to ${url}`);
            showGenericError();
            resolve();
        };

        xhr.send(formData);
    });
}

/**
 * Fire-and-forget style action with no <form> behind it, used for Delete
 * buttons, "mark unavailable" quick actions, and similar one-click operations.
 * `extraFields` lets the caller send a small payload without building a form.
 * Pass `confirmMessage` to show a native confirm() first; omit it for flows
 * (like a custom two-choice popup) that already confirmed with the user.
 */
async function submitAjaxAction({ url, method = 'DELETE', confirmMessage = null, extraFields = {}, toastKind, toastMessage, onSuccess, onError }) {
    if (confirmMessage && !confirm(confirmMessage)) {
        return;
    }

    const formData = new FormData();
    formData.append('_method', method);
    Object.entries(extraFields).forEach(([key, value]) => formData.append(key, value));

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
            body: formData,
        });

        let data = null;
        try {
            const text = await response.text();
            data = text ? JSON.parse(text) : null;
        } catch (parseErr) {
            console.error('Non-JSON response from', url, parseErr);
            showToast('Something went wrong. Please try again.', 'error');
            onError?.(parseErr);
            return;
        }

        if (!response.ok) {
            // Surface the server's actual message when available (e.g. a foreign-key
            // constraint explanation), instead of always showing a generic failure,
            // this matters for cases like "can't delete, it's still in use."
            showToast((data && data.message) || 'Something went wrong. Please try again.', 'error');
            onError?.(data);
            return;
        }

        // toastMessage lets a call site override the backend's generic message
        // (e.g. "Menu item updated successfully.") with something more specific
        // to what the user actually did (e.g. "Marked unavailable.").
        showToast(toastMessage ?? data?.message ?? 'Done.', toastKind ?? 'success');
        onSuccess?.(data);
    } catch (err) {
        console.error(err);
        showToast('Something went wrong. Please try again.', 'error');
        onError?.(err);
    }
}

// Expose globally so Blade templates can call these directly with plain <script> tags,
// without needing to set up ES module imports in every page.
window.showToast = showToast;
window.submitAjaxForm = submitAjaxForm;
window.submitAjaxAction = submitAjaxAction;

/**
 * Filter-chip clear ("x") buttons, shared by every admin filter/search bar
 * (Menu's Category chip, Staff's Role chip, and the search box on all three
 * pages: Menu, Inventory, Staff). Registered once here via event delegation
 * rather than per-page, since the button only ever does one thing regardless
 * of which page or field it's attached to: blank out its own field and
 * resubmit the surrounding GET filter form.
 
 * Resubmitting the whole form (rather than just navigating to a hand-built
 * URL) is what lets clearing one filter leave any other active filter in the
 * same form untouched, e.g. clearing Category on Menu Management preserves
 * an active Search term, and vice versa, instead of a single "Clear" link
 * wiping every filter at once.
 */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.filter-chip-clear');
    if (!btn) return;

    const form = btn.closest('form');
    const field = form?.elements[btn.dataset.clearField];
    if (!field) return;

    field.value = '';
    form.submit();
});

/**
 * Searchable filter-select popovers ("Category: All ▾" style chips), see
 * admin/partials/_filter_select.blade.php. One delegated set of listeners
 * handles every instance on the page, so the partial can be dropped in
 * anywhere without its own <script> block.
 */
document.addEventListener('click', (e) => {
    // Close every popover whose trigger/content wasn't part of this click,
    // before handling what the click actually landed on. A container whose
    // own trigger was clicked is deliberately left alone here, its
    // open/closed state is decided explicitly in the trigger branch below,
    // not by this "click was outside" sweep.
    document.querySelectorAll('[data-filter-select]').forEach((container) => {
        if (!container.contains(e.target)) {
            container.querySelector('.filter-select-popover')?.classList.add('hidden');
        }
    });

    const trigger = e.target.closest('.filter-select-trigger');
    if (trigger) {
        const container = trigger.closest('[data-filter-select]');
        const popover = container.querySelector('.filter-select-popover');
        const wasOpen = !popover.classList.contains('hidden');

        popover.classList.toggle('hidden');
        trigger.setAttribute('aria-expanded', String(!wasOpen));

        if (!wasOpen) {
            const search = popover.querySelector('.filter-select-search');
            if (search) {
                search.value = '';
                search.dispatchEvent(new Event('input'));
                search.focus();
            }
        }
        return;
    }

    const option = e.target.closest('.filter-select-option');
    if (option) {
        const container = option.closest('[data-filter-select]');
        const form = container.closest('form');
        const field = form?.elements[container.dataset.name];
        if (!field) return;

        field.value = option.dataset.optionValue;
        form.submit();
    }
});

// Live search-as-you-type filtering within an open popover's option list.
document.addEventListener('input', (e) => {
    if (!e.target.matches('.filter-select-search')) return;

    const query = e.target.value.trim().toLowerCase();
    const list = e.target.closest('[data-filter-select]').querySelector('.filter-select-list');
    let anyVisible = false;

    list.querySelectorAll('.filter-select-option').forEach((option) => {
        const matches = (option.dataset.optionLabel || '').includes(query);
        option.classList.toggle('hidden', !matches);
        if (matches) anyVisible = true;
    });

    list.querySelector('.filter-select-empty')?.classList.toggle('hidden', anyVisible);
});

// Escape closes any open popover regardless of where focus currently is.
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.filter-select-popover:not(.hidden)').forEach((popover) => {
        popover.classList.add('hidden');
        popover.closest('[data-filter-select]')?.querySelector('.filter-select-trigger')?.setAttribute('aria-expanded', 'false');
    });
});

/**
 * Search-with-suggestions dropdown, see admin/partials/_filter_search.blade.php.
 * Opt-in per instance (only renders when a page passes a $suggestions list),
 * so this has zero effect on search boxes that don't use it.
 */
const SUGGESTION_HIGHLIGHT_CLASS = 'bg-gray-100';

function visibleSuggestions(list) {
    return [...list.querySelectorAll('.search-suggestion:not(.hidden)')];
}

// Filter suggestions live as the admin types. Deliberately hidden entirely
// on an empty query, suggestions only appear "when the user has already
// input" something, not just from focusing an empty search box.
document.addEventListener('input', (e) => {
    if (!e.target.matches('.search-suggest-input')) return;

    const list = e.target.closest('[data-search-suggest]')?.querySelector('.search-suggest-list');
    if (!list) return;

    const query = e.target.value.trim().toLowerCase();
    if (!query) {
        list.classList.add('hidden');
        return;
    }

    let anyVisible = false;
    list.querySelectorAll('.search-suggestion').forEach((btn) => {
        btn.classList.remove(SUGGESTION_HIGHLIGHT_CLASS); // clear stale keyboard highlight
        const matches = btn.dataset.value.toLowerCase().includes(query);
        btn.classList.toggle('hidden', !matches);
        if (matches) anyVisible = true;
    });

    list.querySelector('.search-suggest-empty')?.classList.toggle('hidden', anyVisible);
    list.classList.remove('hidden');
});

// Re-show the dropdown when refocusing a field that already has text typed
// (e.g. tabbing back in), 'focus' doesn't bubble, so this needs the
// capture phase to work via delegation.
document.addEventListener('focus', (e) => {
    if (!e.target.matches?.('.search-suggest-input')) return;
    const list = e.target.closest('[data-search-suggest]')?.querySelector('.search-suggest-list');
    if (list && e.target.value.trim() && visibleSuggestions(list).length) {
        list.classList.remove('hidden');
    }
}, true);

document.addEventListener('click', (e) => {
    const suggestion = e.target.closest('.search-suggestion');
    if (suggestion) {
        const wrapper = suggestion.closest('[data-search-suggest]');
        const input = wrapper.querySelector('.search-suggest-input');
        const form = wrapper.closest('form');
        input.value = suggestion.dataset.value;
        form.submit();
        return;
    }

    // Close any open suggestions dropdown when clicking elsewhere. This is
    // click-based rather than a 'blur' handler on the input deliberately:
    // blur fires (on mousedown) before a click on a suggestion button ever
    // reaches this listener, so hiding on blur would make every suggestion
    // unclickable, the list would vanish a moment before the click landed.
    document.querySelectorAll('[data-search-suggest]').forEach((wrapper) => {
        if (!wrapper.contains(e.target)) {
            wrapper.querySelector('.search-suggest-list')?.classList.add('hidden');
        }
    });
});

document.addEventListener('keydown', (e) => {
    if (!e.target.matches('.search-suggest-input')) return;
    const list = e.target.closest('[data-search-suggest]')?.querySelector('.search-suggest-list');
    if (!list || list.classList.contains('hidden')) return;

    const options = visibleSuggestions(list);
    if (!options.length) return;

    const currentIndex = options.findIndex((btn) => btn.classList.contains(SUGGESTION_HIGHLIGHT_CLASS));

    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (currentIndex >= 0) options[currentIndex].classList.remove(SUGGESTION_HIGHLIGHT_CLASS);
        const nextIndex = e.key === 'ArrowDown'
            ? (currentIndex + 1) % options.length
            : (currentIndex <= 0 ? options.length - 1 : currentIndex - 1);
        options[nextIndex].classList.add(SUGGESTION_HIGHLIGHT_CLASS);
        options[nextIndex].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter' && currentIndex >= 0) {
        e.preventDefault();
        options[currentIndex].click();
    } else if (e.key === 'Escape') {
        list.classList.add('hidden');
    }
});

/**
 * Notification bell, lives in the shared layout, so this runs on every admin page.
 * Fetches current alerts from the notifications endpoint, populates the dropdown
 * with a per-type icon and a relative timestamp, and tracks which alerts the admin
 * has already dismissed via "Mark all as read" (stored in localStorage, since there's
 * no backend notifications table to persist true read/unread state against, this is
 * a "don't keep bugging me about the same alert" mechanism, not a durable read log).
 */
const NOTIFICATION_ICONS = {
    low_stock: {
        bg: 'bg-red-100', color: 'text-red-600',
        path: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
    },
    default: {
        bg: 'bg-blue-100', color: 'text-blue-600',
        path: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
    },
};

function timeAgo(isoString) {
    if (!isoString) return '';
    const diffMinutes = Math.floor((Date.now() - new Date(isoString).getTime()) / 60000);
    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`;
    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours < 24) return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
}

function getDismissedAlertKeys() {
    try {
        return JSON.parse(localStorage.getItem('dz_dismissed_alerts') || '[]');
    } catch {
        return [];
    }
}

function initNotificationBell() {
    const btn = document.getElementById('notification-bell-btn');
    if (!btn) return;

    const dropdown = document.getElementById('notification-dropdown');
    const dot = document.getElementById('notification-dot');
    const list = document.getElementById('notification-list');
    const emptyState = document.getElementById('notification-empty');
    const markReadBtn = document.getElementById('notification-mark-read');
    const url = btn.dataset.url;

    let latestAlerts = [];

    function renderAlertItem(alert) {
        const icon = NOTIFICATION_ICONS[alert.type] || NOTIFICATION_ICONS.default;
        const a = document.createElement('a');
        a.href = alert.url;
        a.className = 'flex items-start gap-3 px-4 py-3 hover:bg-gray-50';
        a.innerHTML = `
            <span class="w-8 h-8 rounded-full ${icon.bg} ${icon.color} flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="${icon.path}" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block text-sm font-semibold text-gray-800 truncate">${escapeHtml(alert.title)}</span>
                <span class="block text-xs text-gray-500">${escapeHtml(alert.message)}</span>
                <span class="block text-xs text-gray-400 mt-0.5">${timeAgo(alert.timestamp)}</span>
            </span>
        `;
        return a;
    }

    function render() {
        list.innerHTML = '';

        if (latestAlerts.length === 0) {
            dot.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        latestAlerts.forEach((alert) => list.appendChild(renderAlertItem(alert)));

        const dismissed = getDismissedAlertKeys();
        const hasUnread = latestAlerts.some((alert) => !dismissed.includes(alert.key));
        dot.classList.toggle('hidden', !hasUnread);
    }

    async function loadNotifications() {
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            latestAlerts = data.alerts || [];
            render();
        } catch (err) {
            console.error('Failed to load notifications', err);
        }
    }

    markReadBtn.addEventListener('click', () => {
        localStorage.setItem('dz_dismissed_alerts', JSON.stringify(latestAlerts.map((a) => a.key)));
        render();
    });

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    loadNotifications();
    // Poll frequently enough that new alerts show up without the admin needing to
    // manually refresh the page, true push (no delay at all) would need WebSocket
    // broadcasting (Laravel Reverb, per the project architecture), which is a bigger
    // piece of shared infrastructure outside this page's scope. This is the practical
    // middle ground until that's wired up.
    setInterval(loadNotifications, 15000);
}

document.addEventListener('DOMContentLoaded', initNotificationBell);