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
            svetopronitsaemost: ['40%'],
        },
    });
});

test('rimskie import parser supports the current product description and image markup', () => {
    const currentProductHtml = `<!doctype html><html><head>
        <meta itemprop="description" content="SEO-анонс карточки">
        <meta itemprop="price" content="2476">
    </head><body>
        <div class="product-code">Код товара: 11889</div>
        <h1>Римская штора вельвет белоснежный, для проёма</h1>
        <img src="/uploads/settings/logo.svg">
        <img src="/media/output/first-current.webp"
             alt="Римская штора вельвет белоснежный, для проёма">
        <div class="prices"><span class="price current">4 799 ₽</span></div>
        <section class="text" itemprop="description">
            Классическая римская штора из белоснежного вельвета.
        </section>
        <meta itemprop="description" content="Описание рекомендованного товара">
    </body></html>`;

    const result = parseProductPage(
        currentProductHtml,
        'https://rimskie.com/products/11889-current',
    );

    assert.equal(
        result.sourceDescription,
        'Классическая римская штора из белоснежного вельвета.',
    );
    assert.equal(
        result.firstImageUrl,
        'https://rimskie.com/media/output/first-current.webp',
    );
    assert.equal(result.sourcePrice, '4799.00');
});

test('rimskie import parser reads current reversed characteristic cells and list values', () => {
    const currentProductHtml = `<!doctype html><html><body>
        <div class="product-code">Код товара: 463</div>
        <h1>Римская штора день-ночь, лён синий</h1>
        <table class="characteristics">
            <tr><td>Уровень затемнения:</td><th>Лёгкое затемнение</th></tr>
            <tr><td>Установка:</td><th>На всё окно</th></tr>
            <tr><td>Тип римской шторы:</td><th>День-ночь, С электроприводом</th></tr>
            <tr><td>Помещение:</td><th>В гостиную, В кухню, В офис</th></tr>
        </table>
    </body></html>`;

    const result = parseProductPage(
        currentProductHtml,
        'https://rimskie.com/products/463-current',
    );

    assert.deepEqual(result.attributes, {
        uroven_zatemneniya: ['Лёгкое затемнение'],
        ustanovka: ['На всё окно'],
        tip_rimskoi_shtory: ['День-ночь', 'С электроприводом'],
        pomeshchenie: ['В гостиную', 'В кухню', 'В офис'],
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
