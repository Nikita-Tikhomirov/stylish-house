@php
    $categoryPayload = $categories->map(function ($category) {
        return [
            'id' => $category->id,
            'title' => $category->titleh1,
            'slug' => $category->slug,
            'url' => \App\Support\CanonicalUrl::route('category.show', ['slug' => $category->slug]),
            'base' => [
                'jaluzi' => 2500,
                'story' => 2200,
                'rolstavni' => 9000,
            ][$category->slug] ?? 3000,
            'min' => [
                'jaluzi' => 3500,
                'story' => 4000,
                'rolstavni' => 9000,
            ][$category->slug] ?? 3500,
            'subcategories' => $category->subcategories->map(fn ($subcategory) => [
                'title' => $subcategory->titleh1,
                'slug' => $subcategory->slug,
                'url' => \App\Support\CanonicalUrl::route('subcategory.show', [
                    'category_slug' => $category->slug,
                    'subcategory_slug' => $subcategory->slug,
                ]),
            ])->values(),
        ];
    })->values();
@endphp

<section class="siteCalc" data-site-calculator data-categories='@json($categoryPayload)'>
    <div class="siteCalc__grid">
        <div class="siteCalc__form">
            <h2 class="siteCalc__heading">Параметры изделия</h2>
            <div class="siteCalc__field">
                <span>Направление</span>
                <div class="siteCalc__tabs" data-calc-categories></div>
            </div>
            <label class="siteCalc__field">
                <span>Тип изделия</span>
                <select data-calc-subcategory></select>
            </label>
            <div class="siteCalc__sizes">
                <label class="siteCalc__field">
                    <span>Ширина, мм</span>
                    <input type="number" min="300" max="6000" step="10" value="900" data-calc-width>
                </label>
                <label class="siteCalc__field">
                    <span>Высота, мм</span>
                    <input type="number" min="300" max="6000" step="10" value="1200" data-calc-height>
                </label>
                <label class="siteCalc__field">
                    <span>Количество</span>
                    <input type="number" min="1" max="50" step="1" value="1" data-calc-qty>
                </label>
            </div>
            <div class="siteCalc__checks">
                <label><input type="checkbox" data-calc-install> Нужен монтаж</label>
                <label><input type="checkbox" data-calc-hard> Сложный проем</label>
                <label><input type="checkbox" data-calc-motor> Электропривод</label>
            </div>
            <button class="siteCalc__button btn" type="button" data-modal="#measure">Заказать точный расчет</button>
        </div>
        <aside class="siteCalc__result">
            <div class="siteCalc__resultLabel">Предварительная стоимость</div>
            <div class="siteCalc__price" data-calc-price>0 ₽</div>
            <div class="siteCalc__note" data-calc-note></div>
            <a class="siteCalc__link" href="#" data-calc-link>Перейти в выбранный раздел</a>
        </aside>
    </div>
</section>

<style>
    .siteCalc{margin-top:35px}
    .siteCalc__grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:28px;background:#f6f7f9;border-radius:8px;padding:32px}
    .siteCalc__heading{font-size:24px;margin:0 0 22px}
    .siteCalc__field{display:flex;flex-direction:column;gap:9px;margin-bottom:18px;font-size:14px;color:#55606b}
    .siteCalc__field input,.siteCalc__field select{height:48px;border:1px solid #dfe4ea;border-radius:6px;padding:0 14px;background:#fff;font-size:16px;color:#17212b}
    .siteCalc__tabs{display:flex;flex-wrap:wrap;gap:8px}
    .siteCalc__tab{border:1px solid #dfe4ea;background:#fff;border-radius:6px;padding:10px 14px;cursor:pointer;font-weight:700}
    .siteCalc__tab.active{border-color:#0989ff;color:#0989ff;background:#eef7ff}
    .siteCalc__sizes{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .siteCalc__checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 18px;margin:8px 0 24px}
    .siteCalc__checks label{display:flex;align-items:center;gap:10px}
    .siteCalc__button{width:100%;height:52px;border:none}
    .siteCalc__result{background:#fff;border:1px solid #dfe4ea;border-radius:8px;padding:28px;align-self:start;position:sticky;top:20px}
    .siteCalc__resultLabel{font-size:14px;color:#7a8794;margin-bottom:12px}
    .siteCalc__price{font-size:38px;font-weight:800;margin-bottom:14px}
    .siteCalc__note{line-height:1.55;color:#55606b;margin-bottom:20px}
    .siteCalc__link{color:#0989ff;font-weight:700}
    @media(max-width:900px){.siteCalc__grid{grid-template-columns:1fr;padding:22px}.siteCalc__sizes,.siteCalc__checks{grid-template-columns:1fr}.siteCalc__result{position:static}}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('[data-site-calculator]');
        if (!root) return;
        const categories = JSON.parse(root.dataset.categories || '[]');
        if (!categories.length) return;

        const tabs = root.querySelector('[data-calc-categories]');
        const subcategorySelect = root.querySelector('[data-calc-subcategory]');
        const widthInput = root.querySelector('[data-calc-width]');
        const heightInput = root.querySelector('[data-calc-height]');
        const qtyInput = root.querySelector('[data-calc-qty]');
        const installInput = root.querySelector('[data-calc-install]');
        const hardInput = root.querySelector('[data-calc-hard]');
        const motorInput = root.querySelector('[data-calc-motor]');
        const priceNode = root.querySelector('[data-calc-price]');
        const noteNode = root.querySelector('[data-calc-note]');
        const linkNode = root.querySelector('[data-calc-link]');
        let activeCategory = categories[0];

        function formatPrice(value) {
            return `${Math.round(value).toLocaleString('ru-RU')} ₽`;
        }

        function renderTabs() {
            tabs.innerHTML = categories.map((category) => (
                `<button type="button" class="siteCalc__tab${category.id === activeCategory.id ? ' active' : ''}" data-category-id="${category.id}">${category.title}</button>`
            )).join('');
        }

        function renderSubcategories() {
            subcategorySelect.innerHTML = activeCategory.subcategories.map((subcategory) => (
                `<option value="${subcategory.url}">${subcategory.title}</option>`
            )).join('');
        }

        function calculate() {
            const width = Math.max(300, Number(widthInput.value) || 0);
            const height = Math.max(300, Number(heightInput.value) || 0);
            const qty = Math.max(1, Number(qtyInput.value) || 1);
            const area = Math.max(0.5, (width * height) / 1000000);
            let price = Math.max(activeCategory.min, area * activeCategory.base) * qty;
            if (installInput.checked) price += 1800 * qty;
            if (hardInput.checked) price *= 1.15;
            if (motorInput.checked) price += 7000 * qty;

            priceNode.textContent = `от ${formatPrice(price)}`;
            noteNode.textContent = `Расчет ориентировочный: ${activeCategory.title}, ${width} x ${height} мм, ${qty} шт. Точная стоимость зависит от материала, комплектации и замера.`;
            linkNode.href = subcategorySelect.value || activeCategory.url;
        }

        tabs.addEventListener('click', function (event) {
            const button = event.target.closest('[data-category-id]');
            if (!button) return;
            activeCategory = categories.find((category) => String(category.id) === button.dataset.categoryId) || activeCategory;
            renderTabs();
            renderSubcategories();
            calculate();
        });

        [subcategorySelect, widthInput, heightInput, qtyInput, installInput, hardInput, motorInput].forEach((element) => {
            element.addEventListener('input', calculate);
            element.addEventListener('change', calculate);
        });

        renderTabs();
        renderSubcategories();
        calculate();
    });
</script>
