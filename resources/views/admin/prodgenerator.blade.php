<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Генератор товаров</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <div class="card-body">
                <form action="/admin/prodgenerator/generate" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method("PUT")
                    <div class="form-group">
                        <label for="title">Название товара</label>
                        <input id="title" name="title" type="text" class="form-control" value="">
                    </div>

                    <div class="form-group">
                        <label for="subcategory_id">Выберите подкатегорию</label>
                        <select id="subcategory_id" name="subcategory_id" class="form-control">
                            <option value="">-- Выберите подкатегорию --</option>
                            @foreach($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}">{{ $subcategory->titleh1 }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <select id="model" name="model" class="form-control">
                            <option value="">-- Выберите модель --</option>
                            @foreach ($models as $model)
                                <option value="{{ $model->id }}">{{ $model->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cloth">Материал (Категория)</label>

                        <select id="cloth" name="cloth" class="form-control">
                            <option value="0 категория">0
                                категория</option>
                            <option value="1 категория">1
                                категория</option>
                            <option value="2 категория">2
                                категория</option>
                            <option value="3 категория">3
                                категория</option>
                            <option value="4 категория">4
                                категория</option>
                            <option value="5 категория">5
                                категория</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="material">Материал</label>
                        <select id="material" name="material" class="form-control">
                            <!-- Опции будут добавлены через JavaScript -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="titlemeta">Заголовок (meta)</label>
                        <textarea class="form-control" name="titlemeta" id="titlemeta"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="descriptionmeta">Описание (meta)</label>
                        <textarea class="form-control" name="descriptionmeta" id="descriptionmeta"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="firstscreentext">Первый абзац</label>
                        <textarea class="form-control" name="firstscreentext" id="firstscreentext"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="parametrs">Характеристики</label>
                        <textarea class="form-control" name="characteristic" id="characteristic"></textarea>
                        <div id="characteristicqUill"></div>

                    </div>


                    <div class="form-group">
                        <label for="seotext">СЕО текст</label>
                        <textarea class="form-control" name="seotext" id="seotext" style="display: none"></textarea>
                        <div id="seoEditor"></div>
                    </div>

                    <div id="tabsContainer" style="margin-bottom: 25px;">
                        <div class="form-group">
                            <label for="tab1title">название Таб 1</label>
                            <textarea class="form-control" name="tab1title" id="tab1title"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tab1">Таб 1</label>
                            <textarea class="form-control" name="tab1" id="tab1"></textarea>
                        </div>
                                            <button id="addTabBtn" type="button" class="btn btn-primary">Добавить таб</button>
                    </div>





                    <button class="btn btn-primary" type="submit" id="generateBtn">Сохранить</button>


                </form>
            </div>

        </div>
    </div>
</div>

<script>
    let tabIndex = 1; // уже есть таб 1

    document.getElementById('addTabBtn').addEventListener('click', () => {
        tabIndex++;

        const container = document.getElementById('tabsContainer');

        const titleGroup = document.createElement('div');
        titleGroup.classList.add('form-group');
        titleGroup.innerHTML = `
      <label for="tab${tabIndex}title">название Таб ${tabIndex}</label>
      <textarea class="form-control" name="tab${tabIndex}title" id="tab${tabIndex}title"></textarea>
    `;

        const contentGroup = document.createElement('div');
        contentGroup.classList.add('form-group');
        contentGroup.innerHTML = `
      <label for="tab${tabIndex}">Таб ${tabIndex}</label>
      <textarea class="form-control" name="tab${tabIndex}" id="tab${tabIndex}"></textarea>
    `;

        container.appendChild(titleGroup);
        container.appendChild(contentGroup);
    });
</script>
<script>
    const materialsByCategory = {
        "0 категория": ["Шелк"],
        "1 категория": ["Дарина", "Мадагаскар", "Респект блэкаут", "Тэффи", "Сахара", "Дриада", "Ниагара", "Оливия", "Рябина", "Арабика", "Либерика", "Подсолнух", "Оливка", "Эмилия"],
        "2 категория": ["Аллегро перл", "Монако", "Дарина блэкаут", "Подсолнух блэкаут", "Оливка блэкаут", "Кейптаун ФР", "Респект ФР блэкаут", "Эмилия блэкаут", "Нуар", "Севилья", "Металлик", "Корсо", "Анже", "Дарина металлик", "Дарина перл", "Эклипс", "Кастелло"],
        "3 категория": ["Скрин 102", "Скрин К 304", "Скрин 311", "Скрин 3% 315", "Скрин 3% 317", "Севилья блэкаут", "Лусто", "Корсо перл", "Корсо блэкаут", "Анже блэкаут", "Калипсо", "Лэйси", "Ажур", "Форио"],
        "4 категория": ["Скрин алю 311", "Скрин Алю 312", "Скрин Алю 313", "Амальфи", "Баски димаут", "Шейд", "Ницца", "Палау"],
        "5 категория": ["Сиена", "Атико", "Лэйси блэкаут", "Ницца блэкаут", "Шерни"]
    };

    const clothSelect = document.getElementById('cloth');
    const materialSelect = document.getElementById('material');

    const updateMaterials = (category) => {
        materialSelect.innerHTML = "";
        (materialsByCategory[category] || []).forEach(material => {
            const option = new Option(material, material);
            materialSelect.add(option);
        });
    };

    clothSelect.addEventListener('change', () => updateMaterials(clothSelect.value));

    // Инициализация при загрузке
    updateMaterials(clothSelect.value);
</script>


<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

{{-- Сео текст --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            ['link', 'image', 'video', 'formula'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'list': 'check' }],
            [{ 'script': 'sub' }, { 'script': 'super' }],
            [{ 'indent': '-1' }, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['clean']
        ];

        var quill = new Quill('#seoEditor', {
            modules: { toolbar: toolbarOptions },
            theme: 'snow',
        });

        const form = document.querySelector('form');
        const seotext = document.getElementById('seotext');

        quill.on('text-change', function () {
            seotext.value = quill.root.innerHTML; // Передаем HTML-код в textarea
        });



        const characteristic = document.getElementById('characteristic');
        var quillChar = new Quill('#characteristicqUill', {
            modules: { toolbar: toolbarOptions },
            theme: 'snow',
        });

        quillChar.on('text-change', function () {
            characteristic.value = quillChar.root.innerHTML; // Передаем HTML-код в textarea

        });

        // form.addEventListener('submit', function() {
        //     document.getElementById('seotext').value = quill.root.innerHTML;
        // });
    });
</script>



<x-admin.footer></x-admin.footer>