<div class="formWrapper" id="call">
    <form class="form popupForm">
        <div class="form__title">Заказать звонок</div><label class="form__label">
            <p>Имя</p><input class="form__input" type="text" name="name" placeholder="Александр" required="required" />
        </label><label class="form__label">
            <p>Телефон</p><input class="form__input" type="text" name="phone" placeholder="+7(920)-113-46-44"
                required="required" />
        </label><label class="form__label">
            <p>Коментарий</p>
            <textarea class="form__input textarea" type="text" name="message" placeholder="Ваш коментарий"></textarea>
        </label><button class="form__btn btn">Заказать звонок</button>
    </form>
    <div class="ajaxMessage">
        <div class="ajaxMessage__success">
            <div class="ajaxMessage__title">
                <p>Спасибо!</p>
                <p>Ваша заявка принята</p>
            </div>
            <div class="ajaxMessage__text">Мы свяжемся с вами в ближайшее время, что бы обсудить детали и ответить
                на вопросы</div>
        </div>
        <div class="ajaxMessage__error">
            <div class="ajaxMessage__title">Ошибка при отправке!</div>
            <div class="ajaxMessage__text">Попробуйте позднее</div>
        </div><button class="btn closeModal">[object Object]</button>
    </div>
</div>

<div class="formWrapper" id="measure">
    <form class="form popupForm">
        <div class="form__title">Заказать замер</div><label class="form__label">
            <p>Имя</p><input class="form__input" type="text" name="name" placeholder="Александр"
                required="required" />
        </label><label class="form__label">
            <p>Телефон</p><input class="form__input" type="text" name="phone" placeholder="+7(920)-113-46-44"
                required="required" />
        </label><label class="form__label">
            <p>Коментарий</p>
            <textarea class="form__input textarea" type="text" name="message" placeholder="Ваш коментарий"></textarea>
        </label><button class="form__btn btn">Заказать замер</button>
    </form>
    <div class="ajaxMessage">
        <div class="ajaxMessage__success">
            <div class="ajaxMessage__title">
                <p>Спасибо!</p>
                <p>Ваша заявка принята</p>
            </div>
            <div class="ajaxMessage__text">Мы свяжемся с вами в ближайшее время, что бы обсудить детали и ответить
                на вопросы</div>
        </div>
        <div class="ajaxMessage__error">
            <div class="ajaxMessage__title">Ошибка при отправке!</div>
            <div class="ajaxMessage__text">Попробуйте позднее</div>
        </div><button class="btn closeModal">[object Object]</button>
    </div>
</div>

<div class="formWrapper" id="rolletsCalc">
    <form class="form popupForm">
        <div class="form__title">Рассчитать стоимость рольставен</div>

        <label class="form__label">
            <p>Имя</p><input class="form__input" type="text" name="name" placeholder="Александр" required="required" />
        </label>
        <label class="form__label">
            <p>Телефон</p><input class="form__input" type="text" name="phone" placeholder="+7(920)-113-46-44" required="required" />
        </label>
        <label class="form__label">
            <p>Комментарий</p>
            <textarea class="form__input textarea" type="text" name="message" placeholder="Опишите ваши требования"></textarea>
        </label>
        <!-- Скрытые поля для передачи данных из калькулятора -->
        <input type="hidden" name="width" id="calc-width">
        <input type="hidden" name="height" id="calc-height">
        <input type="hidden" name="color" id="calc-color">
        <input type="hidden" name="purposes" id="calc-purposes">
        <button class="form__btn btn">Рассчитать стоимость</button>
    </form>
    <div class="ajaxMessage">
        <div class="ajaxMessage__success">
            <div class="ajaxMessage__title">
                <p>Спасибо!</p>
                <p>Ваша заявка принята</p>
            </div>
            <div class="ajaxMessage__text">Мы свяжемся с вами в ближайшее время, что бы обсудить детали и ответить на вопросы</div>
        </div>
        <div class="ajaxMessage__error">
            <div class="ajaxMessage__title">Ошибка при отправке!</div>
            <div class="ajaxMessage__text">Попробуйте позднее</div>
        </div>
        <button class="btn closeModal">[object Object]</button>
    </div>
</div>

<div class="formWrapper prodPopup" id="popupProd">
    <div class="prodForm">

        {{-- <div class="prodForm__galleryWrap">
            <div class="prodForm__bar">
            </div>
            <div class="prodForm__imgWrap"> <img src="img/prod.jpg" alt="" /></div>
        </div> --}}

        <div class="prodForm__galleryWrapOuter">
            <div class="prodForm__galleryWrap">


                <div class="prodForm__imgWrap">
                    <img src="img/prod.jpg" alt="" />
                    <img src="img/prod.jpg" alt="" />

                </div>
                <div class="prodForm__bar">

                </div>

            </div>

