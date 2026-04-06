(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function getCsrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function makeAvatarFromSender(sender) {
        var size = 40;
        var d = document.createElement('div');
        d.className =
            'dm-avatar flex-shrink-0 rounded-circle overflow-hidden d-flex align-items-center justify-content-center';
        d.style.width = size + 'px';
        d.style.height = size + 'px';
        d.setAttribute('aria-hidden', 'true');
        var has = sender && sender.has_photo && sender.photo_url;
        if (has) {
            var img = document.createElement('img');
            img.src = sender.photo_url;
            img.alt = '';
            img.className = 'w-100 h-100';
            img.style.objectFit = 'cover';
            d.appendChild(img);
        } else {
            d.classList.add('dm-avatar--silhouette');
            var i = document.createElement('i');
            i.className = 'bx bx-user text-secondary';
            i.style.fontSize = Math.max(14, Math.round(size * 0.5)) + 'px';
            d.appendChild(i);
        }
        return d;
    }

    function appendMessage(root, payload) {
        if (!payload || !payload.id) return;
        if (root.querySelector('[data-msg-id="' + payload.id + '"]')) return;

        var meId = window.__dmThreadConfig ? parseInt(window.__dmThreadConfig.meId, 10) : 0;
        var mine = parseInt(payload.sender_id, 10) === meId;
        var sender = payload.sender || {};

        var wrap = document.createElement('div');
        wrap.className =
            'd-flex mb-3 align-items-end ' + (mine ? 'justify-content-end' : 'justify-content-start');
        wrap.setAttribute('data-msg-id', String(payload.id));

        var avatar = makeAvatarFromSender(sender);

        var bubble = document.createElement('div');
        bubble.className = 'dm-bubble ' + (mine ? 'dm-bubble--me' : 'dm-bubble--them');
        var meta = document.createElement('div');
        meta.className = 'dm-bubble-meta';
        meta.textContent = payload.created_label || '';
        var body = document.createElement('div');
        body.className = 'dm-bubble-body';
        body.textContent = payload.body || '';
        bubble.appendChild(meta);
        bubble.appendChild(body);

        if (mine) {
            bubble.classList.add('me-2');
            wrap.appendChild(bubble);
            wrap.appendChild(avatar);
        } else {
            avatar.classList.add('me-2');
            wrap.appendChild(avatar);
            wrap.appendChild(bubble);
        }

        var empty = root.querySelector('.dm-thread-empty');
        if (empty) empty.remove();
        root.appendChild(wrap);
    }

    function scrollToBottom(el) {
        if (!el) return;
        el.scrollTop = el.scrollHeight;
    }

    window.initMessagesThread = function (config) {
        if (!config || !config.pollUrl || !config.sendUrl) return;
        window.__dmThreadConfig = config;

        var root = qs(config.scrollSelector || '.dm-thread-scroll');
        if (!root) return;

        var lastId = parseInt(config.lastMessageId, 10) || 0;
        var pollMs = parseInt(config.pollIntervalMs, 10) || 3500;

        function tick() {
            if (document.visibilityState === 'hidden') return;
            var sep = config.pollUrl.indexOf('?') >= 0 ? '&' : '?';
            var url = config.pollUrl + sep + 'after_id=' + encodeURIComponent(String(lastId));
            fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data || !data.ok || !data.messages) return;
                    var n = data.messages.length;
                    data.messages.forEach(function (m) {
                        appendMessage(root, m);
                        if (m.id > lastId) lastId = m.id;
                    });
                    if (n) scrollToBottom(root);
                })
                .catch(function () {});
        }

        setInterval(tick, pollMs);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') tick();
        });

        var form = qs(config.formSelector || '.dm-send-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                var ta = form.querySelector('textarea[name="body"]');
                if (!ta || !ta.value.trim()) return;
                e.preventDefault();
                var fd = new FormData(form);
                fetch(config.sendUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: fd,
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (data) {
                        if (!data || !data.ok || !data.message) return;
                        appendMessage(root, data.message);
                        if (data.message.id > lastId) lastId = data.message.id;
                        scrollToBottom(root);
                        ta.value = '';
                    })
                    .catch(function () {
                        form.submit();
                    });
            });
        }

        scrollToBottom(root);
    };
})();
