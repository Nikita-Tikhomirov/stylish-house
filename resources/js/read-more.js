export const toggleReadMore = (button) => {
    const container = button.closest('[data-read-more]');
    if (!container) {
        return;
    }

    const expanded = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!expanded));
    button.textContent = expanded ? 'Подробнее' : 'Скрыть';
    container.classList.toggle('readMore--expanded', !expanded);
};

export const initReadMore = (scope = document) => {
    scope.querySelectorAll('[data-read-more-toggle]').forEach((button) => {
        const container = button.closest('[data-read-more]');
        const content = container?.querySelector('[data-read-more-content]');

        if (!content) {
            button.hidden = true;
            return;
        }

        button.hidden = content.scrollHeight <= content.clientHeight;
        button.addEventListener('click', () => toggleReadMore(button));
    });
};
