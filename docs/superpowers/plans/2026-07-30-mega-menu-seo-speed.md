# Editable Mega Menu, Product SEO, and Speed Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an editable, cached mega menu that links SEO landing pages, make product meta descriptions unique, and remove the largest verified performance bottlenecks.

**Architecture:** Store navigation as a validated three-level tree and expose it through a cached `NavigationService` shared with the header by a view composer. Keep frontend behavior in focused JavaScript/CSS modules, format product descriptions at render time, and lazy-load heavyweight third-party map code.

**Tech Stack:** PHP 8.1, Laravel 10, Blade, Vite 5, vanilla JavaScript, PHPUnit 10, Node test runner.

## Global Constraints

- Create and validate a production database/code backup before implementation.
- Do not rewrite existing SEO category/page content or stored meta tags.
- Do not add a frontend framework or drag-and-drop dependency.
- Allow entity-backed links and validated custom internal links.
- Keep the navigation hierarchy at tab -> section -> link.
- Visually verify every frontend change at 1920x1080, 390x844, and 360x800.
- Never run database-mutating tests against production.

---

### Task 1: Navigation persistence and cached resolver

**Files:**
- Create: `database/migrations/2026_07_30_000000_create_navigation_menus_table.php`
- Create: `database/migrations/2026_07_30_000100_create_navigation_items_table.php`
- Create: `app/Models/NavigationMenu.php`
- Create: `app/Models/NavigationItem.php`
- Create: `app/Services/NavigationService.php`
- Test: `tests/Feature/NavigationServiceTest.php`

**Interfaces:**
- Produces: `NavigationService::header(): array`
- Produces: `NavigationService::forgetHeader(): void`
- Produces: `NavigationItem::resolvedUrl(): string`

- [ ] **Step 1: Write failing resolver and cache tests**

Cover category, subcategory, page, and custom URLs; inactive records; position ordering; three-level nesting; and cache reuse.

```php
$payload = app(NavigationService::class)->header();
$this->assertSame('Жалюзи', $payload['tabs'][0]['label']);
$this->assertSame('/jaluzi/gorizontalnye-zhalyuzi', $payload['tabs'][0]['sections'][0]['links'][0]['url']);
```

- [ ] **Step 2: Run the test and confirm it fails**

Run: `/opt/php81/bin/php artisan test --filter=NavigationServiceTest`

Expected: failure because navigation tables and service do not exist.

- [ ] **Step 3: Implement migrations, models, URL resolution, and cache**

Use `Cache::rememberForever('navigation:header-catalog:v1', ...)`, eager-load the constrained tree, return plain arrays, and resolve entity URLs through the existing category/subcategory/page route shapes. Reject external custom URLs in model/controller validation rather than rewriting them.

- [ ] **Step 4: Run the focused test**

Run: `/opt/php81/bin/php artisan test --filter=NavigationServiceTest`

Expected: all resolver and cache assertions pass.

- [ ] **Step 5: Commit**

```bash
git add app/Models app/Services database/migrations tests/Feature/NavigationServiceTest.php
git commit -m "feat: add cached navigation tree"
```

### Task 2: Visual admin menu editor

**Files:**
- Create: `app/Http/Controllers/NavigationMenuController.php`
- Create: `app/Http/Requests/UpdateNavigationMenuRequest.php`
- Create: `resources/views/admin/navigation-menu.blade.php`
- Create: `resources/js/admin-navigation-editor.js`
- Create: `resources/css/admin-navigation-editor.css`
- Modify: `resources/views/components/admin/sidebar.blade.php`
- Modify: `routes/web.php`
- Modify: `vite.config.js`
- Test: `tests/Feature/NavigationMenuAdminTest.php`

**Interfaces:**
- Consumes: `NavigationService::forgetHeader()`
- Produces: `GET admin.navigation.edit`
- Produces: `PUT admin.navigation.update`

- [ ] **Step 1: Write failing authorization and validation tests**

Assert guest/non-admin denial, admin access, transactional replacement, invalid hierarchy rejection, missing entity rejection, external custom URL rejection, valid relative URL acceptance, and cache invalidation.

```php
$response = $this->actingAs($admin)->put(route('admin.navigation.update'), [
    'items' => [[
        'node_type' => 'tab',
        'label' => 'Жалюзи',
        'children' => [],
    ]],
]);
$response->assertRedirect(route('admin.navigation.edit'));
```

- [ ] **Step 2: Run tests and confirm failure**

Run: `/opt/php81/bin/php artisan test --filter=NavigationMenuAdminTest`

- [ ] **Step 3: Implement controller, form request, routes, and editor UI**

