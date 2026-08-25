import { JSDOM } from 'jsdom';

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

    return {
        externalId,
        sourceUrl: new URL(link.getAttribute('href'), pageUrl).href,
        sourceTitle: normalizedText(card.querySelector('.product-title')),
        sourcePrice: normalizeMoney(card.querySelector('meta[itemprop="lowPrice"]')?.getAttribute('content')),
        cardImageUrl: readBackgroundImage(imageNode?.getAttribute('style'))
            ? new URL(readBackgroundImage(imageNode.getAttribute('style')), pageUrl).href
            : null,
    };
}

function resolveNextPage(document, pageUrl) {
    const href = document.querySelector('a[rel="next"]')?.getAttribute('href');

    return href ? new URL(href, pageUrl).href : null;
}

function readPageNumber(pageUrl) {
    const page = Number(new URL(pageUrl).searchParams.get('page'));

    return Number.isInteger(page) && page > 0 ? page : 1;
}

export function parseCategoryPage(html, pageUrl) {
    const document = new JSDOM(html, { url: pageUrl }).window.document;
    const unique = new Map();

    for (const card of document.querySelectorAll('.product[data-id]')) {
        const link = card.querySelector('.product-link');
        const externalId = card.getAttribute('data-id')?.trim();
        if (!externalId || !link) continue;
        unique.set(externalId, readCategoryCard(card, link, pageUrl));
    }

    return {
        products: [...unique.values()],
        nextPageUrl: resolveNextPage(document, pageUrl),
        pageNumber: readPageNumber(pageUrl),
    };
}
