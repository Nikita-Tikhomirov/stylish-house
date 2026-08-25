import { JSDOM } from 'jsdom';

import { resolveDonorUrl } from './donor-url-policy.mjs';

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

function readBackgroundImage(style) {
    const match = style?.match(/background-image\s*:\s*url\(\s*['"]?([^'")]+)['"]?\s*\)/i);

    return match?.[1] || null;
}

function readCategoryCard(card, link, pageUrl) {
    const externalId = card.getAttribute('data-id').trim();
    const imageNode = card.querySelector('.product-image-background');
    const rawCardImageUrl = readBackgroundImage(imageNode?.getAttribute('style'));

    return {
        externalId,
        sourceUrl: resolveDonorUrl(link.getAttribute('href'), {
            baseUrl: pageUrl,
            kind: 'product',
            label: 'product URL',
        }),
        sourceTitle: normalizedText(card.querySelector('.product-title')),
        sourcePrice: normalizeMoney(card.querySelector('meta[itemprop="lowPrice"]')?.getAttribute('content')),
        cardImageUrl: rawCardImageUrl
            ? resolveDonorUrl(rawCardImageUrl, {
                baseUrl: pageUrl,
                kind: 'image',
                label: 'card image URL',
            })
            : null,
    };
}

function resolveNextPage(document, pageUrl) {
    const href = document.querySelector('a[rel="next"]')?.getAttribute('href');
    if (!href) return null;
    const nextPageUrl = resolveDonorUrl(href, {
        baseUrl: pageUrl,
        kind: 'category',
        label: 'next-page URL',
    });
    if (new URL(nextPageUrl).pathname !== new URL(pageUrl).pathname) {
        throw new Error('Category pagination must stay on the exact source path');
    }

    return nextPageUrl;
}

function readPageNumber(pageUrl) {
    const page = Number(new URL(pageUrl).searchParams.get('page'));

    return Number.isInteger(page) && page > 0 ? page : 1;
}

export function parseCategoryPage(html, pageUrl) {
    const approvedPageUrl = resolveDonorUrl(pageUrl, {
        kind: 'category',
        label: 'category page URL',
    });
    const document = new JSDOM(html, { url: approvedPageUrl }).window.document;
    const unique = new Map();

    for (const card of document.querySelectorAll('.product[data-id]')) {
        const link = card.querySelector('.product-link');
        const externalId = card.getAttribute('data-id')?.trim();
        if (!externalId || !link) continue;
        unique.set(externalId, readCategoryCard(card, link, approvedPageUrl));
    }

    return {
        products: [...unique.values()],
        nextPageUrl: resolveNextPage(document, approvedPageUrl),
        pageNumber: readPageNumber(approvedPageUrl),
    };
}
