<div class="s-rollets-installation wrapper">
    <h2 style="margin-bottom: 30px;" class="s-rollets-installation__title title"> <span>Виды монтажа</span><svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
    </svg></h2>

    <div class="accardionJs">
        <div class="accardion__title">
            <div class="accardion__title-img">
                <img src="img/korob-vnutri.jpg" alt="Короб внутри (скрытый)" />
            </div>
            <div class="accardion__title-text">1. Короб внутри (скрытый)</div>
        </div>
        <div class="accardion__content">
            <div class="accardion__content-img">
                <img src="img/korob-vnutri-big.jpg" alt="Короб внутри (скрытый)" />
            </div>
            <div class="accardion__content-text">
                <p>Рольставни устанавливаются так, что короб находится внутри ниши и полностью скрыт от глаз. Это обеспечивает аккуратный и эстетичный вид, особенно важно для ванных комнат и туалетов. Рекомендуется монтировать до укладки плитки — так получится ровное и красивое соединение без щелей.</p>
                <p><strong>Плюсы:</strong></p>
                <ul>
                    <li>Эстетичный внешний вид, короб полностью скрыт</li>
                    <li>Идеально сочетается с интерьером ванной</li>
                    <li>Позволяет избежать щелей между направляющими и плиткой при установке до отделки</li>
                </ul>
                <p><strong>Минусы:</strong></p>
                <ul>
                    <li>Требует точных замеров и аккуратной установки</li>
                    <li>Размер короба может ограничивать пространство (обычно 137–180 мм)</li>
                    <li>Более сложный монтаж по сравнению с наружным вариантом</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="accardionJs">
        <div class="accardion__title">
            <div class="accardion__title-img">
                <img src="img/korob-snaruzhi.jpg" alt="Короб снаружи" />
            </div>
            <div class="accardion__title-text">2. Короб снаружи</div>
        </div>
        <div class="accardion__content">
            <div class="accardion__content-img">
                <img src="img/korob-snaruzhi-big.jpg" alt="Короб снаружи" />
            </div>
            <div class="accardion__content-text">
                <p>Короб размещается с внешней стороны ниши. Такой монтаж проще и быстрее, его можно делать в любое время, даже после отделки помещения. Размер короба зависит от высоты изделия, но при наружной установке он заметен.</p>
                <p><strong>Плюсы:</strong></p>
                <ul>
                    <li>Простота и скорость монтажа</li>
                    <li>Можно устанавливать в любое время, даже после укладки плитки</li>
                    <li>Не требуется точная подгонка под нишу</li>
                </ul>
                <p><strong>Минусы:</strong></p>
                <ul>
                    <li>Короб виден снаружи, что может влиять на эстетику</li>
                    <li>Менее аккуратное соединение с плиткой при последующем монтаже</li>
                    <li>Занимает немного больше пространства перед нишей</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<style>
    .s-rollets-installation .accardionJs p{
        background-color: transparent;
        color: #333;
        padding: 0;
    }
    .s-rollets-installation .accardionJs ul{
        margin-bottom: 20px;
    }
    .s-rollets-installation .accardionJs .accardion__content img{
        display: block;
        margin-bottom: 20px;
        max-width: 100%;
        height: auto;
    }
    .s-rollets-installation .accardion__title{
        display: flex;
        align-items: center;
        gap: 20px;
        justify-content: flex-start;
    }
    .s-rollets-installation .accardion__title-img{
        width: 50px;
        height: 50px;
        max-width: 50px;
        max-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .s-rollets-installation .accardion__title-img img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .s-rollets-installation .accardionJs .accardion__content p{
        line-height: 130%;
    }
    .s-rollets-installation .accardionJs .accardion__content ul {
        list-style: disc;
        list-style-position: inside;
    }
    .s-rollets-installation .accardionJs .accardion__content ul li{
        margin-bottom: 10px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Исправление для аккордеонов с вложенными элементами
    const accordionTitles = document.querySelectorAll('.accardion__title');
    
    accordionTitles.forEach(title => {
        // Находим все вложенные элементы в заголовке
        const childElements = title.querySelectorAll('*');
        
        // Добавляем обработчик клика на каждый дочерний элемент
        childElements.forEach(child => {
            child.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Симулируем клик на родительский заголовок
                title.click();
            });
        });
    });
});
</script>
