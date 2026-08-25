# Roman Blinds Donor Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a controlled staging pipeline that slowly collects the 46 approved Roman-blind donor sections, stores each unique item and its first photo once, rewrites factual content, supports many-to-many filtering, and publishes only manually approved records after a fresh database backup.

**Architecture:** A local Node.js/Playwright collector writes a versioned, resumable package without touching production. Laravel validates and ingests that package into private staging tables, exposes review controls in the existing admin area, and publishes approved records transactionally into canonical products, SEO collections, attributes, and local media. Existing calculator-backed products keep their current behavior; imported products have `calculator_enabled=false` and use a request-price CTA.

**Tech Stack:** Node.js 22, `playwright-core`, Node built-in test runner, PHP 8.1+/Laravel 10, Eloquent, Blade, PHPUnit 10, MySQL/SQLite-compatible migrations, existing Vite assets and `ProductImageThumbnailService`.

**Spec:** `docs/superpowers/specs/2026-08-25-rimskie-donor-import-design.md`

## Global Constraints

- Collector concurrency is exactly `1`.
- Default HTML delay is random `20–40` seconds; default image delay is random `10–20` seconds.
- Default donor-origin ceiling is `120` requests per rolling hour.
- A visible BotHunt/challenge pauses immediately. Ordinary `403`, `429`, timeout, and network failures use `2`, `5`, then `15` minute backoff; the third consecutive failure requires an explicit resume and cannot cause a fourth automatic request after restart.
- A completed URL or downloaded image is never requested again when resuming a run.
- Only the actual WebP bytes of the first product photo are saved; URL-only records and gallery images are not collected.
- Local collector state, browser profile, checkpoints, JSON/NDJSON, export, and downloaded photos must use the explicit root `G:\stylish-house-data\rimskie-imports`; the CLI requires `--data-root` or `RIMSKIE_IMPORT_DATA_ROOT` and rejects drive `C:` before writing.
- Donor price and raw donor copy remain private staging data and never appear on public pages.
- One donor product is stored once and may belong to any number of the 46 SEO collections.
- Rewritten copy may use only facts present in source data, must remove donor branding, and suspicious output is marked `needs_review`.
- Nothing is public before manual approval; publication is transactionally idempotent and requires a fresh verified database backup.
- Imported products have `calculator_enabled=false`, `min_price=null`, no Excel-cache calculation, and no add-to-cart action.
- Canonical imported product URLs use `/story/rimskieshtory/<product-slug>/`; collection URLs use `/story/<target-slug>/`, all with trailing slashes.
- Filter URL parameters receive `noindex, follow`; clean collections and ordinary `?page=N` pagination remain indexable.
- All new source/config/code files are UTF-8; code identifiers and filenames are ASCII.
- No scheduler, cron job, Windows service, or automatic publication is added.

## File Structure

### Collector boundary

- `config/rimskie-import-sources.json` — single versioned source of truth for the 46 labels, donor URLs, target slugs, enable flags, and order.
- `scripts/rimskie-import/lib/category-parser.mjs` — pure category HTML parser.
- `scripts/rimskie-import/lib/product-parser.mjs` — pure product HTML parser and attribute normalization input.
- `scripts/rimskie-import/lib/run-store.mjs` — atomic checkpoints, NDJSON append, resumable queues, and package export.
- `scripts/rimskie-import/lib/request-policy.mjs` — delay, rolling hourly budget, backoff, and auto-pause decisions.
- `scripts/rimskie-import/lib/playwright-transport.mjs` — persistent headed Chrome profile and donor-only resource policy.
- `scripts/rimskie-import/lib/collector.mjs` — category/product/image state machine.
- `scripts/rimskie-import/cli.mjs` — `start/status/pause/resume/stop/export/dry-run` CLI.
- `tests/js/rimskie-import-*.test.mjs` and `tests/fixtures/rimskie-import/*.html` — deterministic collector contract tests.

### Laravel staging and publication boundary

- `database/migrations/2026_08_25_000000_create_catalog_import_staging_tables.php` — runs, sources, items, and staging memberships.
- `database/migrations/2026_08_25_000100_create_catalog_attribute_tables.php` — normalized attribute definitions/values and staging/product pivots.
- `database/migrations/2026_08_25_000200_add_catalog_import_fields_to_products_and_subcategories.php` — source metadata, calculator flag, collection flag, publication/rollback ownership, and collection product pivot.
- `app/Models/CatalogImport*.php`, `CatalogAttribute.php`, `CatalogAttributeValue.php` — Eloquent aggregate and relations.
- `app/Services/CatalogImport/CatalogImportPackageValidator.php` — strict schema/path/count validation.
- `app/Services/CatalogImport/CatalogImportIngestor.php` — idempotent staging upsert.
- `app/Services/CatalogImport/ProductContentRewriter.php` and `TemplateProductRewriter.php` — replaceable factual rewrite contract and implementation.
- `app/Services/CatalogImport/LandingContentRewriter.php` and `TemplateLandingRewriter.php` — factual landing drafts from the approved source labels, without donor body copy.
- `app/Services/CatalogImport/CatalogDatabaseBackupService.php` — private gzip dump plus SHA-256 manifest.
- `app/Services/CatalogImport/CatalogImportPublisher.php` and `CatalogImportRollback.php` — guarded publication and run-scoped rollback.
- `app/Console/Commands/IngestCatalogImport.php` and `BackupCatalogDatabase.php` — operator entry points.

### Admin and storefront boundary

- `app/Http/Controllers/CatalogImportController.php` — list/review/edit/approve/reject/publish/rollback actions.
- `resources/views/admin/catalog-imports/*.blade.php` — progress, comparisons, filters, preflight, and action forms.
- `app/Services/Catalog/CatalogCollectionQuery.php` — collection membership scope and normalized filter semantics.
- `app/Services/Catalog/CatalogFilterOptions.php` — values/counts for the active collection.
- `resources/views/front/partials/catalog-attribute-filters.blade.php` — filter controls.
- `resources/views/front/partials/rimskie-seo-links.blade.php` — dedicated chips for the 46 collection pages.
- Existing `SubcategoryController`, `ProductController`, models, Blade views, `IndexingPolicy`, `GenerateSitemap`, routes, and admin sidebar receive focused integration changes only.

---

### Task 1: Versioned source contract, HTML parsers, and atomic run store

**Files:**
- Create: `config/rimskie-import-sources.json`
- Create: `scripts/rimskie-import/lib/category-parser.mjs`
- Create: `scripts/rimskie-import/lib/product-parser.mjs`
- Create: `scripts/rimskie-import/lib/run-store.mjs`
- Create: `tests/fixtures/rimskie-import/category-page.html`
- Create: `tests/fixtures/rimskie-import/product-page.html`
- Create: `tests/js/rimskie-import-parsers.test.mjs`
- Create: `tests/js/rimskie-import-run-store.test.mjs`
- Modify: `package.json`
- Modify: `package-lock.json`

**Interfaces:**
- Produces: `parseCategoryPage(html: string, pageUrl: string): CategoryPageResult` where `CategoryPageResult` has `products`, `nextPageUrl`, and `pageNumber`.
- Produces: `parseProductPage(html: string, pageUrl: string): ProductPageResult` where `ProductPageResult` has `externalId`, `sourceUrl`, `sourceTitle`, `sourceDescription`, `sourcePrice`, `firstImageUrl`, and `attributes: Record<string,string[]>`.
- Produces: `RunStore.open({ rootDir, runId })`, `readState()`, `checkpoint(state)`, `saveSource(slug, data)`, `saveProduct(externalId, data)`, `appendMembership(record)`, `appendEvent(record)`, and `exportManifest()`.
- Produces: manifest schema version string `stylish-house.catalog-import/v1` consumed by Task 4.

- [ ] **Step 1: Add synthetic fixtures that mirror the observed donor boundaries**

`category-page.html` contains two `.product` cards, repeated links for deduplication, a `meta[itemprop="lowPrice"]`, CSS background image, and a next-page anchor. `product-page.html` contains donor code `11889`, H1, description, a gallery whose first `<img>` differs from the card image, and repeated characteristic rows.

