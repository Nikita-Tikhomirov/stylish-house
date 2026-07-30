export function matchesNavigationQuery(label, query) {
    const normalizedQuery = String(query || '').trim().toLocaleLowerCase('ru-RU');
    return normalizedQuery === '' || String(label || '').toLocaleLowerCase('ru-RU').includes(normalizedQuery);
}

export function toggleAccordionKey(currentKey, requestedKey) {
    return currentKey === requestedKey ? null : requestedKey;
}

export function syncDisclosure(trigger, panel, expanded) {
    trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    panel.hidden = !expanded;
}

function setActiveTab(navigation, tabId) {
    navigation.querySelectorAll('[data-navigation-tab]').forEach((button) => {
        const active = button.dataset.navigationTab === tabId;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
        button.tabIndex = active ? 0 : -1;
    });
    navigation.querySelectorAll('[data-navigation-tab-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.navigationTabPanel !== tabId;
    });
}

function filterLinks(navigation, query) {
    navigation.querySelectorAll('[data-navigation-link]').forEach((link) => {
        link.hidden = !matchesNavigationQuery(link.dataset.navigationLabel || link.textContent, query);
    });

    navigation.querySelectorAll('[data-navigation-section]').forEach((section) => {
        const links = [...section.querySelectorAll('[data-navigation-link]')];
        section.hidden = links.length > 0 && links.every((link) => link.hidden);
    });

    navigation.querySelectorAll('[data-navigation-empty]').forEach((empty) => {
        const panel = empty.closest('[data-navigation-tab-panel]');
        const links = [...panel.querySelectorAll('[data-navigation-link]')];
        empty.hidden = links.some((link) => !link.hidden);
    });
}

export function initHeaderNavigation(root = document) {
    root.querySelectorAll('[data-header-navigation]').forEach((navigation) => {
        const trigger = navigation.querySelector('[data-navigation-toggle]');
        const panel = navigation.querySelector('[data-navigation-panel]');
        const search = navigation.querySelector('[data-navigation-search]');
        const mobileBurger = document.querySelector('.header__mobileBurger');
        let accordionKey = null;
        let lastTrigger = trigger;

        const isOpen = () => trigger.getAttribute('aria-expanded') === 'true';
        const open = (source = trigger) => {
            lastTrigger = source;
            syncDisclosure(trigger, panel, true);
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('is-navigation-open');
            const firstTab = navigation.querySelector('[data-navigation-tab]');
            if (firstTab) setActiveTab(navigation, firstTab.dataset.navigationTab);
            if (window.matchMedia('(min-width: 1024px)').matches) search?.focus();
        };
        const close = (restoreFocus = true) => {
            syncDisclosure(trigger, panel, false);
            panel.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('is-navigation-open');
            filterLinks(navigation, '');
            if (search) search.value = '';
            if (restoreFocus) lastTrigger?.focus?.();
        };

        trigger.addEventListener('click', () => isOpen() ? close(false) : open(trigger));
        mobileBurger?.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 1023px)').matches) isOpen() ? close(false) : open(mobileBurger);
        });
        navigation.querySelectorAll('[data-navigation-close]').forEach((button) => button.addEventListener('click', () => close()));
        navigation.querySelectorAll('[data-navigation-tab]').forEach((button) => {
            button.addEventListener('click', () => setActiveTab(navigation, button.dataset.navigationTab));
        });
        navigation.querySelectorAll('[data-mobile-accordion-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const group = button.closest('[data-mobile-accordion]');
                accordionKey = toggleAccordionKey(accordionKey, group.dataset.mobileAccordion);
                navigation.querySelectorAll('[data-mobile-accordion]').forEach((accordion) => {
                    const expanded = accordion.dataset.mobileAccordion === accordionKey;
                    syncDisclosure(
                        accordion.querySelector('[data-mobile-accordion-toggle]'),
                        accordion.querySelector('[data-mobile-accordion-panel]'),
                        expanded
                    );
                });
            });
        });
        search?.addEventListener('input', () => filterLinks(navigation, search.value));
        navigation.addEventListener('click', (event) => {
            if (event.target.closest('a')) close(false);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen()) close();
        });
        window.addEventListener('resize', () => {
            if (isOpen() && window.innerWidth >= 1024) document.querySelector('.mobileMenu')?.classList.remove('active');
        });
    });
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => initHeaderNavigation());
}
