<section class="sectionalScheme wrapper">
    <div class="sectionalScheme__media">
        <img src="{{ asset('img/sectional-gates-scheme.png') }}" alt="Конструкция секционных ворот">
    </div>
    <div class="sectionalScheme__content">
        <h2 class="sectionalScheme__title title">
            <span>Конструкция секционных ворот</span>
            <svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
            </svg>
        </h2>
        <div class="sectionalScheme__text">
            <p>Секционные ворота состоят из нескольких секций, выполненных из сэндвич-панелей. Секции соединяются между собой с помощью петель. Полотно ворот движется по направляющим сначала вертикально вверх, а затем располагается под потолком в горизонтальном положении. Благодаря такому способу открывания не требуется дополнительное пространство перед воротами или внутри помещения, как это необходимо для распашных моделей.</p>
            <p>Дополнительным преимуществом секционных ворот является высокая теплоизоляция. Сэндвич-панели содержат специальный наполнитель, который защищает от холода и сквозняков, помогая поддерживать комфортную температуру внутри помещения.</p>
        </div>
        <h3 class="sectionalScheme__subtitle">Основные элементы конструкции секционных ворот:</h3>
        <ol class="sectionalScheme__list">
            <li>Сэндвич-панель TECSEDO</li>
            <li>Барабан</li>
            <li>Конечное крепление пружины</li>
            <li>Вертикальная направляющая</li>
            <li>Угловая стойка</li>
            <li>Изгиб горизонтальной направляющей</li>
            <li>С-профиль</li>
            <li>Нижний уплотнитель</li>
            <li>Верхний уплотнитель</li>
            <li>Боковой уплотнитель</li>
            <li>Нижний и верхний профили</li>
            <li>Торсионная пружина</li>
            <li>Концевой опорный кронштейн</li>
            <li>Нижний угловой кронштейн</li>
            <li>Внутренняя петля</li>
            <li>Боковая опора</li>
            <li>Подшипник</li>
            <li>Ролик</li>
            <li>Стальной трос</li>
            <li>Вал</li>
            <li>Внутренний опорный кронштейн</li>
            <li>Усиленная задвижка</li>
            <li>Усиленная ручка</li>
            <li>Боковая крышка</li>
            <li>Монтажный уголок</li>
            <li>Задняя планка</li>
        </ol>
    </div>
</section>

<style>
    .sectionalScheme {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(360px, 0.92fr);
        gap: 38px;
        align-items: start;
        padding-top: 70px;
        padding-bottom: 50px;
    }

    .sectionalScheme__media {
        background: #f5f7f8;
        border: 1px solid #e2e8ee;
        border-radius: 8px;
        overflow: hidden;
    }

    .sectionalScheme__media img {
        display: block;
        width: 100%;
        height: auto;
    }

    .sectionalScheme__title {
        margin-bottom: 22px;
    }

    .sectionalScheme__text {
        color: #5d6874;
        font-size: 16px;
        line-height: 1.55;
    }

    .sectionalScheme__text p {
        margin: 0 0 14px;
    }

    .sectionalScheme__subtitle {
        margin: 22px 0 16px;
        font-size: 20px;
        font-weight: 700;
        color: #121820;
    }

    .sectionalScheme__list {
        columns: 2;
        column-gap: 30px;
        margin: 0;
        padding-left: 22px;
        color: #202832;
        font-size: 15px;
        line-height: 1.45;
    }

    .sectionalScheme__list li {
        break-inside: avoid;
        margin-bottom: 8px;
        padding-left: 4px;
    }

    @media (max-width: 1024px) {
        .sectionalScheme {
            grid-template-columns: 1fr;
            gap: 28px;
            padding-top: 48px;
        }

        .sectionalScheme__list {
            columns: 3;
        }
    }

    @media (max-width: 640px) {
        .sectionalScheme {
            padding-top: 36px;
            padding-bottom: 36px;
        }

        .sectionalScheme__text {
            font-size: 15px;
        }

        .sectionalScheme__subtitle {
            font-size: 18px;
        }

        .sectionalScheme__list {
            columns: 1;
        }
    }
</style>