```html
<article class="product" data-id="11889">
  <a class="product-link" href="/products/11889-rimskaya-shtora-kortin-velvet-belosnezhnyy-dlya-proema">
    <span class="product-image-background" style="background-image:url('/media/output/card.webp')"></span>
    <span class="product-title">Римская штора KORTIN VELVET белоснежный</span>
    <meta itemprop="lowPrice" content="2708">
  </a>
</article>
<a rel="next" href="?page=2">Следующая</a>
```

- [ ] **Step 2: Write parser tests with hand-derived expected values**

```js
test('category parser deduplicates cards and resolves donor URLs', async () => {
  const result = parseCategoryPage(html, 'https://rimskie.com/catalog/rimskie-shtory/example');
  assert.deepEqual(result.products[0], {
    externalId: '11889',
    sourceUrl: 'https://rimskie.com/products/11889-rimskaya-shtora-kortin-velvet-belosnezhnyy-dlya-proema',
    sourceTitle: 'Римская штора KORTIN VELVET белоснежный',
    sourcePrice: '2708.00',
    cardImageUrl: 'https://rimskie.com/media/output/card.webp',
  });
  assert.equal(result.nextPageUrl, 'https://rimskie.com/catalog/rimskie-shtory/example?page=2');
});

test('product parser keeps only the first gallery photo and factual attributes', async () => {
  const result = parseProductPage(html, 'https://rimskie.com/products/11889-example');
  assert.equal(result.externalId, '11889');
  assert.equal(result.firstImageUrl, 'https://rimskie.com/media/output/first.webp');
  assert.deepEqual(result.attributes.material, ['полиэстер']);
  assert.deepEqual(result.attributes.color, ['белый']);
});
```

- [ ] **Step 3: Run parser tests and verify the missing-module failure**

Run: `npm run test:shop -- --test-name-pattern="rimskie import parser"`

Expected: FAIL with `ERR_MODULE_NOT_FOUND` for the parser modules.

- [ ] **Step 4: Declare the DOM parser and implement pure parsers with URL and money normalization**

Run: `npm install --save-dev jsdom@20.0.3`

```js
import { JSDOM } from 'jsdom';

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
```

The implementation must use `jsdom` only as a declared parser dependency; it must not launch a browser while parsing and must not add a second HTML parsing dependency.

- [ ] **Step 5: Write failing run-store tests for atomic resume and deduplication**

```js
test('checkpoint survives reopen and membership append is idempotent', async () => {
  const store = await RunStore.open({ rootDir, runId: 'run-001' });
  await store.checkpoint({ status: 'running', nextProductIndex: 4 });
  await store.appendMembership({ sourceSlug: 'white', externalId: '11889' });
  await store.appendMembership({ sourceSlug: 'white', externalId: '11889' });

  const reopened = await RunStore.open({ rootDir, runId: 'run-001' });
  assert.equal((await reopened.readState()).nextProductIndex, 4);
  assert.equal((await reopened.readMemberships()).length, 1);
});
```

- [ ] **Step 6: Run the run-store test and verify it fails**

Run: `node --test tests/js/rimskie-import-run-store.test.mjs`

Expected: FAIL because `RunStore` does not exist.

- [ ] **Step 7: Implement atomic files, NDJSON append, and export validation**

```js
async checkpoint(state) {
  const tempPath = `${this.statePath}.${process.pid}.tmp`;
  await writeFile(tempPath, `${JSON.stringify(state, null, 2)}\n`, 'utf8');
  await rename(tempPath, this.statePath);
}
```

The store creates exactly the directories in the spec, maintains in-memory membership keys loaded from `memberships.ndjson`, rejects path traversal, and emits `export.json` only when every referenced product JSON and first-image file exists.

- [ ] **Step 8: Add the exact 46-source JSON and validate uniqueness in the test**

Each object has `label`, `source_url`, `target_slug`, `enabled: true`, and one-based `sort_order`. The test asserts 46 entries, unique URLs/slugs/orders, HTTPS host `rimskie.com`, and target slugs equal the final donor path segment. The labels and URLs are copied exactly from the user-provided list.

| Order | Label | Donor path / target slug |
|---:|---|---|
| 1 | На арочное окно | `rimskie-shtory-na-arochnoe-okno` |
| 2 | Тканевые | `rimskie-shtory-tkanevye` |
| 3 | Из рогожки | `rimskie-shtory-iz-rogozhi` |
| 4 | Римские шторы для офиса | `rimskie-shtory-na-stvorku` |
| 5 | Римские шторы в гостиную | `rimskie-shtory-v-gostinuyu` |
| 6 | На треугольное окно | `rimskie-shtory-na-treugolnoe-okno` |
| 7 | На эркерное окно | `rimskie-shtory-na-erkernoe-okno` |
| 8 | Римские шторы в спальню | `rimskie-shtory-v-spalnyu` |
| 9 | В комнату подростка | `rimskie-shtory-v-komnatu-podrostka` |
| 10 | Белые | `rimskie-shtory-belye` |
| 11 | Серые | `rimskie-shtory-serye` |
| 12 | С рисунком | `rimskie-shtory-s-risunkom` |
| 13 | Римские шторы день-ночь | `rimskie-shtory-den-noch` |
| 14 | Прозрачные | `rimskie-shtory-prozrachnye` |
| 15 | Из льна | `rimskie-shtory-iz-lna` |
| 16 | Из хлопка | `rimskie-shtory-iz-hlopka` |
| 17 | Римские шторы 160 | `rimskie-shtory-160h160` |
| 18 | 150 см | `rimskie-shtory-150-sm` |
| 19 | Широкие | `shirokie-rimskie-shtory` |
| 20 | Узкие | `uzkie-rimskie-shtory` |
| 21 | 180 см | `rimskie-shtory-180h160` |
| 22 | Римские шторы 130 см | `rimskie-shtory-130-sm` |
| 23 | 220 см | `rimskie-shtory-220-sm` |
| 24 | Из тюля | `rimskie-shtory-iz-tyulya` |
| 25 | Крепление без сверления | `rimskie-shtory-bez-sverleniya` |
| 26 | Плотные | `plotnye-rimskie-shtory` |
| 27 | Длинные | `dlinnye-rimskie-shtory` |
| 28 | В морском стиле | `rimskie-shtory-v-morskom-stile` |
| 29 | Во французском стиле | `shtory-vo-francuzskom-stile` |
| 30 | Римские шторы с Алисой | `s-alisoy` |
| 31 | Римские шторы на петлях | `na-petlyah` |
| 32 | Римские шторы с тюлем | `rimskie-shtory-s-tyulem` |
| 33 | Прованс | `provans` |
| 34 | На мансардные окна | `na-mansardnye-okna` |
| 35 | 140 см | `140-sm` |
| 36 | 200 см | `200-sm` |
| 37 | С кантом | `s-kantom` |
| 38 | С карнизом | `s-karnizom` |
| 39 | Римские шторы на пластиковые окна | `na-plastikovye-okna` |
| 40 | На магнитах | `na-magnitah` |
| 41 | Каскадные | `kaskadnye` |
| 42 | На панорамные окна | `na-panoramnye-okna` |
| 43 | Без карниза | `bez-karniza` |
| 44 | Римские шторы в ванную | `rimskie-shtory-v-vannuyu` |
| 45 | Римские шторы на окно | `rimskie-shtory-na-okno` |
| 46 | Римские шторы на пластиковое окно | `rimskie-shtory-na-plastikovoe-okno` |

For every row, `source_url` is `https://rimskie.com/catalog/rimskie-shtory/<donor-path>` and `target_slug` is the literal third-column value.

- [ ] **Step 9: Confirm runtime output is already ignored and run the focused tests**

Verify the collector has no project-relative runtime default. Operational output must be supplied through `--data-root` or `RIMSKIE_IMPORT_DATA_ROOT` and remain outside the repository on `G:`.

Run: `node --test tests/js/rimskie-import-parsers.test.mjs tests/js/rimskie-import-run-store.test.mjs`

Expected: all tests PASS.

- [ ] **Step 10: Commit**

```bash
git add package.json package-lock.json config/rimskie-import-sources.json scripts/rimskie-import/lib tests/fixtures/rimskie-import tests/js/rimskie-import-parsers.test.mjs tests/js/rimskie-import-run-store.test.mjs
git commit -m "feat: add resumable roman blinds import contract"
```

### Task 2: Throttled Playwright collector and operator CLI

