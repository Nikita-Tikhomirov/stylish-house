import { JSDOM } from 'jsdom';

const attributeKeys = new Map([
    ['материал', 'material'],
    ['цвет', 'color'],
]);

function normalizedText(node) {
    return node?.textContent?.replace(/\s+/g, ' ').trim() || '';
}

function normalizeMoney(value) {
    const numeric = String(value ?? '')
        .replace(/\s/g, '')
        .replace(',', '.')
        .replace(/[^\d.]/g, '');
    const amount = Number(numeric);

    return Number.isFinite(amount) ? amount.toFixed(2) : null;
}

function externalIdFromDocument(document, pageUrl) {
    const declaredId = document.querySelector('[data-product-id]')?.getAttribute('data-product-id')?.trim();
    if (declaredId) return declaredId;

    const code = normalizedText(document.querySelector('.product-code, [itemprop="sku"]'));
    const codeMatch = code.match(/\d+/);
    if (codeMatch) return codeMatch[0];

    return new URL(pageUrl).pathname.match(/\/products\/(\d+)/)?.[1] || null;
}

function attributeKey(label) {
    const normalizedLabel = label.replace(/\s+/g, ' ').trim().toLowerCase();

    return attributeKeys.get(normalizedLabel) || normalizedLabel
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function readAttributes(document) {
    const attributes = {};

    for (const row of document.querySelectorAll('.characteristics tr')) {
        const key = attributeKey(normalizedText(row.querySelector('th, dt, .name')));
        const value = normalizedText(row.querySelector('td, dd, .value'));
        if (!key || !value) continue;

        const values = attributes[key] || [];
        if (!values.includes(value)) values.push(value);
        attributes[key] = values;
    }

    return attributes;
}

export function parseProductPage(html, pageUrl) {
    const document = new JSDOM(html, { url: pageUrl }).window.document;
    const firstImage = document.querySelector('.product-gallery img, [data-gallery] img, .gallery img');

    return {
        externalId: externalIdFromDocument(document, pageUrl),
        sourceUrl: pageUrl,
        sourceTitle: normalizedText(document.querySelector('h1')),
        sourceDescription: normalizedText(document.querySelector('.product-description, [itemprop="description"]')),
        sourcePrice: normalizeMoney(document.querySelector('meta[itemprop="price"]')?.getAttribute('content')),
        firstImageUrl: firstImage?.getAttribute('src')
            ? new URL(firstImage.getAttribute('src'), pageUrl).href
            : null,
        attributes: readAttributes(document),
    };
}
