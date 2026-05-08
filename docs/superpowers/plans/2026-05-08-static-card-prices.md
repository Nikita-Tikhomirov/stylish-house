# Static Card Prices Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Убрать AJAX-подтягивание цены из товарных карточек-превью и показывать в карточках статическую цену из `products.min_price`, сохранив запрос к `/sheet-names` только для интерактивного пересчета в калькуляторе и попапе.

**Architecture:** Карточка товара должна рендериться из одного и того же источника данных: `min_price`, `discount`, `min_width`, `min_height`, ссылка, изображения. Сервер возвращает уже готовые поля для Blade и AJAX-фильтров, а фронтенд перестает выполнять `rebuilCardsPrice()`/`rebuldPrice()` для превью-списков. Калькуляторы на странице товара, категории, подкатегории и в quick view остаются интерактивными, но запрос к `/sheet-names` выполняется только после изменения размеров или опций, а стартовое значение цены берется из уже сохраненного `min_price`.

**Tech Stack:** Laravel, Blade, vanilla JS, existing `/sheet-names` pricing endpoint, `products.min_price` and `products.discount` fields.

---

## File Map

**Primary backend files**
- Modify: `C:\Users\user\Desktop\stylish-house\app\Http\Controllers\CategoryController.php`
  - Отдает JSON для `filter-cat-products`, сейчас возвращает `price/old_price`, но не возвращает `min_price/min_width/min_height`.
- Modify: `C:\Users\user\Desktop\stylish-house\app\Http\Controllers\SubcategoryController.php`
  - Отдает JSON для `filter-subcat-products`, сейчас тоже не возвращает `min_price/min_width/min_height`.
- Modify: `C:\Users\user\Desktop\stylish-house\app\Http\Controllers\HomeController.php`
  - Отдает JSON для `/products/{categoryId}` на главной; нужно передавать `min_price` и связанные поля карточки.
- Optional refactor: `C:\Users\user\Desktop\stylish-house\app\Support\` or `app\Http\Resources\`
  - Если в процессе будет удобно, можно вынести единую сериализацию карточки в отдельный helper/resource, чтобы не дублировать shape в 3 контроллерах.

**Primary Blade/card files**
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\partials\products.blade.php`
  - Карточки в подкатории; сейчас стоят заглушки `1000₽/500₽`.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\partials\catproducts.blade.php`
  - Карточки в категории; сейчас тоже заглушки `1000₽/500₽`.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory.blade.php`
  - Удалить карточечный AJAX-ребилд цены, обновить JS-рендер фильтрованных карточек и инициализацию цены в калькуляторе/попапе.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory-template-1.blade.php`
  - Та же логика для альтернативного шаблона подкатегории.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory-plumbing.blade.php`
  - Та же логика для plumbing-версии.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\category.blade.php`
  - Удалить карточечный AJAX-ребилд, обновить карточки после фильтра и стартовую цену калькулятора.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\categoryrolstavni.blade.php`
  - Та же логика для роллетной категории.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\home.blade.php`
  - Убрать `rebuldPrice()` для популярных карточек, рендерить статическую цену на старте и при смене таба.
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\product.blade.php`
  - Связанные и альтернативные товары сейчас тоже получают цену через AJAX на загрузке; перевести на статический вывод.

**Potential shared frontend helper**
- Create or embed locally: `C:\Users\user\Desktop\stylish-house\resources\views\front\partials\card-price-js.blade.php` or local JS helper blocks
  - Общие функции: форматирование статической цены, разметка `normalPrice/discount`, meta размеров.
  - Делать только если это уменьшит копипасту; не выносить ради выноса.

**Tests / verification**
- Create: `C:\Users\user\Desktop\stylish-house\tests\Feature\CardPriceDataTest.php`
  - Проверка JSON shape для home/category/subcategory filters.
- Optional: `C:\Users\user\Desktop\stylish-house\tests\Feature\ProductPageCardRenderingTest.php`
  - Если в проекте есть рабочие feature tests на Blade, проверить наличие `min_price` в HTML карточки.

---

### Task 1: Зафиксировать единый контракт карточки товара

**Files:**
- Modify: `C:\Users\user\Desktop\stylish-house\app\Http\Controllers\CategoryController.php`
- Modify: `C:\Users\user\Desktop\stylish-house\app\Http\Controllers\SubcategoryController.php`
- Modify: `C:\Users\user\Desktop\stylish-house\app\Http\Controllers\HomeController.php`
- Test: `C:\Users\user\Desktop\stylish-house\tests\Feature\CardPriceDataTest.php`

- [ ] **Step 1: Определить финальный shape данных карточки**