**Files:**
- Create: `scripts/rimskie-import/lib/request-policy.mjs`
- Create: `scripts/rimskie-import/lib/playwright-transport.mjs`
- Create: `scripts/rimskie-import/lib/collector.mjs`
- Create: `scripts/rimskie-import/cli.mjs`
- Create: `tests/js/rimskie-import-request-policy.test.mjs`
- Create: `tests/js/rimskie-import-collector.test.mjs`
- Modify: `package.json`
- Modify: `package-lock.json`
- Modify: `README.md`

**Interfaces:**
- Consumes: Task 1 parser functions, `RunStore`, and `config/rimskie-import-sources.json`.
- Produces: `RequestPolicy({ clock, random, htmlDelayMs, imageDelayMs, hourlyLimit, backoffMs })` with `beforeRequest(kind)`, `recordSuccess()`, and `recordFailure(kind)`.
- Produces: `PlaywrightTransport.open({ profileDir, headed, executablePath })`, `getHtml(url)`, `downloadFirstImage(url, destination)`, and `close()`.
- Produces: `Collector.run({ store, transport, policy, maxRequests, maxProducts })` returning a status snapshot.
- Produces: `node scripts/rimskie-import/cli.mjs <command> --run <id>`.

- [ ] **Step 1: Write failing policy tests with injected time/randomness**

```js
test('third consecutive donor failure pauses after 2m, 5m, and 15m backoff', async () => {
  const sleeps = [];
  const policy = new RequestPolicy({
    clock: fakeClock,
    random: () => 0,
    sleep: async (ms) => sleeps.push(ms),
  });
  assert.equal(await policy.recordFailure('http_403'), 'retry');
  assert.equal(await policy.recordFailure('http_429'), 'retry');
  assert.equal(await policy.recordFailure('network'), 'pause');
  assert.deepEqual(sleeps, [120000, 300000, 900000]);
});

test('rolling hour budget rejects request 121 without transport access', async () => {
  for (let count = 0; count < 120; count += 1) await policy.beforeRequest('html');
  await assert.rejects(policy.beforeRequest('html'), /hourly request budget exhausted/);
});
```

- [ ] **Step 2: Run policy tests and verify they fail**

Run: `node --test tests/js/rimskie-import-request-policy.test.mjs`

Expected: FAIL because `RequestPolicy` does not exist.

- [ ] **Step 3: Implement the policy with exact defaults and observable events**

```js
export const DEFAULT_LIMITS = Object.freeze({
  htmlDelayMs: [20_000, 40_000],
  imageDelayMs: [10_000, 20_000],
  hourlyLimit: 120,
  backoffMs: [120_000, 300_000, 900_000],
  concurrency: 1,
});
```

`beforeRequest` prunes timestamps older than 3,600,000 ms, sleeps the random per-kind delay, and records the request immediately before transport access. A success resets consecutive failures. The third failure sleeps 15 minutes, returns `pause`, and never performs a fourth automatic retry.

- [ ] **Step 4: Write collector tests using a complete fake transport**

The fake returns the synthetic HTML fixtures and literal `Buffer.from('first-image')`. Tests assert: category page is checkpointed before product work; duplicate products across two sources cause one product fetch/image download; a completed resume causes zero transport calls; `maxRequests=3` stops cleanly; a challenge status writes an event and sets state to `paused`.

```js
assert.deepEqual(fakeTransport.calls, [
  ['html', 'https://rimskie.com/catalog/rimskie-shtory/white'],
  ['html', 'https://rimskie.com/products/11889-example'],
  ['image', 'https://rimskie.com/media/output/first.webp'],
]);
assert.equal(snapshot.uniqueProducts, 1);
assert.equal(snapshot.status, 'completed');
```

- [ ] **Step 5: Run collector tests and verify they fail**

Run: `node --test tests/js/rimskie-import-collector.test.mjs`

Expected: FAIL because `Collector` and transport contracts do not exist.

- [ ] **Step 6: Add `playwright-core` and implement the persistent transport**

Run: `npm install --save-dev playwright-core`

The transport uses `chromium.launchPersistentContext(profileDir, { headless: false, executablePath })`, discovers Chrome/Edge paths on Windows and common Chromium paths on Linux, and keeps the profile inside the validated `G:` run directory. After an authenticated session exists, it aborts stylesheets, fonts, media, analytics hosts, and every image except the exact first-image download request. It detects ordinary `403`/`429` as retryable typed failures, but any visible BotHunt/challenge page—including HTML returned to an image request—pauses immediately and disarms resource blocking. It accepts only matching `image/webp` bytes for the `.webp` destination, does not inspect or export cookies, and never tries to defeat a challenge; the visible browser remains available for the already-authorized human click.

- [ ] **Step 7: Implement the collector state machine**

```js
for (const source of state.sources) {
  while (source.nextPageUrl && !control.shouldStop()) {
    const html = await fetchHtml(source.nextPageUrl);
    const page = parseCategoryPage(html, source.nextPageUrl);
    await persistPageAndMemberships(source, page);
    await store.checkpoint(nextState(state, source, page));
  }
}
```

The real implementation processes one operation at a time, persists before advancing, downloads only `firstImageUrl`, recovers saved product drafts and valid image bytes across either crash boundary without another donor request, and writes an event for every request, delay, retry, challenge, pause, resume, error, and completion without recording response bodies or credentials.

- [ ] **Step 8: Implement the CLI and local-only control files**

```text
start   --run <id> --data-root <G:\path> [--chrome <path>]
status  --run <id> --data-root <G:\path> [--json]
pause   --run <id> --data-root <G:\path>
resume  --run <id> --data-root <G:\path> [--chrome <path>]
stop    --run <id> --data-root <G:\path>
export  --run <id> --data-root <G:\path>
dry-run --run <id> --data-root <G:\path> --max-requests <n> --max-products <n>
```

`status`, `pause`, and `stop` never access the donor. `pause` and `stop` atomically set stop-dominant control flags checked after each delay, before reservation, and immediately before transport. A live owner prevents a second collector. `resume` refuses a stopped run and continues a paused run from checkpoints. `dry-run` defaults to three requests and one product when limits are omitted. Data-root and run-child validation occurs before runtime creation and rejects drive `C:`, device paths, and escaping junctions.

- [ ] **Step 9: Run collector and existing JS suites**

Run: `npm run test:shop`

Expected: all existing and new JS tests PASS.

- [ ] **Step 10: Document exact operator commands and commit**

README documents runtime directories, headed authentication, safe pause/resume, status JSON, defaults, and the fact that collection is private and non-publishing.

```bash
git add package.json package-lock.json README.md scripts/rimskie-import tests/js/rimskie-import-request-policy.test.mjs tests/js/rimskie-import-collector.test.mjs
git commit -m "feat: add controlled donor collector"
```

### Task 3: Staging, attribute, collection, and source metadata schema

**Files:**
- Create: `database/migrations/2026_08_25_000000_create_catalog_import_staging_tables.php`
- Create: `database/migrations/2026_08_25_000100_create_catalog_attribute_tables.php`
- Create: `database/migrations/2026_08_25_000200_add_catalog_import_fields_to_products_and_subcategories.php`
- Create: `app/Models/CatalogImportRun.php`
- Create: `app/Models/CatalogImportSource.php`
- Create: `app/Models/CatalogImportItem.php`
- Create: `app/Models/CatalogAttribute.php`
- Create: `app/Models/CatalogAttributeValue.php`
- Create: `tests/Feature/CatalogImportSchemaTest.php`
- Modify: `app/Models/Product.php`
- Modify: `app/Models/Subcategory.php`

**Interfaces:**
- Consumes: external IDs, source slugs, manifest statuses, attributes, and memberships from Task 1.
- Produces: Eloquent relations `CatalogImportRun::sources/items`, `CatalogImportItem::sources/attributeValues/product`, `Product::catalogCollections/attributeValues/importRun`, and `Subcategory::collectionProducts/importRun`.
- Produces: stable status constants on run/source/item models instead of scattered string literals.

- [ ] **Step 1: Write a failing schema/relationship test**

