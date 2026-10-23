
import { getJson, postJson } from '../utils/fetch-json';

export function initNotificationPopover() {
    const root = document.querySelector('[data-notification-popover-root]');

    if (!root) {
        return;
    }

    const trigger = root.querySelector('[data-notification-popover-trigger]');
    const panel = root.querySelector('[data-notification-popover-panel]');
    const badge = root.querySelector('[data-notification-popover-badge]');
    const list = root.querySelector('[data-notification-popover-items]');
    const loading = root.querySelector('[data-notification-popover-loading]');
    const empty = root.querySelector('[data-notification-popover-empty]');
    const unreadCountLabel = root.querySelector('[data-notification-popover-unread-count]');
    const markAllButton = root.querySelector('[data-notification-popover-mark-all]');
    const template = root.querySelector('[data-notification-popover-row-template]');
    const tabs = root.querySelectorAll('[data-notification-popover-tab]');

    let currentTab = 'all';
    let unreadCount = Number.parseInt(badge?.textContent?.trim() ?? '0', 10) || 0;
    let isOpen = false;
    let isLoading = false;

    const showError = (message) => {
        list.replaceChildren();

        const item = document.createElement('li');
        item.className = 'p-4 text-center text-xs text-red-600';
        item.textContent = message;

        list.append(item);
        empty.classList.add('hidden');
    };

    const updateUnreadCount = (count) => {
        unreadCount = Math.max(0, Number(count) || 0);

        if (badge) {
            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            badge.classList.toggle('hidden', unreadCount === 0);
        }

        if (trigger) {
            trigger.setAttribute('aria-label', `通知 (${unreadCount} 件未読)`);
        }

        if (unreadCountLabel) {
            unreadCountLabel.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        }
    };

    const setTab = (tab) => {
        currentTab = tab;

        tabs.forEach((button) => {
            button.setAttribute(
                'aria-selected',
                String(button.dataset.notificationPopoverTab === tab),
            );
        });
    };

    const setOpen = (open) => {
        isOpen = open;

        panel.classList.toggle('hidden', !open);
        panel.style.display = open ? 'flex' : 'none';
        panel.classList.toggle('opacity-0', !open);
        panel.classList.toggle('-translate-y-1', !open);

        trigger.setAttribute('aria-expanded', String(open));

        if (open) {
            panel.classList.remove('hidden', 'opacity-0', '-translate-y-1');
            panel.style.display = 'flex';
        }
    };

    const renderNotifications = (notifications) => {
        list.replaceChildren();

        notifications.forEach((notification) => {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('[data-notification-popover-row]');
            const title = fragment.querySelector('[data-notification-popover-row-title]');
            const message = fragment.querySelector('[data-notification-popover-row-message]');
            const time = fragment.querySelector('[data-notification-popover-row-time]');
            const dot = fragment.querySelector('[data-notification-popover-row-dot]');
            const item = fragment.querySelector('li');
            item.dataset.notificationId = notification.id;

            title.textContent = notification.title ?? '通知';
            message.textContent = notification.message ?? '';
            time.textContent = notification.created_relative ?? '';

            const isUnread = notification.read_at === null;

            row.dataset.unread = String(isUnread);
            row.href = notification.url ?? '#';
            row.setAttribute('aria-label', title.textContent);

            dot.classList.toggle('hidden', !isUnread);
            title.classList.toggle('font-semibold', isUnread);

            list.append(fragment);
        });

        empty.classList.toggle('hidden', notifications.length > 0);
    };

    const loadNotifications = async () => {
        if (isLoading) {
            return;
        }

        isLoading = true;
        loading.classList.remove('hidden');
        empty.classList.add('hidden');

        try {
            const url = new URL('/api/v1/notifications', window.location.origin);

            if (currentTab !== 'all') {
                url.searchParams.set('tab', currentTab);
            }

            const result = await getJson(url.toString());

            renderNotifications(result.notifications ?? []);
            updateUnreadCount(result.unread_count ?? 0);
        } catch {
            showError('通知の取得に失敗しました。');
        } finally {
            loading.classList.add('hidden');
            isLoading = false;
        }
    };

    trigger.addEventListener('click', async () => {
        setOpen(!isOpen);

        if (isOpen) {
            await loadNotifications();
        }
    });

    tabs.forEach((button) => {
        button.addEventListener('click', async () => {
            setTab(button.dataset.notificationPopoverTab);
            await loadNotifications();
        });
    });

    markAllButton.addEventListener('click', async () => {
        markAllButton.disabled = true;

        try {
            const result = await postJson('/api/v1/notifications/read-all');

            updateUnreadCount(result.unread_count ?? 0);
            await loadNotifications();
        } catch {
            showError('通知を既読にできませんでした。');
        } finally {
            markAllButton.disabled = false;
        }
    });

    list.addEventListener('click', async (event) => {
        const row = event.target.closest('[data-notification-popover-row]');

        if (!row) {
            return;
        }

        event.preventDefault();

        const notification = row.closest('li');
        const notificationId = notification?.dataset.notificationId;

        if (!notificationId) {
            window.location.assign(row.href);
            return;
        }

        try {
            const result = await postJson(
                `/api/v1/notifications/${encodeURIComponent(notificationId)}/read`,
            );

            updateUnreadCount(result.unread_count ?? 0);
            window.location.assign(result.url);
        } catch {
            showError('通知を既読にできませんでした。');
        }
    });

    document.addEventListener('click', (event) => {
        if (isOpen && !root.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            setOpen(false);
            trigger.focus();
        }
    });
}