Новый shape для всех AJAX-ответов карточек должен включать как минимум:

```php
[
    'id' => $product->id,
    'slug' => $product->slug,
    'h1' => $product->h1,
    'discount' => $product->discount,
    'min_price' => $product->min_price,
    'min_width' => $product->min_width,
    'min_height' => $product->min_height,
    'model' => $prodModelName,
    'modelid' => $product->model_id,
    'cloth' => $product->cloth,
    'category' => [
        'slug' => $product->category->slug,
        'titleh1' => $product->category->titleh1,
    ],
    'subcategory' => [
        'slug' => $product->subcategory->slug,
    ],
    'image_path' => $product->image_path,
    'image_thumb_path' => $product->image_thumb_path,
    'fabric_photo' => $encodePath($product->fabric_photo),
    'fabric_thumb_path' => $encodePath($product->fabric_thumb_path),
]
```

- [ ] **Step 2: Написать feature-тест на JSON shape для фильтра подкатегории**

```php
public function test_subcategory_filter_returns_static_card_price_fields(): void
{
    $response = $this->postJson('/filter-subcat-products/' . $subcategory->id, [
        'models' => [],
        'colors' => [],
        'materials' => [],
        'page' => 1,
    ]);

    $response->assertOk()
        ->assertJsonPath('products.0.min_price', 9900)
        ->assertJsonPath('products.0.min_width', 700)
        ->assertJsonPath('products.0.min_height', 700)
        ->assertJsonPath('products.0.discount', 10);
}
```

Run: `php artisan test --filter=CardPriceDataTest`
Expected: FAIL, потому что `min_price/min_width/min_height` пока не возвращаются.

- [ ] **Step 3: Добавить те же поля в `SubcategoryController::filterProducts()`**

Минимальное изменение: заменить `price/old_price` на реальные поля карточки и вернуть размеры.

- [ ] **Step 4: Добавить те же поля в `CategoryController::filterProducts()` и `HomeController::getProductsByCategory()`**

Нужно привести все три ответа к одному формату, чтобы карточки рендерились одинаково на всех витринах.

- [ ] **Step 5: Прогнать feature-тесты повторно**

Run: `php artisan test --filter=CardPriceDataTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CategoryController.php app/Http/Controllers/SubcategoryController.php app/Http/Controllers/HomeController.php tests/Feature/CardPriceDataTest.php
git commit -m "feat: expose static card min price data"
```

### Task 2: Научить Blade-карточки показывать статическую цену из `min_price`

**Files:**
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\partials\products.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\partials\catproducts.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\product.blade.php`

- [ ] **Step 1: Зафиксировать правило отображения цены**

Использовать одну формулу для всех статических карточек:

```php
@php
    $basePrice = $prod->min_price;
    $discount = (float) ($prod->discount ?? 0);
    $hasDiscount = $basePrice !== null && $discount > 0;
    $finalPrice = $hasDiscount ? (int) floor($basePrice * (1 - $discount / 100)) : $basePrice;
@endphp
```

Правила вывода:
- если `min_price` есть и скидки нет -> показываем только `.discount` с финальной ценой
- если `min_price` есть и скидка есть -> `.normalPrice = base`, `.discount = discounted`
- если `min_price` нет -> `Цена по запросу`

- [ ] **Step 2: Заменить заглушки `1000₽/500₽` в `partials/products.blade.php`**

Сейчас там стоят фиктивные цены. Заменить их на расчет по `min_price`.

- [ ] **Step 3: Повторить то же в `partials/catproducts.blade.php`**

Этот partial используется в категории и должен совпадать по поведению с подкатегорией.

- [ ] **Step 4: Повторить то же в блоках related/alternative товаров в `product.blade.php`**

Там тоже сейчас заглушки, а ниже отдельный AJAX-ребилд. Сначала нужно сделать корректный статический рендер.

- [ ] **Step 5: Проверить fallback на `Цена по запросу`**

Manual:
- открыть карточку с `min_price != null`
- открыть карточку с `min_price == null`
- убедиться, что вторая не рисует `0₽`

- [ ] **Step 6: Commit**

```bash
git add resources/views/front/partials/products.blade.php resources/views/front/partials/catproducts.blade.php resources/views/front/product.blade.php
git commit -m "feat: render static min price in product cards"
```

### Task 3: Убрать карточечный AJAX-ребилд на страницах категорий и подкатегорий

**Files:**
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory-template-1.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory-plumbing.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\category.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\categoryrolstavni.blade.php`

- [ ] **Step 1: Удалить функции `rebuilCardsPrice()`/`rebuldPrice()` для карточек-превью**