```php
public function test_one_staging_item_can_belong_to_multiple_sources_without_duplication(): void
{
    $run = CatalogImportRun::create(['provider' => 'rimskie.com', 'external_run_id' => 'run-001', 'status' => 'staged']);
    $item = $run->items()->create(['provider' => 'rimskie.com', 'external_id' => '11889', 'source_url' => 'https://rimskie.com/products/11889-example', 'review_status' => 'needs_review']);
    $white = $run->sources()->create(['label' => 'Белые', 'source_url' => 'https://rimskie.com/catalog/white', 'target_slug' => 'white', 'status' => 'completed', 'sort_order' => 1]);
    $office = $run->sources()->create(['label' => 'Для офиса', 'source_url' => 'https://rimskie.com/catalog/office', 'target_slug' => 'office', 'status' => 'completed', 'sort_order' => 2]);
    $item->sources()->syncWithoutDetaching([$white->id, $office->id]);

    $this->assertSame(2, $item->sources()->count());
    $this->assertSame(1, CatalogImportItem::where('external_id', '11889')->count());
}
```

The test class creates only the minimal `categories`, `subcategories`, `products`, and import dependency tables in `setUp`, runs the three new migration files explicitly, and drops those tables in `tearDown`; it does not use `RefreshDatabase` because the repository's pre-existing migration history cannot migrate fresh.

- [ ] **Step 2: Run the focused test and verify it fails**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogImportSchemaTest.php`

Expected: FAIL because the import tables/models do not exist.

- [ ] **Step 3: Create staging migrations with explicit uniqueness and indexes**

`catalog_import_runs` includes provider/external run ID/status/config/counters/error/started/completed/published/backup fields; unique `(provider, external_run_id)`.

`catalog_import_sources` includes run FK, label/source URL/target slug/status/sort/progress, `rewritten_title`, `rewritten_h1`, `rewritten_intro`, `rewritten_description`, `rewritten_seo`, review status/notes/warnings, and error; unique `(catalog_import_run_id, target_slug)`.

`catalog_import_items` includes run FK, provider/external ID/source URL, source title/description/price, source image path, rewritten title/summary/description/slug, review status/review notes/error, nullable published product FK, created-product flag, and JSON publication snapshot; unique `(catalog_import_run_id, provider, external_id)` so separate runs retain separate review/audit state.

`catalog_import_sources` also stores nullable published subcategory FK, created-subcategory flag, and JSON publication snapshot.

`catalog_import_item_source` uses `import_item_id` and `import_source_id` FKs plus a named unique pair. JSON uses Laravel casts; prices use `decimal(12,2)`.

- [ ] **Step 4: Create normalized attribute and production pivots**

`catalog_attributes` has unique `code`, label, type (`select|number`), unit, sort order, and public flag. `catalog_attribute_values` has attribute FK, normalized value, label, numeric value, sort order, and unique `(catalog_attribute_id, normalized_value)`. `catalog_import_item_attribute_value` uses `import_item_id` and `attribute_value_id`. `catalog_product_attribute_value` uses `product_id`, `attribute_value_id`, and nullable `catalog_import_run_id`. `catalog_collection_product` uses `subcategory_id`, `product_id`, and nullable `catalog_import_run_id`. Every pivot has a short explicitly named unique pair to stay under MySQL's identifier limit.

- [ ] **Step 5: Add product/subcategory metadata safely**

Products receive nullable `source_provider`, `source_external_id`, `source_url`, `source_price`, nullable import-run FK, and `calculator_enabled boolean default true`; add unique `(source_provider, source_external_id)`. Subcategories receive `is_import_collection boolean default false` and nullable import-run FK. Down migrations remove FKs/indexes before columns and do not touch legacy rows.

- [ ] **Step 6: Implement focused models, casts, fillable fields, and relations**

```php
public function catalogCollections(): BelongsToMany
{
    return $this->belongsToMany(Subcategory::class, 'catalog_collection_product')
        ->withPivot('catalog_import_run_id')
        ->withTimestamps();
}

public function attributeValues(): BelongsToMany
{
    return $this->belongsToMany(
        CatalogAttributeValue::class,
        'catalog_product_attribute_value',
        'product_id',
        'attribute_value_id'
    )
        ->withTimestamps();
}

public function sources(): BelongsToMany
{
    return $this->belongsToMany(
        CatalogImportSource::class,
        'catalog_import_item_source',
        'import_item_id',
        'import_source_id'
    )->withTimestamps();
}
```

- [ ] **Step 7: Run schema tests and full migration cycle**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogImportSchemaTest.php`

Expected: PASS on SQLite memory and prove the three new migrations migrate up/down against the explicit dependency schema. Do not use repository-wide `migrate:fresh`: pre-existing legacy migrations alter `products` before creating it and do not define current product foreign keys.

- [ ] **Step 8: Commit**

```bash
git add database/migrations app/Models tests/Feature/CatalogImportSchemaTest.php
git commit -m "feat: add catalog import staging schema"
```

### Task 4: Strict package ingest and deterministic factual rewrite

**Files:**
- Create: `app/Services/CatalogImport/CatalogImportPackageValidator.php`
- Create: `app/Services/CatalogImport/CatalogImportIngestor.php`
- Create: `app/Services/CatalogImport/ProductContentRewriter.php`
- Create: `app/Services/CatalogImport/TemplateProductRewriter.php`
- Create: `app/Services/CatalogImport/LandingContentRewriter.php`
- Create: `app/Services/CatalogImport/TemplateLandingRewriter.php`
- Create: `app/Data/CatalogImport/ValidatedCatalogImportPackage.php`
- Create: `app/Data/CatalogImport/RewrittenProductContent.php`
- Create: `app/Data/CatalogImport/RewrittenLandingContent.php`
- Create: `app/Console/Commands/IngestCatalogImport.php`
- Create: `tests/Fixtures/catalog-import/export.json`
- Create: `tests/Fixtures/catalog-import/images/11889.webp`
- Create: `tests/Unit/TemplateProductRewriterTest.php`
- Create: `tests/Feature/CatalogImportIngestTest.php`

**Interfaces:**
- Consumes: `stylish-house.catalog-import/v1` manifest produced by Task 1.
- Produces: `CatalogImportPackageValidator::validate(string $manifestPath): ValidatedCatalogImportPackage`.
- Produces: `CatalogImportIngestor::ingest(ValidatedCatalogImportPackage $package): CatalogImportRun`.
- Produces: `ProductContentRewriter::rewrite(array $source): RewrittenProductContent` with `title`, `summary`, `description`, `slugBase`, `warnings`.
- Produces: `LandingContentRewriter::rewrite(string $label, string $targetSlug): RewrittenLandingContent` with `title`, `h1`, `intro`, `description`, `seo`, `warnings`.
- Produces: Artisan command `catalog-import:ingest {manifest} {--dry-run}`.

- [ ] **Step 1: Write rewrite tests that name public-copy failures**

```php
public function test_rewriter_removes_donor_brand_and_uses_only_present_facts(): void
{
    $result = $this->rewriter->rewrite([
        'external_id' => '11889',
        'title' => 'Римская штора KORTIN VELVET белоснежный',
        'description' => 'Купить в Rimskie.com с доставкой.',
        'attributes' => ['material' => ['полиэстер'], 'color' => ['белый']],
    ]);

    $publicText = mb_strtolower($result->title.' '.$result->summary.' '.$result->description);
    $this->assertStringNotContainsString('kortin', $publicText);
    $this->assertStringNotContainsString('rimskie.com', $publicText);
    $this->assertStringContainsString('полиэстер', $publicText);
    $this->assertStringContainsString('бел', $publicText);
    $this->assertNotEmpty($result->warnings);
}
```

Add a second test proving identical input returns identical output and a third proving two different external IDs select different template variants while retaining the same factual set.

