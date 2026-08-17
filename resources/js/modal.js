const MODAL_CLOSE_SELECTOR = '[data-modal-close], .modal__close';

const restorePageScroll = (documentRef) => {
    documentRef.body.style.overflow = '';
    documentRef.body.style.paddingRight = '';
    documentRef.documentElement.style.overflow = '';
};

export const initModalCloseDelegation = (
    documentRef = document,
    schedule = setTimeout,
) => {
    documentRef.addEventListener('click', (event) => {
        const closeControl = event.target?.closest?.(MODAL_CLOSE_SELECTOR);
        const modal = closeControl?.closest?.('.modal');

        if (!modal) {
            return;
        }

        const popup = modal.querySelector('.modal__container > :not(.modal__close)');
        if (!popup) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        modal.classList.add('fadeOut');

        schedule(() => {
            popup.style.display = '';
            documentRef.body.appendChild(popup);
            modal.remove();
            restorePageScroll(documentRef);
        }, 450);
    }, true);
};
