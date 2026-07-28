
<div id="map" class="s-map">

</div>

<script src="https://api-maps.yandex.ru/2.1/?apikey=9c6059ba-7962-4a66-8ebd-21b4e5bac044&lang=ru_RU"></script>

<script>
  ymaps.ready(init);

  function init() {
    const center = [55.812585, 37.698591]; // широта, долгота

    const map = new ymaps.Map("map", {
      center: center,
      zoom: 14,
      controls: ['zoomControl', 'fullscreenControl']
    });

    const placemark = new ymaps.Placemark(center, {
      balloonContentHeader: 'Stylish-house',
      balloonContentBody: 'Stylish-house г. Москва, ул Краснобогатырская 19а',
      balloonContentFooter: '<a href="tel:+79060609989">+79060609989</a>',
      hintContent: 'Stylish-house г. Москва, ул Краснобогатырская 19а',
    }, {
      preset: 'islands#redDotIconWithCaption',
      iconCaption: 'Stylish-house',
    });

    map.geoObjects.add(placemark);

    // Сразу открываем балун
    // placemark.balloon.open();
  }
</script>