- [ ] **Step 2: Run rewrite tests and verify they fail**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Unit/TemplateProductRewriterTest.php`

Expected: FAIL because the rewriter contract/DTOs do not exist.

- [ ] **Step 3: Implement the rewriter contract and deterministic templates**

```php
interface ProductContentRewriter
{
    public function rewrite(array $source): RewrittenProductContent;
}
```

Normalize facts through an allowlist (`type`, `material`, `color`, `opacity`, `texture`, `mounting`, `control`, `room`, `style`, `width`, `height`, `composition`, `manufacturer`, `density`, `trim`). Strip HTML, marketplace/brand phrases, phone/email/URLs, and unsupported claims. Select one of exactly four sentence layouts with `abs(crc32(external_id)) % 4`. Valid output is title 25–140 characters, summary 80–220 characters, and description 180–1,000 characters. Mark `warnings` for missing material/type, `similar_text` similarity of 70% or more against normalized source copy, output outside those bounds, unknown characteristics, or removed branding.

`TemplateLandingRewriter` builds neutral SEO title/H1/intro/description/SEO text from the approved Russian label and target slug, uses no donor body copy, and marks awkward labels or duplicate generated text for review.

- [ ] **Step 4: Write failing package validation and idempotent ingest tests**

The literal fixture has two sources, one unique product, two memberships, one first-image file, and two normalized attributes. Tests assert a second ingest keeps one run/item, two memberships, one copied private image, and stable rewritten content. Separate tests reject schema-version mismatch, wrong counts, missing image, `../` traversal, duplicate target slug, non-`rimskie.com` source host, and an external ID inconsistent with its URL.

```php
$first = $ingestor->ingest($validator->validate($manifest));
$second = $ingestor->ingest($validator->validate($manifest));

$this->assertSame($first->id, $second->id);
$this->assertDatabaseCount('catalog_import_items', 1);
$this->assertDatabaseCount('catalog_import_item_source', 2);
$this->assertSame('needs_review', $second->items()->first()->review_status);
```

- [ ] **Step 5: Run ingest tests and verify they fail**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogImportIngestTest.php`

Expected: FAIL because validator/ingestor are missing.

- [ ] **Step 6: Implement strict validation and transactional upsert**

Validation resolves every package path against the manifest directory, verifies realpath containment, size/hash/count fields, exactly the enabled source records declared by config, unique external IDs/memberships, and required first image. Ingest copies the image into private `storage/app/catalog-imports/<external-run-id>/images/<external-id>.webp`, upserts the run/sources/items, generates source landing drafts plus product drafts, normalizes attribute codes/values, syncs staging pivots without detaching memberships from other sources, and rolls back the whole ingest on any error.

- [ ] **Step 7: Implement the Artisan boundary with dry-run output**

`--dry-run` validates and prints literal counts but never writes DB/storage. A real run prints run ID, sources, unique items, memberships, images, `needs_review`, and warnings. Errors include the manifest path and failed invariant without dumping source descriptions or credentials.

- [ ] **Step 8: Run focused and adjacent tests**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Unit/TemplateProductRewriterTest.php tests/Feature/CatalogImportIngestTest.php tests/Feature/CatalogImportSchemaTest.php`

Expected: all PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Services/CatalogImport app/Console/Commands/IngestCatalogImport.php tests/Fixtures/catalog-import tests/Unit/TemplateProductRewriterTest.php tests/Feature/CatalogImportIngestTest.php
git commit -m "feat: ingest and rewrite donor catalog packages"
```

### Task 5: Automatic database backup, guarded publication, and run rollback

**Files:**
- Create: `app/Contracts/DatabaseDumpRunner.php`
- Create: `app/Services/CatalogImport/NativeMysqlDumpRunner.php`
- Create: `app/Services/CatalogImport/CatalogDatabaseBackupService.php`
- Create: `app/Services/CatalogImport/CatalogImportPublisher.php`
- Create: `app/Services/CatalogImport/CatalogImportRollback.php`
- Create: `app/Console/Commands/BackupCatalogDatabase.php`
- Create: `app/Data/CatalogImport/CatalogBackupArtifact.php`
- Create: `app/Data/CatalogImport/PublicationReport.php`
- Create: `app/Data/CatalogImport/RollbackReport.php`
- Create: `tests/Feature/CatalogDatabaseBackupTest.php`
- Create: `tests/Feature/CatalogImportPublishTest.php`
- Create: `tests/Feature/CatalogImportRollbackTest.php`
- Create: `config/rimskie_import.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Interfaces:**
- Consumes: approved staging sources/items and first images from Task 4; staging publication snapshot fields and production pivots from Task 3.
- Produces: `DatabaseDumpRunner::dump(array $connection, string $sqlPath): void`.
- Produces: `CatalogDatabaseBackupService::create(CatalogImportRun $run): CatalogBackupArtifact` with gzip path, manifest path, compressed/uncompressed SHA-256, byte counts, and timestamp.
- Produces: `CatalogImportPublisher::preflight(CatalogImportRun $run): PublicationReport` and `publish(CatalogImportRun $run): PublicationReport`.
- Produces: `CatalogImportRollback::rollback(CatalogImportRun $run): RollbackReport`.
- Produces: command `catalog:backup {--run=}`.

- [ ] **Step 1: Write a failing backup test against a fake dump runner**

The fake implements the real contract and writes the literal SQL `CREATE TABLE test (id INT);\n`. The test calls the real backup service and independently verifies gzip contents, both hashes, non-public location, JSON manifest fields, and `backup_created_at`/path/hash recorded on the run.

```php
$artifact = $service->create($run);
$this->assertSame("CREATE TABLE test (id INT);\n", gzdecode(file_get_contents($artifact->gzipPath)));
$this->assertSame(hash_file('sha256', $artifact->gzipPath), $artifact->compressedSha256);
$this->assertStringStartsWith(storage_path('app/catalog-backups/'), $artifact->gzipPath);
```

- [ ] **Step 2: Run the backup test and verify it fails**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogDatabaseBackupTest.php`

Expected: FAIL because the backup contracts/services do not exist.

- [ ] **Step 3: Implement a credential-safe native dump and manifest**

`NativeMysqlDumpRunner` invokes an argument-array `mysqldump` process with `--single-transaction`, `--quick`, `--routines`, `--triggers`, and `--hex-blob`. The password is passed only through process environment `MYSQL_PWD`; it never appears in arguments/logs. SQL is streamed to a private temporary file, gzip is streamed at compression level 6, both hashes are calculated by reading the finished files, and every temporary file is removed on failure. The binary path comes from `CATALOG_BACKUP_MYSQLDUMP_BINARY` or `mysqldump`.

Bind `DatabaseDumpRunner` to `NativeMysqlDumpRunner` in `AppServiceProvider::register()`. `catalog:backup` prints artifact paths/hashes and returns non-zero on a missing binary, empty dump, gzip error, or manifest error.

- [ ] **Step 4: Write failing publication tests for the safety boundary**

Create an approved run with two approved sources, one approved item, two memberships, two attributes, and one private image. Tests independently assert:

- when backup throws, products/subcategories/pivots remain unchanged;
- a `needs_review` or rejected item is not published;
- successful publish creates one canonical product in `rimskieshtory`, two visible import collections, two memberships, local first image and thumbnail, normalized product attributes, and no duplicate product;
- source price remains in `products.source_price`, while `min_price` is null and `calculator_enabled` is false;
- a second publish updates the same product/collections and leaves counts unchanged;
- source/item publication snapshots identify whether rows were created or updated.

```php
$report = $publisher->publish($run);
$product = Product::where('source_provider', 'rimskie.com')->where('source_external_id', '11889')->firstOrFail();
$this->assertFalse($product->calculator_enabled);
$this->assertNull($product->min_price);
$this->assertSame('rimskieshtory', $product->subcategory->slug);
$this->assertSame(2, $product->catalogCollections()->count());
$this->assertSame(1, $report->createdProducts);
```

- [ ] **Step 5: Run publication tests and verify they fail**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogImportPublishTest.php`

Expected: FAIL because publisher/preflight do not exist.

- [ ] **Step 6: Implement preflight and transactional idempotent publication**

Preflight verifies run state, 46 reviewed source definitions for a full run (or the exact dry-run source count for a run marked dry-run), at least one approved item, every approved image/hash, unique public slugs/URLs, base category slug `story`, and base subcategory slug `rimskieshtory`. It returns literal create/update/skip/error counts without writes.

`publish()` calls `CatalogDatabaseBackupService::create()` before any catalog mutation, then inside one DB transaction:

1. upserts approved source collections with `is_import_collection=true`, this run ID, `show_in_catalog=true`, `show_in_menu=false`;
2. upserts approved products by `(source_provider, source_external_id)` with deterministic slug `<rewritten-slug>-<external-id>`, canonical base category/subcategory, `calculator_enabled=false`, `min_price=null`, and no donor price in public price fields;
3. copies the private first image to `storage/app/public/products/rimskie-import/<external-id>.webp`, stores `storage/products/rimskie-import/<external-id>.webp`, and calls `ProductImageThumbnailService`;
4. syncs collection and attribute pivots with the run ID;
5. records exact before snapshots plus created/updated flags on staging sources/items;
6. marks the run `published` only after catalog invariants pass.

After commit, invoke `sitemap:generate`. A sitemap failure sets a diagnostic run error and remains safely retryable without duplicating DB rows.

- [ ] **Step 7: Write failing rollback tests**

Tests cover both new and pre-existing imported rows. A new product/collection from the run is deleted after its run-owned pivots and public image are removed. A pre-existing product/collection is retained and restored byte-for-byte from its snapshot. Legacy rows and another import run remain unchanged. A second rollback is a no-op with zero counts.

- [ ] **Step 8: Run rollback tests and verify they fail**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogImportRollbackTest.php`

