import assert from 'node:assert/strict';
import test from 'node:test';

let buildProductPriceRequestUrl;

try {
    ({ buildProductPriceRequestUrl } = await import('../../resources/js/product-price-request.js'));
} catch {
    // The first RED run proves the shared request contract does not exist yet.
}

test('buildProductPriceRequestUrl includes the model id and product title', () => {
    assert.equal(typeof buildProductPriceRequestUrl, 'function');

    const requestUrl = buildProductPriceRequestUrl({
        width: 800,
        height: 700,
        model: 'Горизонтальные алюминиевые',
        control: false,
        cloth: '1 категория',
        modelId: 66,
        prodTitle: 'Алюминиевые 25 мм 25-100',
    });
    const parsedUrl = new URL(requestUrl, 'https://stylish-house.test');

    assert.equal(parsedUrl.pathname, '/sheet-names');
    assert.equal(parsedUrl.searchParams.get('width'), '800');
    assert.equal(parsedUrl.searchParams.get('height'), '700');
    assert.equal(parsedUrl.searchParams.get('model'), 'Горизонтальные алюминиевые');
    assert.equal(parsedUrl.searchParams.get('control'), 'false');
    assert.equal(parsedUrl.searchParams.get('cloth'), '1 категория');
    assert.equal(parsedUrl.searchParams.get('modelId'), '66');
    assert.equal(parsedUrl.searchParams.get('prodTitle'), 'Алюминиевые 25 мм 25-100');
    assert.equal(requestUrl.includes('undefined'), false);
});
