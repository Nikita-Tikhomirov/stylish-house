@php($isIndustrialGates = str_contains($subcategory->slug, 'promyshl'))
@php($gateProductKind = $isIndustrialGates ? 'промышленных ворот' : 'секционных ворот')
@php($gateProductTitle = $isIndustrialGates ? 'промышленные ворота' : 'секционные ворота')
@php($showTemplateCalculator = false)
@php($showSubcatSections = false)
@php($showRolletCalculator = true)
@php($showRolletProfilePrices = true)
@php($showSeoSection = true)
@php($showInstallationSection = false)
@php($showWorkExamplesEarly = true)
@php($showGateScheme = true)
@php($showDeliveryAfterSeo = true)
@php($calculatorTitle = 'Рассчитать стоимость ' . $gateProductKind)
@php($rolletCalculatorTitle = 'Рассчитать стоимость ' . $gateProductKind)
@php($rolletCalculatorProducts = !empty($firstProduct)
    ? \App\Models\Product::where('subcategory_id', $subcategory->clone_subcategory_id ?: $subcategory->id)
        ->where('model_id', $firstProduct->model_id)
        ->orderByRaw('id = ? desc', [$firstProduct->id])
        ->orderBy('id')
        ->get()
    : ($sameModelProducts ?? collect()))
@php($rolletProfilePricesTitle = 'Цены на ' . $gateProductTitle)
@php($rolletProfilePricesNote = $isIndustrialGates
    ? 'В таблицах указаны цены на популярные размеры промышленных ворот по профилям RH45N, RH58N и RH77M. Итоговая стоимость уточняется по размерам, комплектации и монтажу.'
    : 'В таблицах указаны цены на популярные размеры секционных ворот по профилям RH45N, RH58N и RH77M. Итоговая стоимость уточняется по размерам, комплектации и монтажу.')
@php($gateSchemeTitle = $isIndustrialGates ? 'Конструкция промышленных ворот' : 'Конструкция секционных ворот')
@php($gateSchemeParagraphs = $isIndustrialGates
    ? [
        'Промышленные ворота рассчитаны на интенсивную эксплуатацию на складах, производствах, сервисных зонах и логистических объектах. Полотно состоит из прочных сэндвич-панелей, соединенных петлями, и движется по направляющим вверх с переходом в горизонтальное положение под потолком.',
        'Такая конструкция помогает экономить пространство возле проема, выдерживает частые циклы открывания и закрывания, а сэндвич-панели повышают теплоизоляцию помещения и защищают рабочую зону от холода, ветра и сквозняков.',
    ]
    : [
        'Секционные ворота состоят из нескольких секций, выполненных из сэндвич-панелей. Секции соединяются между собой с помощью петель. Полотно ворот движется по направляющим сначала вертикально вверх, а затем располагается под потолком в горизонтальном положении. Благодаря такому способу открывания не требуется дополнительное пространство перед воротами или внутри помещения, как это необходимо для распашных моделей.',
        'Дополнительным преимуществом секционных ворот является высокая теплоизоляция. Сэндвич-панели содержат специальный наполнитель, который защищает от холода и сквозняков, помогая поддерживать комфортную температуру внутри помещения.',
    ])
@php($gateSchemeListTitle = $isIndustrialGates ? 'Основные элементы конструкции промышленных ворот:' : 'Основные элементы конструкции секционных ворот:')

@include('front.subcategory-template-1')