Expected: FAIL because `CatalogImportRollback` does not exist.

- [ ] **Step 9: Implement run-scoped rollback**

Rollback refuses a run that is currently publishing, removes only pivots whose `catalog_import_run_id` matches, restores updated row snapshots, deletes only rows explicitly marked created by this run, deletes only media paths recorded by this run, regenerates sitemap, and marks the run `rolled_back`. Any invariant mismatch aborts before destructive DB changes and reports the exact conflicting IDs.

- [ ] **Step 10: Run the safety suite and commit**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogDatabaseBackupTest.php tests/Feature/CatalogImportPublishTest.php tests/Feature/CatalogImportRollbackTest.php`

Expected: all PASS.

```bash
git add app/Contracts/DatabaseDumpRunner.php app/Services/CatalogImport app/Console/Commands/BackupCatalogDatabase.php app/Providers/AppServiceProvider.php config/rimskie_import.php tests/Feature/CatalogDatabaseBackupTest.php tests/Feature/CatalogImportPublishTest.php tests/Feature/CatalogImportRollbackTest.php
git commit -m "feat: publish catalog imports with backup and rollback"
```

### Task 6: Admin review, approval, progress, and publication controls

**Files:**
- Create: `app/Http/Controllers/CatalogImportController.php`
- Create: `app/Http/Requests/UpdateCatalogImportItemRequest.php`
- Create: `app/Http/Requests/UpdateCatalogImportSourceRequest.php`
- Create: `resources/views/admin/catalog-imports/index.blade.php`
- Create: `resources/views/admin/catalog-imports/show.blade.php`
- Create: `resources/views/admin/catalog-imports/partials/items.blade.php`
- Create: `tests/Feature/CatalogImportAdminTest.php`
- Modify: `routes/web.php`
- Modify: `resources/views/components/admin/sidebar.blade.php`

**Interfaces:**
- Consumes: run/source/item models, preflight/publish/rollback services from Tasks 3–5.
- Produces: named routes `admin.catalog_imports.index`, `.show`, `.state`, `.items`, `.sources.update`, `.sources.approve`, `.sources.reject`, `.items.update`, `.items.approve`, `.items.reject`, `.approve_bulk`, `.prepublish`, `.publish`, and `.rollback`.
- Produces: controller methods `index`, `show`, `state`, `items`, `updateSource`, `approveSource`, `rejectSource`, `updateItem`, `approveItem`, `rejectItem`, `approveBulk`, `prepublish`, `publish`, `rollback`.

- [ ] **Step 1: Write failing authorization and review-flow tests**

```php
public function test_non_admin_cannot_open_or_mutate_catalog_imports(): void
{
    $user = User::factory()->create(['role' => 'user']);
    $run = CatalogImportRun::create([
        'provider' => 'rimskie.com',
        'external_run_id' => 'run-001',
        'status' => CatalogImportRun::STATUS_REVIEWING,
    ]);
    $item = $run->items()->create([
        'provider' => 'rimskie.com',
        'external_id' => '11889',
        'source_url' => 'https://rimskie.com/products/11889-example',
        'review_status' => CatalogImportItem::STATUS_NEEDS_REVIEW,
    ]);
    $this->actingAs($user)->get(route('admin.catalog_imports.show', $run))->assertForbidden();
    $this->actingAs($user)->post(route('admin.catalog_imports.items.approve', [$run, $item]))->assertForbidden();
}

public function test_admin_can_edit_then_approve_an_item(): void
{
    $response = $this->actingAs($admin)->put(route('admin.catalog_imports.items.update', [$run, $item]), [
        'rewritten_title' => 'Римская штора из белого полиэстера',
        'rewritten_summary' => 'Аккуратная модель для светлого интерьера.',
        'rewritten_description' => 'Белая римская штора из полиэстера с указанным в карточке типом монтажа.',
        'rewritten_slug' => 'rimskaya-shtora-belaya-11889',
        'review_notes' => 'Проверено',
    ]);
    $response->assertRedirect();
    $this->post(route('admin.catalog_imports.items.approve', [$run, $item]))->assertRedirect();
    $this->assertSame('approved', $item->fresh()->review_status);
}
```

Use the project’s explicit minimal test schema/data pattern instead of adding test-only methods to production models.

- [ ] **Step 2: Run admin tests and verify they fail**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogImportAdminTest.php`

Expected: FAIL because routes/controller/views are missing.

- [ ] **Step 3: Add admin-only routes inside the existing `role:admin` group**

```php
Route::get('/admin/catalog-imports', [CatalogImportController::class, 'index'])->name('admin.catalog_imports.index');
Route::get('/admin/catalog-imports/{run}', [CatalogImportController::class, 'show'])->name('admin.catalog_imports.show');
Route::get('/admin/catalog-imports/{run}/state', [CatalogImportController::class, 'state'])->name('admin.catalog_imports.state');
Route::get('/admin/catalog-imports/{run}/items', [CatalogImportController::class, 'items'])->name('admin.catalog_imports.items');
Route::put('/admin/catalog-imports/{run}/sources/{source}', [CatalogImportController::class, 'updateSource'])->name('admin.catalog_imports.sources.update');
Route::post('/admin/catalog-imports/{run}/sources/{source}/approve', [CatalogImportController::class, 'approveSource'])->name('admin.catalog_imports.sources.approve');
Route::post('/admin/catalog-imports/{run}/sources/{source}/reject', [CatalogImportController::class, 'rejectSource'])->name('admin.catalog_imports.sources.reject');
Route::put('/admin/catalog-imports/{run}/items/{item}', [CatalogImportController::class, 'updateItem'])->name('admin.catalog_imports.items.update');
Route::post('/admin/catalog-imports/{run}/items/{item}/approve', [CatalogImportController::class, 'approveItem'])->name('admin.catalog_imports.items.approve');
Route::post('/admin/catalog-imports/{run}/items/{item}/reject', [CatalogImportController::class, 'rejectItem'])->name('admin.catalog_imports.items.reject');
Route::post('/admin/catalog-imports/{run}/approve', [CatalogImportController::class, 'approveBulk'])->name('admin.catalog_imports.approve_bulk');
Route::get('/admin/catalog-imports/{run}/prepublish', [CatalogImportController::class, 'prepublish'])->name('admin.catalog_imports.prepublish');
Route::post('/admin/catalog-imports/{run}/publish', [CatalogImportController::class, 'publish'])->name('admin.catalog_imports.publish');
Route::post('/admin/catalog-imports/{run}/rollback', [CatalogImportController::class, 'rollback'])->name('admin.catalog_imports.rollback');
```

Nested item actions verify `$item->catalog_import_run_id === $run->id`; mismatches return 404.

- [ ] **Step 4: Implement validated controller actions**

`UpdateCatalogImportItemRequest` validates UTF-8 strings, title/slug lengths, slug pattern, and notes. `UpdateCatalogImportSourceRequest` validates title/H1/intro/description/SEO fields and target slug without permitting source URL changes. Source/item nested actions verify run ownership. Source approval refuses incomplete landing fields; item approval refuses missing first image, empty rewritten fields, validation warnings not acknowledged in notes, or missing membership. Bulk approve accepts explicit checked item IDs only and returns approved/refused counts. Publish and rollback call the services from Task 5 and surface their reports; controller contains no DB orchestration.

- [ ] **Step 5: Build the run index and review screen in the existing admin layout**

Both pages use `<x-admin.head>`, `<x-admin.header>`, `<x-admin.sidebar>`, and `<x-admin.footer>`. The run page contains:

