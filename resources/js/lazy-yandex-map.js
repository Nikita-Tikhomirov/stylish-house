let yandexMapsPromise;

export const yandexMapsUrl = (apiKey) => (
    `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(apiKey)}&lang=ru_RU`
);

export const createMapObserver = (element, load, Observer = globalThis.IntersectionObserver) => {
    if (!Observer) {
        load();
        return null;
    }

    const observer = new Observer((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) {
            return;
        }

        observer.disconnect();
        load();
    }, { rootMargin: '600px 0px' });

    observer.observe(element);
    return observer;
};

const loadYandexMaps = (apiKey) => {
    if (globalThis.ymaps) {
        return Promise.resolve(globalThis.ymaps);
    }

    if (!yandexMapsPromise) {
        yandexMapsPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = yandexMapsUrl(apiKey);
            script.async = true;
            script.onload = () => globalThis.ymaps.ready(() => resolve(globalThis.ymaps));
            script.onerror = () => reject(new Error('Не удалось загрузить Яндекс Карты'));
            document.head.append(script);
        });
    }

    return yandexMapsPromise;
};

const renderMap = async (element) => {
    if (element.dataset.mapLoaded === 'true') {
        return;
    }

    element.dataset.mapLoaded = 'true';
    element.setAttribute('aria-busy', 'true');

    try {
        const ymaps = await loadYandexMaps(element.dataset.mapApiKey);
        const center = [55.812585, 37.698591];
        element.replaceChildren();

        const map = new ymaps.Map(element, {
            center,
            zoom: 14,
            controls: ['zoomControl', 'fullscreenControl'],
        });
        const placemark = new ymaps.Placemark(center, {
            balloonContentHeader: 'Стильный Дом',
            balloonContentBody: 'г. Москва, ул. Краснобогатырская, 19а',
            balloonContentFooter: '<a href="tel:+79060609989">+7 (906) 060-99-89</a>',
            hintContent: 'Стильный Дом, ул. Краснобогатырская, 19а',
        }, {
            preset: 'islands#blueDotIconWithCaption',
            iconCaption: 'Стильный Дом',
        });

        map.geoObjects.add(placemark);
        element.removeAttribute('aria-busy');
    } catch (error) {
        element.dataset.mapLoaded = 'false';
        element.removeAttribute('aria-busy');
        element.querySelector('[data-map-status]')?.removeAttribute('hidden');
        console.error(error);
    }
};

export const initializeLazyYandexMaps = () => {
    document.querySelectorAll('[data-yandex-map]').forEach((element) => {
        const load = () => renderMap(element);
        createMapObserver(element, load);
        element.querySelector('[data-map-load]')?.addEventListener('click', load, { once: true });
    });
};

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', initializeLazyYandexMaps);
}