The controller supplies all category, subcategory, and page choices. The request recursively validates the three levels and internal URLs. The editor serializes the ordered tree into one hidden JSON field and provides add, rename, enable, remove, and native drag controls plus a responsive preview.

- [ ] **Step 4: Run focused tests and Vite build**

Run:

```bash
/opt/php81/bin/php artisan test --filter=NavigationMenuAdminTest
npm run build
```

- [ ] **Step 5: Commit**

```bash
git add app/Http resources/views/admin resources/views/components/admin resources/js/admin-navigation-editor.js resources/css/admin-navigation-editor.css routes/web.php vite.config.js tests/Feature/NavigationMenuAdminTest.php
git commit -m "feat: add visual header menu editor"
```

### Task 3: Accessible desktop mega menu and mobile drawer

**Files:**
- Create: `resources/views/components/front/header-navigation.blade.php`
- Create: `resources/js/header-navigation.js`
- Create: `resources/css/header-navigation.css`
- Modify: `resources/views/components/front/header.blade.php`
- Modify: `resources/js/shop.js`
- Modify: `resources/views/components/front/head.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/HeaderNavigationMarkupTest.php`
- Test: `tests/js/header-navigation.test.mjs`

**Interfaces:**
- Consumes: `NavigationService::header(): array`
- Produces: `[data-header-navigation]` with desktop tab panel and mobile accordion behavior

- [ ] **Step 1: Write failing markup and JavaScript tests**

Assert semantic buttons, `aria-expanded`, `aria-controls`, link rendering, no menu images, Escape close, focus return, local filtering, one open mobile accordion, and body scroll restoration.

- [ ] **Step 2: Run tests and confirm failure**

Run:

```bash
/opt/php81/bin/php artisan test --filter=HeaderNavigationMarkupTest
npm run test:shop
```

- [ ] **Step 3: Implement view composer, Blade component, CSS, and JS**

Render a quiet desktop overlay with tab rail and column grid. Render the same payload as mobile accordion groups below 1024px. Keep the measurement CTA as a plain link to `/shop-pages/zamer`. Add no category or product images.

- [ ] **Step 4: Run tests and build**

Run:

```bash
/opt/php81/bin/php artisan test --filter=HeaderNavigationMarkupTest
npm run test:shop
npm run build
```

- [ ] **Step 5: Commit**

```bash
git add app/Providers resources/views/components/front resources/js resources/css tests/Unit/HeaderNavigationMarkupTest.php tests/js/header-navigation.test.mjs
git commit -m "feat: replace catalog tree with responsive mega menu"
```

### Task 4: Unique product meta descriptions

**Files:**
- Create: `app/Support/ProductMetaDescription.php`
- Modify: `resources/views/front/product.blade.php`
- Modify: `resources/views/front/product-plumbing.blade.php`
- Test: `tests/Unit/ProductMetaDescriptionTest.php`

**Interfaces:**
- Produces: `ProductMetaDescription::make(string $title, ?string $description): string`

- [ ] **Step 1: Write failing formatter tests**

Cover duplicate detection, HTML stripping, whitespace normalization, empty description fallback, grammatical title prefix, Cyrillic first-letter lowering, and word-boundary length limiting.

```php
$description = ProductMetaDescription::make(
    'Стандарт Дарина абрикосовый',
    'Практичные рулонные шторы создают комфорт.'
);
$this->assertStringStartsWith('Стандарт Дарина абрикосовый — практичные', $description);
```

- [ ] **Step 2: Run the test and confirm failure**

Run: `/opt/php81/bin/php artisan test --filter=ProductMetaDescriptionTest`

- [ ] **Step 3: Implement formatter and use it in both product templates**

Keep stored descriptions unchanged. Return at most 160 characters, trim at the last complete word, and keep the product title at the start so near-identical product pages become unique.

- [ ] **Step 4: Run focused and product-heading tests**

Run:

```bash
/opt/php81/bin/php artisan test --filter='ProductMetaDescriptionTest|ProductHeadingTest'
```

- [ ] **Step 5: Commit**

```bash
git add app/Support resources/views/front/product*.blade.php tests/Unit/ProductMetaDescriptionTest.php
git commit -m "fix: make product meta descriptions unique"
```

### Task 5: Remove verified performance bottlenecks

**Files:**
- Create: `resources/js/lazy-yandex-map.js`
- Modify: `resources/views/components/front/section/map.blade.php`
- Modify: `resources/views/components/front/head.blade.php`
- Modify: `resources/js/shop.js`
- Modify: `resources/css/main.css`
- Test: `tests/Unit/AuditHtmlPerformanceTest.php`
- Test: `tests/js/lazy-yandex-map.test.mjs`

**Interfaces:**
- Produces: `initLazyYandexMaps(root: Document): void`