- run status and collector configuration;
- sources/pages/unique products/photos/memberships/duplicates/error counters;
- per-source progress and review status;
- per-source landing draft edit/approve/reject controls;
- item filters for `needs_review|approved|rejected|published|error`;
- first local photo;
- source URL and internal source price explicitly labeled “служебная, не публикуется”;
- source and rewritten title/description side-by-side;
- attributes and collection memberships;
- edit/approve/reject forms with CSRF;
- selected-item bulk approval;
- prepublish report;
- publish button that states a fresh backup will be created first;
- rollback button shown only for a published run.

Use paginated server-side item results and the `state` JSON endpoint for lightweight progress refresh; never load thousands of item bodies into one response.

- [ ] **Step 6: Add the sidebar entry and complete admin assertions**

The link text is `Импорт каталога`, points to `admin.catalog_imports.index`, and appears beside the existing min-price/thumbnails controls. Tests assert the admin page contains source/public copy labels, photo, statuses, price privacy label, and CSRF-backed POST forms; the response must not embed source raw descriptions in JavaScript.

- [ ] **Step 7: Run tests and commit**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogImportAdminTest.php tests/Feature/CatalogImportPublishTest.php`

Expected: all PASS.

```bash
git add app/Http/Controllers/CatalogImportController.php app/Http/Requests/UpdateCatalogImportItemRequest.php app/Http/Requests/UpdateCatalogImportSourceRequest.php resources/views/admin/catalog-imports routes/web.php resources/views/components/admin/sidebar.blade.php tests/Feature/CatalogImportAdminTest.php
git commit -m "feat: add catalog import review controls"
```

### Task 7: Collection query, normalized storefront filters, SEO chips, and indexing

**Files:**
- Create: `app/Services/Catalog/CatalogCollectionQuery.php`
- Create: `app/Services/Catalog/CatalogFilterOptions.php`
- Create: `resources/views/front/partials/collection-filters.blade.php`
- Create: `resources/views/front/partials/rimskie-seo-links.blade.php`
- Create: `tests/Feature/CatalogCollectionFilterTest.php`
- Modify: `app/Http/Controllers/SubcategoryController.php`
- Modify: `resources/views/front/subcategory.blade.php`
- Modify: `app/Support/IndexingPolicy.php`
- Modify: `tests/Feature/AuditTechnicalIndexingTest.php`
- Modify: `tests/Feature/GenerateSitemapTest.php`

**Interfaces:**
- Consumes: `Subcategory::is_import_collection`, collection/product and attribute pivots from Task 3.
- Produces: `CatalogCollectionQuery::base(Subcategory $subcategory): Builder` and `apply(Builder $query, array $filters, array $ranges): Builder`.
- Produces: `CatalogFilterOptions::forCollection(Subcategory $subcategory, array $activeFilters = []): Collection`.
- Produces: request payload `filters[<attribute-code>][]=<normalized-value>` and `ranges[<attribute-code>][min|max]=<number>`.
- Produces: AJAX response keys `products_html`, `products`, `pagination`, `filter_counts`, and `total`.

- [ ] **Step 1: Write failing collection-query tests for OR/AND/dedup semantics**

Create one collection with products A/B/C. Assign A `{color:white, material:linen}`, B `{color:gray, material:linen}`, C `{color:white, material:polyester}`. Assert:

```php
$result = $service->apply(
    $service->base($collection),
    ['color' => ['white', 'gray'], 'material' => ['linen']],
    []
)->pluck('products.id')->all();

$this->assertSame([$productA->id, $productB->id], $result);
```

Add tests for a numeric range, unknown attribute/value rejection, a product linked twice still appearing once, collection isolation, and 12-item pagination preserving filter query parameters.

- [ ] **Step 2: Run the collection test and verify it fails**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogCollectionFilterTest.php`

Expected: FAIL because collection query/filter services do not exist.

- [ ] **Step 3: Implement a shared collection query and option counts**

`base()` starts from `Product::query()` with canonical `category`, `subcategory`, and `model` relations, joins only the requested collection through `whereHas('catalogCollections', ...)`, restricts `show_in_catalog=true`, and selects distinct products. `apply()` validates codes/values against public attribute definitions, performs one `whereHas(attributeValues)` per attribute code (AND across codes), and one `whereIn` within that relation (OR within a code). Numeric filters use the stored `numeric_value` and inclusive min/max bounds.

`CatalogFilterOptions` calculates each visible value count inside the active collection and other active filters, excluding the value’s own dimension so switching remains useful. It returns literal `code`, `label`, `type`, `unit`, `values[{value,label,count,selected}]`.

- [ ] **Step 4: Integrate the same query into initial HTML and AJAX**

In `SubcategoryController::show`, preserve all existing legacy/clone behavior when `is_import_collection=false`. For an import collection, obtain the paginator and filters through the new services and never use `clone_subcategory_id`. In `filterProducts`, use the identical service and paginator path, render `front.partials.products` into `products_html`, retain serialized `products` for compatibility, and return updated counts/total.

Replace the hand-built JavaScript card template on the import-collection branch with `productsWrap.innerHTML = data.products_html`; do not maintain a third calculator/CTA card implementation.

- [ ] **Step 5: Render accessible filters and update the address bar**

The filter partial uses checkbox groups with labels/counts and numeric min/max inputs. It submits through the existing debounced fetch path, keeps selections over pagination, and calls `history.replaceState` with bracketed filter/range query parameters. Empty filters remove their keys. A no-JS GET reload uses the same request normalization and returns the same products.

- [ ] **Step 6: Add the dedicated 46-link SEO chips block**

On the base `rimskieshtory` page and every published import collection, query visible `is_import_collection=true` subcategories ordered by their import source order and render canonical links through `CanonicalUrl::route`. Reuse the existing `.s-tags__tag` visual language, wrap on mobile, and omit unpublished staging sources.

- [ ] **Step 7: Write and run indexing/sitemap regressions**

Extend tests with literal requests:

```php
$this->get('/story/white/?filters[color][]=white')->assertHeader('X-Robots-Tag', 'noindex, follow');
$this->get('/story/white/?ranges[width][min]=100')->assertHeader('X-Robots-Tag', 'noindex, follow');
$this->get('/story/white/?page=2')->assertHeaderMissing('X-Robots-Tag');
```

Sitemap tests assert a published visible collection is present with a trailing slash, a staging hidden collection is absent, and an imported product appears only under `/story/rimskieshtory/<slug>/`.

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogCollectionFilterTest.php tests/Feature/AuditTechnicalIndexingTest.php tests/Feature/GenerateSitemapTest.php`

Expected: all PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Catalog app/Http/Controllers/SubcategoryController.php resources/views/front/subcategory.blade.php resources/views/front/partials/collection-filters.blade.php resources/views/front/partials/rimskie-seo-links.blade.php app/Support/IndexingPolicy.php tests/Feature/CatalogCollectionFilterTest.php tests/Feature/AuditTechnicalIndexingTest.php tests/Feature/GenerateSitemapTest.php
git commit -m "feat: add filterable roman blind collections"
```

### Task 8: Keep imported products out of Excel calculations and cart flows

**Files:**
- Create: `tests/Feature/CatalogCalculatorDisabledTest.php`
- Modify: `app/Services/MinPriceRecalcService.php`
- Modify: `app/Http/Controllers/SubcategoryController.php`
- Modify: `app/Http/Controllers/CategoryController.php`
- Modify: `app/Http/Controllers/ProductController.php`
- Modify: `app/Http/Controllers/ExcelController.php`
- Modify: `app/Http/Controllers/CartController.php`
- Modify: `app/Support/PreviewCardData.php`
- Modify: `resources/views/front/product.blade.php`
- Modify: `resources/views/front/subcategory.blade.php`
- Modify: `resources/views/front/category.blade.php`
- Modify: `resources/views/front/partials/products.blade.php`
- Modify: `resources/views/front/partials/catproducts.blade.php`
- Modify: `resources/views/components/subcat-sections.blade.php`
- Modify: `resources/views/components/front/section/subcatProducts.blade.php`
- Modify: `resources/views/components/front/section/subcatSections.blade.php`

**Interfaces:**
- Consumes: `products.calculator_enabled` from Task 3 and existing `#call` contact modal.
- Produces: serialized boolean `calculator_enabled` on every card/popup product payload.
- Produces: imported-product CTA text `Запросить расчёт`, opening `data-modal="#call"`.
- Preserves: current calculator/cart behavior for every existing product where `calculator_enabled=true`.