Удаляем:
- вызовы на `DOMContentLoaded`
- вызовы после пагинации/фильтрации
- любые `fetch('/sheet-names?...')` внутри карточечных циклов `.card`

Оставляем только код quick view и калькулятора.

- [ ] **Step 2: Добавить локальный helper рендера цены для AJAX-вставляемых карточек**

JS helper должен строить price HTML из уже пришедших `min_price` и `discount`, без сети.

```js
function buildCardPriceHtml(product) {
    const basePrice = Number(product.min_price || 0);
    const discount = Number(product.discount || 0);

    if (!basePrice) {
        return `
            <div class="bigProdCard__priceWrap">
                <span class="discount">Цена по запросу</span>
            </div>
        `;
    }

    if (discount > 0) {
        const finalPrice = Math.floor(basePrice * (1 - discount / 100));
        return `
            <div class="bigProdCard__priceWrap">
                <span class="normalPrice">${basePrice}₽</span>
                <span class="discount">${finalPrice}₽</span>
            </div>
        `;
    }

    return `
        <div class="bigProdCard__priceWrap">
            <span class="discount">${basePrice}₽</span>
        </div>
    `;
}
```

- [ ] **Step 3: Использовать helper при AJAX-фильтрации в `subcategory.blade.php`**

Также не забыть передавать и рендерить:
- `data-min-width`
- `data-min-height`
- мета-блок `От ... мм x ... мм`

- [ ] **Step 4: Повторить тот же подход в `subcategory-template-1`, `subcategory-plumbing`, `category`, `categoryrolstavni`**

Цель: ни один из этих шаблонов не должен дергать `/sheet-names` для карточек после фильтра, пагинации или начальной загрузки.

- [ ] **Step 5: Прогнать ручную проверку по страницам**

Manual:
- `/jaluzi/gorizontalnye-zhalyuzi/`
- одна обычная категория
- одна подкатегория на основном шаблоне
- одна подкатегория на `template-1`
- одна подкатегория plumbing

Проверить:
- карточки загружаются без заметной задержки цены
- при фильтре цена не мигает и не меняется через сеть
- в Network нет пачки `/sheet-names` на карточки

- [ ] **Step 6: Commit**

```bash
git add resources/views/front/subcategory.blade.php resources/views/front/subcategory-template-1.blade.php resources/views/front/subcategory-plumbing.blade.php resources/views/front/category.blade.php resources/views/front/categoryrolstavni.blade.php
git commit -m "refactor: remove ajax price rebuild from preview cards"
```

### Task 4: Убрать карточечный AJAX-ребилд на главной и странице товара

**Files:**
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\home.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\product.blade.php`

- [ ] **Step 1: Удалить `rebuldPrice()` с главной**

На главной сейчас при загрузке и при смене таба популярных товаров идет `/sheet-names` на каждую карточку. Это нужно убрать полностью.

- [ ] **Step 2: Обновить шаблон карточки популярных товаров на главной**

В HTML, который строится после `fetch('/products/{categoryId}')`, использовать уже готовые `min_price`, `discount`, `min_width`, `min_height`.

- [ ] **Step 3: Удалить карточечный price-fetch из `product.blade.php`**

Связанные и альтернативные товары должны рендерить статическую цену сразу из Blade, без отдельного цикла `allCards.forEach(... fetch('/sheet-names'))`.

- [ ] **Step 4: Ручная проверка**

Manual:
- главная: первая загрузка популярных товаров
- главная: смена вкладки категории
- страница товара: связанные товары
- страница товара: альтернативные товары

Ожидаемо:
- карточки сразу показывают цену
- сеть не дергает `/sheet-names` на превью-товары

- [ ] **Step 5: Commit**

```bash
git add resources/views/front/home.blade.php resources/views/front/product.blade.php
git commit -m "refactor: use static card prices on home and product pages"
```

### Task 5: Оставить `/sheet-names` только для перерасчета в калькуляторе и попапе

**Files:**
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory-template-1.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\subcategory-plumbing.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\category.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\categoryrolstavni.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\home.blade.php`
- Modify: `C:\Users\user\Desktop\stylish-house\resources\views\front\product.blade.php`

- [ ] **Step 1: Заменить автозапуск `fetchPrice()` при инициализации калькулятора на статическую стартовую цену**

Сейчас в нескольких шаблонах `getPrice()` вызывает `fetchPrice()` сразу на загрузке. Нужно изменить поведение:
- при инициализации выставлять стартовую цену из `min_price`
- `fetchPrice()` вызывать только после события пользователя: `input/change` размеров, управления, ткани и т.д.