{{-- 
            <div class="sidebarFilter__statusWrap filterColors colorsForCalc">
                <div class="sidebarFilter__labelText complectTitle">Цвет комплектации</div>

                <div class="sidebarFilter__paramsWrap">


                    <div class="radio-buttons">
                        <label class="radio-button active">
                            <input class="controlColor" type="radio" name="choice" value="#fff" checked />
                            <img src="img/fur.jpg" alt="Option 1">
                        </label>
                        <label class="radio-button">
                            <input class="controlColor" type="radio" name="choice" value="#000" />
                            <img src="img/fur.jpg" alt="Option 2">
                        </label>
                        <label class="radio-button">
                            <input class="controlColor" type="radio" name="choice" value="#eee" />
                            <img src="img/fur.jpg" alt="Option 3">
                        </label>
                    </div>




                </div>

            </div> --}}
        </div>

        <div class="prodForm__calcFormWrap">
            <div class="prodForm__formSubtitle">Товар 1</div>
            <div class="prodForm__formTitle">Заказать товар</div>
            <div class="prodForm__description">
                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Minus incidunt sit quisquam illum
                    pariatur vitae est cumque ea similique corporis deleniti, veritatis culpa! Saepe fugiat
                    quibusdam voluptas ratione quaerat voluptatibus...</p><span class="more">Подробнее</span>
            </div>
            <div class="prodForm__sizeWrap">
                <label class="prodForm__label">
                    <p>Ширина, мм</p>
                    <input class="prodForm__input width-input" type="number" name="width" value="500"
                        required />
                </label>
                <label class="prodForm__label">
                    <p>Высота, мм</p>
                    <input class="prodForm__input height-input" type="number" name="height" value="500"
                        required />
                </label>
            </div>
            <div class="calcWidhType">
                <div class="cartForm__optionsList">
                    <div class="cartForm__listTitle">Тип замера </div>
                    <ul>
                        <li>
                            <label>
                                <input class="widthType" type="radio" name="widhType"
                                    value="Ширина по ткани" checked>
                                <span>Ширина по ткани</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input class="widthType" type="radio" name="widhType"
                                    value="Ширина по габариту">
                                <span>Ширина по габариту</span>

                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <select class="select-js side" name="select">
                <option value="Левое" selected="selected">Левое управление</option>
                <option value="Правое">Правое управление</option>
            </select>
            <div class="sidebarFilter" style="margin-bottom: 20px">
                <div class="sidebarFilter__paramsWrap">

                    <label class="sidebarFilter__label" for="control">
                        <input class="control" type="checkbox" id="control" name="control">
                        <span class="checkmark"><i class="fas fa-check" aria-hidden="true"></i></span>
                        <span class="sidebarFilter__labelText">Электропривод + пульт
                            управления</span>
                    </label>


                </div>
            </div>
            <input type="hidden" name="model" id="model" class="model">
            <input type="hidden" name="cloth" id="cloth" class="cloth">
            <input type="hidden" name="discount" id="discount" class="discount">
            <input type="hidden" name="modelIdInput" id="modelIdInput" class="modelIdInput">



            <div class="prodForm__howMany"> <button class="minus">-</button><input type="text" placeholder="1"
                    value="1" class="quantity-input" /><button class="plus">+</button></div>
            <div class="prodForm__priceAndAddToCart">
                <div class="prodForm__price">Цена: 1200₽</div><button class="prodForm__addToCart"> Добавить в
                    корзину </button>
            </div>
        </div>
    </div>
</div>


<style>
    .radio-buttons {
        display: flex;
        gap: 5px;
    }

    .radio-button {
        cursor: pointer;
    }

    .radio-button img {
        display: block;
        width: 80px;
        height: 80px;
        object-fit: cover;
        border: 2px solid transparent;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .radio-button.active img {
        border: 2px solid #0989ff;
        /* Синий цвет выделения */
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        border-radius: 8px;
    }

    /* Скрываем радио-кнопки */
    .radio-button input[type="radio"] {
        display: none;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const radioButtons = document.querySelectorAll(".prodForm .radio-button");

        radioButtons.forEach((radioButton) => {
            const input = radioButton.querySelector("input[type='radio']");

            if (input.checked) {
                radioButton.classList.add("active");
            }

            radioButton.addEventListener("click", () => {
                // Снимаем активный класс у всех
                radioButtons.forEach((btn) => btn.classList.remove("active"));

                // Добавляем активный класс текущему
                radioButton.classList.add("active");
            });
        });
    });
</script>
