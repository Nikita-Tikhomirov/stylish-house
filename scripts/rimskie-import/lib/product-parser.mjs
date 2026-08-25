import { JSDOM } from 'jsdom';

import { resolveDonorUrl } from './donor-url-policy.mjs';

const attributeKeys = new Map([
    ['материал', 'material'],
    ['цвет', 'color'],
]);

const cyrillicTransliteration = new Map([
    ['а', 'a'], ['б', 'b'], ['в', 'v'], ['г', 'g'], ['д', 'd'], ['е', 'e'], ['ё', 'e'],
    ['ж', 'zh'], ['з', 'z'], ['и', 'i'], ['й', 'i'], ['к', 'k'], ['л', 'l'], ['м', 'm'],
    ['н', 'n'], ['о', 'o'], ['п', 'p'], ['р', 'r'], ['с', 's'], ['т', 't'], ['у', 'u'],
    ['ф', 'f'], ['х', 'kh'], ['ц', 'ts'], ['ч', 'ch'], ['ш', 'sh'], ['щ', 'shch'], ['ъ', ''],
    ['ы', 'y'], ['ь', ''], ['э', 'e'], ['ю', 'yu'], ['я', 'ya'],
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

    if (attributeKeys.has(normalizedLabel)) return attributeKeys.get(normalizedLabel);

    return [...normalizedLabel]
        .map((character) => cyrillicTransliteration.get(character) ?? character)
        .join('')
        .normalize('NFKD')
        .replace(/\p{Mark}/gu, '')
        .replace(/[^\p{Letter}\p{Number}]+/gu, '_')
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
    const approvedPageUrl = resolveDonorUrl(pageUrl, {
        kind: 'html',
        label: 'product page URL',
    });
    const document = new JSDOM(html, { url: approvedPageUrl }).window.document;
    const firstImage = document.querySelector('.product-gallery img, [data-gallery] img, .gallery img');

    return {
        externalId: externalIdFromDocument(document, approvedPageUrl),
        sourceUrl: approvedPageUrl,
        sourceTitle: normalizedText(document.querySelector('h1')),
        sourceDescription: normalizedText(document.querySelector('.product-description, [itemprop="description"]')),
        sourcePrice: normalizeMoney(document.querySelector('meta[itemprop="price"]')?.getAttribute('content')),
        firstImageUrl: firstImage?.getAttribute('src')
            ? resolveDonorUrl(firstImage.getAttribute('src'), {
                baseUrl: approvedPageUrl,
                kind: 'image',
                label: 'first image URL',
            })
            : null,
        attributes: readAttributes(document),
    };
}