- [ ] **Step 1: Extend tests for lazy third-party loading and fonts**

Assert the map partial contains no eager Yandex API script, the loader observes `[data-yandex-map]`, and the head contains one font stylesheet with preconnects rather than duplicate CSS imports.

- [ ] **Step 2: Run tests and confirm failure**

Run:

```bash
/opt/php81/bin/php artisan test --filter=AuditHtmlPerformanceTest
npm run test:shop
```

- [ ] **Step 3: Implement lazy map and consolidate font loading**

Use `IntersectionObserver` with `rootMargin: '600px 0px'`; load the Yandex script once through a shared Promise; initialize the existing coordinates and controls after the API is ready. Preserve the full-width map dimensions and add a lightweight loading state.

- [ ] **Step 4: Build and run focused tests**

Run:

```bash
/opt/php81/bin/php artisan test --filter=AuditHtmlPerformanceTest
npm run test:shop
npm run build
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/lazy-yandex-map.js resources/js/shop.js resources/views/components/front/section/map.blade.php resources/views/components/front/head.blade.php resources/css/main.css tests
git commit -m "perf: defer maps and consolidate font loading"
```

### Task 6: Seed editorial links and remove legacy menu queries

**Files:**
- Create: `database/seeders/HeaderNavigationSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `app/Http/Controllers/CategoryController.php`
- Modify: `app/Http/Controllers/SubcategoryController.php`
- Modify: `app/Http/Controllers/ProductController.php`
- Modify: `app/Http/Controllers/CartController.php`
- Modify: `app/Http/Controllers/CheckoutController.php`
- Modify: `app/Http/Controllers/OrderController.php`
- Modify: `app/Http/Controllers/PageController.php`
- Modify: `app/Http/Controllers/Auth/LoginController.php`
- Modify: `app/Http/Controllers/Auth/RegisterController.php`
- Test: `tests/Feature/HeaderNavigationSeederTest.php`
- Test: `tests/Unit/HeaderQueryRemovalTest.php`

**Interfaces:**
- Consumes: category, subcategory, and page records already present in production
- Produces: idempotent `HeaderNavigationSeeder`

- [ ] **Step 1: Write failing seeder and source tests**

Assert one tab per active catalog category, a category landing link, grouped subcategory links, popular quick links, utility page links, idempotent reruns, and absence of legacy full product menu queries in controllers.

- [ ] **Step 2: Run tests and confirm failure**

Run: `/opt/php81/bin/php artisan test --filter='HeaderNavigationSeederTest|HeaderQueryRemovalTest'`

- [ ] **Step 3: Implement idempotent seed and remove repeated controller queries**

Seed entity-backed links so future slug changes resolve automatically. Never seed individual products into the mega menu. Remove obsolete `categoriesInCatalogMenu`/`categoriesInHeaderMenu` queries and compact entries from all listed controllers.

- [ ] **Step 4: Run the complete PHP suite**

Run: `/opt/php81/bin/php artisan test`

- [ ] **Step 5: Commit**

```bash
git add database/seeders app/Http/Controllers tests
git commit -m "perf: centralize and seed header navigation"
```

### Task 7: Production deployment and visual/performance verification

**Files:**
- Modify only if defects are found during QA.
- Record: `docs/reports/2026-07-30-mega-menu-speed-verification.md`

**Interfaces:**
- Consumes: verified backup path and all completed tasks
- Produces: deployed menu, admin editor, product descriptions, and before/after evidence

- [ ] **Step 1: Run pre-deploy gates**

Run:

```bash
/opt/php81/bin/php artisan test
npm run test:shop
npm run build
C:\Users\user\.codex\scripts\harness.cmd smoke
```

- [ ] **Step 2: Deploy additive migrations and assets**

Upload changed files, run `php artisan migrate --force`, run only `HeaderNavigationSeeder`, rebuild caches, and verify `/`, one category, one subcategory, one standard product, one plumbing product, `/admin/navigation-menu`, and `/sitemap.xml` return expected statuses.

- [ ] **Step 3: Perform desktop and mobile visual QA**

At 1920x1080, 390x844, and 360x800 verify header alignment, open/close behavior, tabs/accordions, no clipping, no horizontal overflow, correct focus behavior, CTA, product controls, map loading, and admin tree editing preview.

- [ ] **Step 4: Measure speed and inspect runtime errors**

Run Lighthouse mobile and desktop three times each, report medians for Performance, FCP, LCP, Speed Index, TBT, CLS, and transfer bytes. Confirm Yandex Maps is absent from initial requests above the map and review console/network failures.

- [ ] **Step 5: Write verification report, commit, and push**

```bash
git add docs/reports
git commit -m "docs: verify mega menu and performance improvements"
git push
```
