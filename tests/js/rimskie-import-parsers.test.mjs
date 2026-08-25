import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import { parseCategoryPage } from '../../scripts/rimskie-import/lib/category-parser.mjs';
import { parseProductPage } from '../../scripts/rimskie-import/lib/product-parser.mjs';

const categoryHtml = await readFile(
    new URL('../fixtures/rimskie-import/category-page.html', import.meta.url),
    'utf8',
);
const productHtml = await readFile(
    new URL('../fixtures/rimskie-import/product-page.html', import.meta.url),
    'utf8',
);

test('rimskie import parser category deduplicates cards and resolves donor URLs', () => {
    const result = parseCategoryPage(
        categoryHtml,
        'https://rimskie.com/catalog/rimskie-shtory/example',
    );

    assert.deepEqual(result.products, [{
        externalId: '11889',
        sourceUrl: 'https://rimskie.com/products/11889-rimskaya-shtora-kortin-velvet-belosnezhnyy-dlya-proema',
        sourceTitle: 'Римская штора KORTIN VELVET белоснежный',
        sourcePrice: '2708.00',
        cardImageUrl: 'https://rimskie.com/media/output/card.webp',
    }]);
    assert.equal(result.nextPageUrl, 'https://rimskie.com/catalog/rimskie-shtory/example?page=2');
    assert.equal(result.pageNumber, 1);
});

test('rimskie import parser product keeps the first gallery photo and factual attributes', () => {
    const result = parseProductPage(productHtml, 'https://rimskie.com/products/11889-example');

    assert.deepEqual(result, {
        externalId: '11889',
        sourceUrl: 'https://rimskie.com/products/11889-example',
        sourceTitle: 'Римская штора KORTIN VELVET белоснежный',
        sourceDescription: 'Плотная штора для аккуратного оформления окна.',
        sourcePrice: '2708.00',
        firstImageUrl: 'https://rimskie.com/media/output/first.webp',
        attributes: {
            material: ['полиэстер'],
            color: ['белый'],
        },
    });
});

test('rimskie import parser source contract has 46 unique rimskie.com collection definitions', async () => {
    const sources = JSON.parse(await readFile(
        new URL('../../config/rimskie-import-sources.json', import.meta.url),
        'utf8',
    ));

    assert.equal(sources.length, 46);
    assert.equal(new Set(sources.map((source) => source.source_url)).size, 46);
    assert.equal(new Set(sources.map((source) => source.target_slug)).size, 46);
    assert.equal(new Set(sources.map((source) => source.sort_order)).size, 46);

    for (const [index, source] of sources.entries()) {
        const sourceUrl = new URL(source.source_url);
        assert.equal(source.enabled, true);
        assert.equal(source.sort_order, index + 1);
        assert.equal(sourceUrl.protocol, 'https:');
        assert.equal(sourceUrl.hostname, 'rimskie.com');
        assert.equal(source.target_slug, sourceUrl.pathname.split('/').filter(Boolean).at(-1));
    }
});
