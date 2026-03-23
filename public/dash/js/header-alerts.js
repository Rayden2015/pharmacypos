/**
 * Mark-all-read for header announcement / DM dropdowns (persists when data-mark-read-url is set).
 */
(function () {
    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function hideUnreadUI(triggerSelector, menu) {
        if (menu) {
            menu.querySelectorAll('.notify-unread-dot').forEach(function (d) {
                d.remove();
            });
        }
        var trigger = document.querySelector(triggerSelector);
        if (trigger) {
            var badge = trigger.querySelector('.alert-count');
            if (badge) {
                badge.classList.add('d-none');
                badge.textContent = '0';
            }
        }
    }

    function markRead(url, triggerSelector, menu) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: '{}',
        })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error('Request failed');
                }
                return r.json();
            })
            .then(function () {
                hideUnreadUI(triggerSelector, menu);
            })
            .catch(function () {
                /* Keep unread indicators if server rejected */
            });
    }

    document.addEventListener('click', function (e) {
        var markN = e.target.closest('.js-mark-notifications-read');
        if (markN) {
            e.preventDefault();
            var menu = markN.closest('.dropdown-menu');
            var url = markN.getAttribute('data-mark-read-url');
            if (url) {
                markRead(url, '[data-header-alerts="notifications"]', menu);
            } else {
                hideUnreadUI('[data-header-alerts="notifications"]', menu);
            }
            return;
        }

        var markM = e.target.closest('.js-mark-messages-read');
        if (markM) {
            e.preventDefault();
            var menuM = markM.closest('.dropdown-menu');
            var urlM = markM.getAttribute('data-mark-read-url');
            if (urlM) {
                markRead(urlM, '[data-header-alerts="messages"]', menuM);
            } else {
                hideUnreadUI('[data-header-alerts="messages"]', menuM);
            }
        }
    });
})();
