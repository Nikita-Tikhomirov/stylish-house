import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const templates = [
    'resources/views/front/product.blade.php',
    'resources/views/front/product-plumbing.blade.php',
];

for (const template of templates) {
    test(`${template} links same-model thumbnails to their product pages`, async () => {
        const source = await readFile(new URL(`../../${template}`, import.meta.url), 'utf8');
        const gallery = source.match(/<div class="prodForm__bar">([\s\S]*?)<\/div>/)?.[1] ?? '';

        assert.match(gallery, /class="prodForm__productLink"/);
        assert.match(gallery, /href="\{\{ \\App\\Support\\CanonicalUrl::route\('product\.show'/);
        assert.match(gallery, /aria-label="Открыть товар \{\{ \$sameProduct->h1 \}\}"/);
        assert.match(gallery, /<a[\s\S]*?<img[\s\S]*?<\/a>/);
    });
}

test('the hover image cannot cover gallery links on narrow screens', async () => {
    const source = await readFile(new URL('../../resources/css/main.css', import.meta.url), 'utf8');

    assert.match(source, /\.prodForm__imgWrap\s*\{[^}]*overflow:\s*hidden/s);
    assert.match(source, /\.prodForm__imgWrap img:nth-child\(2\)\s*\{[^}]*pointer-events:\s*none/s);
});
