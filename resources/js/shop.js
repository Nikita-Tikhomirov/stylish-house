import {
    collectCartOptions,
    collectConfiguration,
} from './shop-options.js';
import './header-navigation.js';
import '../css/header-navigation.css';

const FAVORITES_KEY = 'stylish-house-favorites';
let currentFavoriteIds = [];

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const isAuthenticated = () => document.querySelector('meta[name="shop-authenticated"]')?.content === '1';

const storedFavorites = () => {
    try {
        const value = JSON.parse(localStorage.getItem(FAVORITES_KEY) || '[]');
        return Array.isArray(value) ? [...new Set(value.map(Number).filter(Boolean))] : [];
    } catch {
        return [];
    }
};

const saveStoredFavorites = (ids) => {
    localStorage.setItem(FAVORITES_KEY, JSON.stringify([...new Set(ids.map(Number).filter(Boolean))]));
};

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers || {}),
        },
    });

    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }

    return response.json();
};

const favoriteButtons = () => document.querySelectorAll('[data-favorite-product]');

const renderFavorites = (ids) => {
    currentFavoriteIds = [...new Set(ids.map(Number).filter(Boolean))];
    const selected = new Set(currentFavoriteIds);
    favoriteButtons().forEach((button) => {
        const active = selected.has(Number(button.dataset.favoriteProduct));
        button.classList.toggle('is-favorite', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');

        const icon = button.querySelector('.fa-heart');
        icon?.classList.toggle('fas', active);
        icon?.classList.toggle('far', !active);

        const tooltip = button.querySelector('.bigProdCard__toolTip');
        if (tooltip) {
            tooltip.textContent = active ? 'Удалить из избранного' : 'Добавить в избранное';
        }
    });
};

const initializeFavorites = async () => {
    let ids = storedFavorites();

    if (isAuthenticated()) {
        if (ids.length) {
            const synced = await request('/favorites/sync', {
                method: 'POST',
                body: JSON.stringify({ product_ids: ids }),
            });
            ids = synced.product_ids || [];
            localStorage.removeItem(FAVORITES_KEY);
        } else {
            const result = await request('/favorites');
            ids = result.product_ids || [];
        }
    }

    renderFavorites(ids);
};

const toggleFavorite = async (button) => {
    const productId = Number(button.dataset.favoriteProduct);
    if (!productId) {
        return;
    }

    let ids = storedFavorites();
    const currentlyFavorite = button.classList.contains('is-favorite');

    if (isAuthenticated()) {
        const result = await request(`/favorites/${productId}`, {
            method: currentlyFavorite ? 'DELETE' : 'POST',
            body: JSON.stringify({}),
        });
        currentlyFavorite
            ? document.querySelector(`[data-favorite-card="${productId}"]`)?.remove()
            : null;
        const refreshed = await request('/favorites');
        ids = refreshed.product_ids || [];
        renderFavorites(ids);
        return result;
    }

    ids = currentlyFavorite ? ids.filter((id) => id !== productId) : [...ids, productId];
    saveStoredFavorites(ids);
    renderFavorites(ids);
};

window.Shop = {
    collectCartOptions,
    collectConfiguration,
};

document.addEventListener('DOMContentLoaded', () => {
    initializeFavorites().catch((error) => console.error('Favorites initialization failed', error));

    const observer = new MutationObserver((mutations) => {
        if (mutations.some((mutation) => [...mutation.addedNodes].some(
            (node) => node instanceof Element && (node.matches('[data-favorite-product]') || node.querySelector('[data-favorite-product]'))
        ))) {
            renderFavorites(currentFavoriteIds);
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-favorite-product]');
    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    toggleFavorite(button).catch((error) => console.error('Favorite update failed', error));
});