- [ ] **Step 1: Write failing backend safety tests**

Tests create one legacy enabled product and one imported disabled product. Assert:

- min-price recalculation query contains only enabled product IDs;
- category/subcategory calculator fallback never selects the disabled product;
- `/sheet-names` returns 422 with `Расчёт для этого товара пока недоступен` for the disabled product before reading Excel cache;
- direct `/cart/add` returns 422 for the disabled product and leaves the session cart empty;
- popup/preview JSON includes `calculator_enabled=false` and never exposes `source_price`.

```php
$this->postJson('/cart/add', [
    'productId' => $imported->id,
    'width' => 500,
    'height' => 500,
    'quantity' => 1,
])->assertStatus(422)->assertJsonPath('success', false);
```

- [ ] **Step 2: Run the safety test and verify it fails**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogCalculatorDisabledTest.php`

Expected: FAIL because current services/controllers do not consult `calculator_enabled`.

- [ ] **Step 3: Add backend guards at every calculation/cart boundary**

Add `where('calculator_enabled', true)` to `MinPriceRecalcService::buildProductsQuery()` and both calculator fallback queries. In Excel and cart endpoints, load the product first and return the literal 422 response before cache/formula/cart work. Add the boolean to `PreviewCardData` and `ProductController::getProdToPopup`; do not serialize `source_price`.

- [ ] **Step 4: Write failing rendered-page assertions**

Feature tests render category, collection, base subcategory, product detail, and popup data for the disabled product. Each surface must contain `Запросить расчёт`, omit an enabled cart/quick-calculator control for that item, and omit the donor price. The enabled control product must still show its existing price/cart/calculator elements.

- [ ] **Step 5: Branch all static and AJAX card paths**

In both card partials and the three slider components:

```blade
@if ($product->calculator_enabled)
    {{-- existing cart and quick-view controls stay unchanged --}}
@else
    <button type="button" class="bigProdCard__requestPrice" data-modal="#call" data-product-title="{{ $product->h1 }}">
        Запросить расчёт
    </button>
@endif
```

Use the correct loop variable in each existing template. For AJAX, prefer the server-rendered `products_html` response in both category and subcategory controllers; any compatibility JS renderer that remains must branch on the serialized boolean.

- [ ] **Step 6: Branch product detail and guard its JavaScript**

For enabled products, preserve the existing dimension fields, Excel request, quantity, price, and add-to-cart markup. For disabled products, render the first image, rewritten description/characteristics, and a styled `Запросить расчёт` button opening `#call`; do not render `.prodForm__addToCart`, calculator inputs, or price scripts for that product. Prefill the modal message with the product title only when its message field is empty.

- [ ] **Step 7: Run safety, card, cart, and calculator tests**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit tests/Feature/CatalogCalculatorDisabledTest.php tests/Feature/CalculatorProductFallbackTest.php tests/Feature/CardPriceDataTest.php tests/Feature/CartFlowTest.php tests/Feature/ExcelControllerMinPriceTest.php`

Run: `npm run test:shop`

Expected: all PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/MinPriceRecalcService.php app/Http/Controllers app/Support/PreviewCardData.php resources/views/front resources/views/components tests/Feature/CatalogCalculatorDisabledTest.php
git commit -m "fix: isolate imported products from calculator flows"
```

### Task 9: End-to-end verification, controlled dry run, deployment, and long-run start

**Files:**
- Create: `docs/runbooks/rimskie-catalog-import.md`
- Modify: `README.md`
- Local cleanup: remove untracked `.phpunit-worktree-bootstrap.php` after all worktree-only PHP verification.

**Interfaces:**
- Consumes: all Tasks 1–8.
- Produces: an operator runbook with backup/collector/ingest/review/publish/rollback commands and status interpretation.
- Produces: a validated one-source/one-product dry-run package and an active checkpointed full collection run; neither publishes public records.

- [ ] **Step 1: Write the runbook with copy-paste commands**

Document:

```text
npm run rimskie:collector -- dry-run --run <id> --max-requests 3 --max-products 1
npm run rimskie:collector -- status --run <id> --json
npm run rimskie:collector -- pause --run <id>
npm run rimskie:collector -- resume --run <id>
npm run rimskie:collector -- export --run <id>
php artisan catalog-import:ingest <absolute-export-path> --dry-run
php artisan catalog-import:ingest <absolute-export-path>
php artisan catalog:backup --run=<database-run-id>
php artisan sitemap:generate
```

Include state meanings, challenge procedure, where first photos/raw source/private prices live, manual approval flow, preflight interpretation, publication invariant checks, and exact rollback consequences. State explicitly that Excel-cache integration remains disabled.

- [ ] **Step 2: Run formatting, encoding, and static checks**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe vendor/bin/pint --test app database routes tests`

Run: `git diff --check`

Run: `rg -n "Rimskie\.com|rimskie\.com|KORTIN|source_price" resources/views app/Support app/Http/Controllers`

Expected: no public donor copy/price rendering; allowed private admin labels and validation host checks are reviewed individually.

- [ ] **Step 3: Run the complete automated suite and build**

Run: `C:\Users\user\AppData\Local\Temp\codex-php-8.3.29-nts-x64\php.exe -d auto_prepend_file=.phpunit-worktree-bootstrap.php vendor/bin/phpunit`

Run: `npm run test:js`

Run: `npm run build`

Run: `C:\Users\user\.codex\scripts\harness.cmd smoke`

Expected: every command exits 0. Record exact test/assertion counts and any intentionally skipped legacy migration check.

- [ ] **Step 4: Run one controlled donor dry run**

Use a new run ID, installed Chrome, headed persistent profile, one enabled source, `--max-requests 3`, and `--max-products 1`. If BotHunt appears, leave the profile visible, perform the already-authorized checkbox click once, and resume from the checkpoint. Verify `status --json`, request timestamps/delays, one product JSON, one first WebP image, one membership, no gallery images, and successful `export` hash/count checks.

- [ ] **Step 5: Dry-run and real-ingest the package into local staging only**

Run validator with `--dry-run`, then ingest the same package. Verify the admin run page shows source and rewritten text side-by-side, internal donor price label, photo, attributes, memberships, `needs_review`, and that public category/product counts are unchanged.

- [ ] **Step 6: Perform desktop/mobile browser QA**

Using the browser testing skill, verify at desktop and 390×844 mobile widths:

- admin run list/item comparison/actions;
- collection filter OR/AND behavior and pagination;
- clean/filtered canonical and robots directives;
- SEO chips wrapping;
- imported card/detail `Запросить расчёт` behavior;
- enabled legacy calculator remains functional;
- popup close button and outside-click close still work as existing behavior.

Capture screenshots and console/network errors in the verification report.

- [ ] **Step 7: Create and independently verify a new production backup immediately before DB changes**

Create a fresh gzip SQL backup outside the repository, verify compressed and uncompressed SHA-256, non-zero SQL size, and expected table markers. Do not run migrations if this step fails.

- [ ] **Step 8: Commit, push, deploy code, and migrate without publishing content**

```bash
git add README.md docs/runbooks/rimskie-catalog-import.md
git commit -m "docs: add catalog import operations runbook"
git push origin codex/rimskie-import
```

Delete the untracked worktree-only `.phpunit-worktree-bootstrap.php` with `apply_patch` after the last local PHP test; verify it never appears in `git status` or a commit.

Deploy the reviewed commit, run `php artisan migrate --force`, clear/rebuild Laravel caches as required by the project, run `php artisan sitemap:generate`, and execute production smoke checks. Confirm the new admin tooling exists while all imported sources/items remain non-public.

- [ ] **Step 9: Start the full slow collector with live checkpoint control**

Start one foreground-managed collector session using the exact 46-source config and default rate limits. Record run ID/session ID and first status snapshot. Do not create a scheduler/service. Monitor via `status --json`; unchanged progress during configured delays is normal. A challenge or third consecutive donor error must pause, not accelerate or retry indefinitely.

- [ ] **Step 10: Final verification commit and push**

If dry-run/browser/deployment verification required code changes, run the affected focused tests plus the full suites again, commit them with an appropriate `fix:` message, and push. The final report states backup artifact/hash, collector run ID/status, staging counts, tests/build results, deployed commit, and explicitly that publication/calculator integration remain pending manual review.
