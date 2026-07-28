<div class="s-how wrapper">
    <div class="s-how__bgWrap"> <img src="img/subscribe-shape-1.png" alt="" /></div>
    <div class="s-how__bgWrapBottom"><img src="img/subscribe-shape-1.png" alt="" /></div>
    <div class="s-how__textWrap">
        <div class="s-how__subtitle">{{ $subtitle }}</div>
        <h2 class="s-how__title">{{ $title }}</h2>
        <div class="s-how__text">{{ $text }}</div>
    </div>
    <form class="s-how__formWrap formAjax" id="contactForm">
        <div class="s-how__formTitle">Задать вопрос </div>
        <div class="s-how__inputsWrap">
            <label class="s-how__label">
                <span>Имя</span>
                <input class="s-how__input" type="text" name="name" placeholder="Андрей" required />
            </label>
            <label class="s-how__label">
                <span>Номер</span>
                <input class="s-how__input" type="text" name="phone" placeholder="+7 000 000-00-00" required />
            </label>
        </div>
        <label class="s-how__label">
            <span>Вопрос</span>
            <textarea class="s-how__input textarea" name="message" placeholder="Ваш вопрос" required></textarea>
        </label>
        <x-front.consent />
        <button type="submit" class="s-how__button btn">Отправить</button>
    </form>

</div>
