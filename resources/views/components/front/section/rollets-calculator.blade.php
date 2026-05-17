@props(['products' => collect()])

@php
    $calcProducts = collect($products)->map(function ($product) {
        $imagePath = $product->image_thumb_path ?: $product->image_path ?: $product->fabric_thumb_path ?: $product->fabric_photo;
        $imageUrl = $imagePath ? asset(ltrim($imagePath, '/')) : null;

        return [
            'title' => $product->h1,
            'label' => $product->cloth ?: $product->color ?: $product->h1,
            'image' => $imageUrl,
        ];
    })->filter(fn ($item) => !empty($item['image']))->values();

    $activeCalcProduct = $calcProducts->first();
@endphp

<!-- Калькулятор рольставен -->
<section class="s-rolletsCalc wrapper">
    <h2 class="s-subcats__title title"> <span>Рассчитать стоимость рольставен</span><svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"> </path>
        </svg></h2>
    <div class="s-rolletsCalc__container" id="rollets-calc">
        <!-- Мобильная версия размеров -->
        <div class="s-rolletsCalc__mobileSizes">
            <div class="s-rolletsCalc__header">Укажите размеры:</div>
            <div class="s-rolletsCalc__sizesBlock">
                <div class="s-rolletsCalc__inputs">
                    <label class="s-rolletsCalc__label">
                        Ширина, мм
                        <input type="number" min="500" max="6000" name="rl_width_mob" value="900" class="s-rolletsCalc__input">
                    </label>
                    <label class="s-rolletsCalc__label">
                        Высота, мм
                        <input type="number" min="500" max="6000" name="rl_height_mob" value="900" class="s-rolletsCalc__input">
                    </label>
                </div>
                <img src="/img/sizes.png" alt="Размеры" class="s-rolletsCalc__sizesImg">
            </div>
        </div>

        <!-- Левая панель -->
        <div class="s-rolletsCalc__left">
            <!-- Выбор цвета -->
            <div class="s-rolletsCalc__colorPicker">
                <div class="s-rolletsCalc__header">Выберите цвет:</div>
                <div class="s-rolletsCalc__colors">
                    @foreach ($calcProducts as $calcProduct)
                        <button type="button" class="s-rolletsCalc__color {{ $loop->first ? 'active' : '' }}" data-img="{{ $calcProduct['image'] }}">
                            <img src="{{ $calcProduct['image'] }}" alt="{{ $calcProduct['title'] }}">
                            <span>{{ $calcProduct['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Размеры -->
            <div class="s-rolletsCalc__sizePicker">
                <div class="s-rolletsCalc__header">Укажите размеры:</div>
                <div class="s-rolletsCalc__inputs">
                    <label class="s-rolletsCalc__label">
                        Ширина, мм
                        <input type="number" name="rl_width" min="500" max="6000" value="900" class="s-rolletsCalc__input">
                    </label>
                    <label class="s-rolletsCalc__label">
                        Высота, мм
                        <input type="number" name="rl_height" min="500" max="6000" value="900" class="s-rolletsCalc__input">
                    </label>
                </div>
            </div>

            <!-- Назначение -->
            <div class="s-rolletsCalc__purpose">
                <div class="s-rolletsCalc__header">Назначение:</div>
                <div class="s-rolletsCalc__checkboxes">
                    <div class="s-rolletsCalc__checkbox">
                        <input type="checkbox" id="vandal" name="vandal" value="Антивандальные (от хулиганов)" checked>
                        <label for="vandal">Антивандальные (от хулиганов)</label>
                    </div>
                    <div class="s-rolletsCalc__checkbox">
                        <input type="checkbox" id="shum" name="shum" value="От шума и/или света">
                        <label for="shum">От шума и/или света</label>
                    </div>
                    <div class="s-rolletsCalc__checkbox">
                        <input type="checkbox" id="vzlom" name="vzlom" value="Противовзломные">
                        <label for="vzlom">Противовзломные</label>
                    </div>
                    <div class="s-rolletsCalc__checkbox">
                        <input type="checkbox" id="other" name="other" value="Другое">
                        <label for="other">Другое</label>
                    </div>
                </div>


            </div>

            <div class="s-rolletsCalc__submit btn" data-modal="#rolletsCalc">Рассчитать стоимость</div>
        </div>

        <!-- Правая панель -->
        <div class="s-rolletsCalc__right">
            <div class="s-rolletsCalc__preview">
                @if ($activeCalcProduct)
                    <img class="s-rolletsCalc__previewImg" src="{{ $activeCalcProduct['image'] }}" alt="{{ $activeCalcProduct['title'] }}">
                @endif
            </div>
        </div>
    </div>
</section>

<style>
/* Калькулятор рольставен */
.s-rolletsCalc {
    margin-bottom: 80px;
}

.s-rolletsCalc__title {
    text-align: center;
    margin-bottom: 50px;
}

.s-rolletsCalc__container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    background: #f8f8f8;
    padding: 40px;
    border-radius: 12px;
}

.s-rolletsCalc__mobileSizes {
    display: none;
    grid-column: 1 / -1;
}

.s-rolletsCalc__header {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
}

.s-rolletsCalc__sizesBlock {
    display: flex;
    align-items: center;
    gap: 30px;
}

.s-rolletsCalc__inputs {
    display: flex;
    justify-content: space-between;
    width: 100%;
    align-items: center;
    /* flex-direction: column; */
    gap: 15px;
}

.s-rolletsCalc__inputs input {
    width: 100%;
}
.s-rolletsCalc__label {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 14px;
    color: #666;
    width: 100%;
}

.s-rolletsCalc__input {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.s-rolletsCalc__input:focus {
    outline: none;
    border-color: #007bff;
}

.s-rolletsCalc__sizesImg {
    max-width: 120px;
    height: auto;
}

.s-rolletsCalc__left {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.s-rolletsCalc__colorPicker {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.s-rolletsCalc__colors {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
}

.s-rolletsCalc__color {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s;
    border: 2px solid transparent;
    cursor: pointer;
    background: transparent;
    font: inherit;
}

.s-rolletsCalc__color:hover {
    background: #f0f0f0;
}

.s-rolletsCalc__color.active {
    border-color: #007bff;
    background: #e7f3ff;
}

.s-rolletsCalc__color img {
    width: 84px;
    height: 84px;
    object-fit: cover;
    border-radius: 4px;
}

.s-rolletsCalc__color span {
    font-size: 12px;
    color: #333;
    text-align: center;
}

.s-rolletsCalc__sizePicker {
    display: flex;
    flex-direction: column;
    /* gap: 20px; */
}

.s-rolletsCalc__checkboxes {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;    
    /* align-items: flex-start; */
}

/* .s-rolletsCalc__checkboxes {
    display: flex;
    flex-direction: column;
    gap: 12px;
} */

.s-rolletsCalc__checkbox {
    display: flex; 
    align-items: flex-start;
    gap: 10px;
}

.s-rolletsCalc__checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.s-rolletsCalc__checkbox label {
    font-size: 14px;
    color: #333;
    cursor: pointer;
}

.s-rolletsCalc__info {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.s-rolletsCalc__infoColumn {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.s-rolletsCalc__infoField {
    display: flex;
    align-items: center;
    gap: 10px;
}

.s-rolletsCalc__infoIcon {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
}

.s-rolletsCalc__infoValue {
    font-weight: 600;
    color: #333;
}

.s-rolletsCalc__colorName {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 14px;
}

.s-rolletsCalc__colorName span:first-child {
    color: #666;
}

.s-rolletsCalc__colorName span:last-child {
    font-weight: 600;
    color: #333;
}

.s-rolletsCalc__colorThumb {
    text-align: center;
}

.s-rolletsCalc__colorThumb img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.s-rolletsCalc__submit {
    padding: 15px 30px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.s-rolletsCalc__submit:hover {
    background: #0056b3;
}

.s-rolletsCalc__right {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.s-rolletsCalc__currentColor {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.s-rolletsCalc__currentColor span {
    color: #007bff;
}

.s-rolletsCalc__preview {
    position: relative;
    background: white;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e0e0e0;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.s-rolletsCalc__previewImg {
    max-width: 100%;
    width: 100%;
    max-height: 360px;
    object-fit: contain;
}

.s-rolletsCalc__widthIcon,
.s-rolletsCalc__heightIcon {
    position: absolute;
    width: 30px;
    height: 30px;
}

.s-rolletsCalc__widthIcon {
    top: 50%;
    left: 20px;
    transform: translateY(-50%);
}

.s-rolletsCalc__heightIcon {
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
}

.s-rolletsCalc__widthField,
.s-rolletsCalc__heightField {
    position: absolute;
    background: rgba(0, 123, 255, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.s-rolletsCalc__widthField {
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
}

.s-rolletsCalc__heightField {
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
}

.s-rolletsCalc__widthField div,
.s-rolletsCalc__heightField div {
    font-weight: 600;
    font-size: 14px;
}

/* Адаптивность */
@media (max-width: 768px) {
    .s-rolletsCalc__container {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 20px;
    }
    
    .s-rolletsCalc__mobileSizes {
        display: block;
    }
    
    .s-rolletsCalc__sizePicker {
        display: none;
    }
    
    .s-rolletsCalc__colors {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .s-rolletsCalc__widthIcon,
    .s-rolletsCalc__heightIcon {
        width: 20px;
        height: 20px;
    }
    
    .s-rolletsCalc__widthField,
    .s-rolletsCalc__heightField {
        font-size: 10px;
        padding: 6px 8px;
    }
    
    .s-rolletsCalc__widthField div,
    .s-rolletsCalc__heightField div {
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .s-rolletsCalc {
        margin-bottom: 40px;
    }
    
    .s-rolletsCalc__title {
        margin-bottom: 30px;
    }
    
    .s-rolletsCalc__container {
        padding: 15px;
    }
    
    .s-rolletsCalc__colors {
        grid-template-columns: 1fr;
    }
    
    .s-rolletsCalc__sizesBlock {
        flex-direction: column;
        gap: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка кликов по маленьким картинкам для смены большой картинки
    const colorOptions = document.querySelectorAll('.s-rolletsCalc__color');
    const previewImg = document.querySelector('.s-rolletsCalc__previewImg');
    
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Удаляем активный класс у всех элементов
            colorOptions.forEach(opt => opt.classList.remove('active'));
            
            // Добавляем активный класс текущему элементу
            this.classList.add('active');
            
            // Меняем большую картинку
            const newImgSrc = this.getAttribute('data-img');
            if (newImgSrc && previewImg) {
                previewImg.src = newImgSrc;
            }
        });
    });
});
</script>