- [ ] **Step 2: Добавить helper для стартовой цены калькулятора**

Пример подхода:

```js
function applyInitialMinPrice(slide, minPrice, discount) {
    const priceElement = slide.querySelector('.prodForm__price');
    if (!priceElement) return;

    if (!minPrice) {
        priceElement.textContent = 'Цена по запросу';
        return;
    }

    const finalPrice = discount > 0
        ? Math.floor(minPrice * (1 - discount / 100))
        : minPrice;

    priceElement.textContent = `Цена: ${finalPrice}₽`;
}
```

- [ ] **Step 3: Сохранить запрос к `/sheet-names` только на пересчет**

Допустимые триггеры:
- изменение `width`
- изменение `height`
- изменение `control`
- изменение тканей/опций калькулятора
- изменение значений в quick view popup

Недопустимые триггеры:
- просто загрузка страницы
- просто рендер списка карточек
- просто открытие страницы категории/подкатегории/товара без действий пользователя

- [ ] **Step 4: Ручная проверка поведения калькулятора**

Manual:
- открыть страницу товара
- зафиксировать, что стартовая цена уже показана без запроса
- поменять ширину/высоту -> запрос к `/sheet-names` появился
- открыть quick view -> стартовая цена есть
- поменять размер в quick view -> запрос к `/sheet-names` появился

- [ ] **Step 5: Commit**

```bash
git add resources/views/front/subcategory.blade.php resources/views/front/subcategory-template-1.blade.php resources/views/front/subcategory-plumbing.blade.php resources/views/front/category.blade.php resources/views/front/categoryrolstavni.blade.php resources/views/front/home.blade.php resources/views/front/product.blade.php
git commit -m "refactor: fetch recalculated price only on calculator changes"
```

### Task 6: Финальная проверка и выпуск

**Files:**
- Verify only

- [ ] **Step 1: Прогнать релевантные тесты**

```bash
php artisan test --filter=CardPriceDataTest
```

Если есть уже существующие тесты на фильтры/контроллеры, прогнать и их тоже.

- [ ] **Step 2: Проверить отсутствие карточечных запросов к `/sheet-names`**

Manual via browser devtools:
- категория
- подкатегория
- главная
- страница товара

Expected:
- карточки не вызывают `/sheet-names`
- калькулятор вызывает `/sheet-names` только после изменения параметров

- [ ] **Step 3: Smoke-check регрессий по цене**

Проверить кейсы:
- товар без скидки
- товар со скидкой
- товар без `min_price`
- товар с `min_width/min_height`
- фильтрация/пагинация на витрине
- quick view popup

- [ ] **Step 4: Commit итоговых правок**

```bash
git add -A
git commit -m "feat: switch preview cards to static min prices"
```

- [ ] **Step 5: Push**

```bash
git push
```

---

## Risks / Notes

- Сейчас в карточечных AJAX-ответах используются поля `price/old_price`, но фактический источник правды для этой задачи должен быть `min_price`. Это может потребовать аккуратно убрать старую путаницу имен, не сломав другие места.
- В нескольких шаблонах есть почти одинаковый JS. Если трогать его локально в каждом файле, легко пропустить одну витрину. Поэтому при реализации нужен checklist по всем шаблонам выше.
- На некоторых карточках `discount` сейчас пустой строкой или не везде проставлен при AJAX-рендере. Это нужно нормализовать, иначе статическая скидка будет считаться по-разному.
- На странице товара связанные карточки тоже входят в scope, иначе после основной переделки там останется старое поведение с AJAX.
- В `subcategory-template-1.blade.php` уже видны проблемы с кодировкой в отдельных `includes("...")` строках. При правках важно сохранить UTF-8 и не размножить битые строки.

## Acceptance Criteria

- На карточках-превью цена отображается сразу из `min_price`, без запроса к `/sheet-names`.
- После фильтрации, пагинации и смены табов карточки продолжают показывать ту же статическую цену.
- `/sheet-names` используется только для пересчета после изменения размеров или опций в калькуляторе/попапе.
- Карточки корректно показывают скидку и fallback `Цена по запросу`.
- Минимальные размеры в карточках продолжают отображаться и после AJAX-перерисовок.

## Self-Review

- Spec coverage: покрыты карточки в категориях, подкатегориях, на главной и на странице товара; отдельно сохранен интерактивный пересчет в калькуляторе и popup.
- Placeholder scan: в плане нет `TODO/TBD`; все основные файлы и проверки перечислены явно.
- Type consistency: во всех задачах используется один контракт `min_price/min_width/min_height/discount`.

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-05-08-static-card-prices.md`. Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?
