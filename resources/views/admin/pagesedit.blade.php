<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Редактировать страницу {{ $page->h1 }}</h1>
        </div>
    </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Редактирование страницы</h5>
            <div class="card-body">
                <form action="{{ route('pages.update', $page->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="title">Заголовок</label>
                        <input type="text" name="title" id="title" class="form-control"
                            value="{{ $page->title ?? '' }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <input type="text" name="description" id="description" class="form-control"
                            value="{{ $page->description ?? '' }}" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control"
                            value="{{ $page->slug ?? '' }}" required>
                    </div>
                    <div class="form-group">
                        <label for="h1">Заголовок h1</label>
                        <input type="text" name="h1" id="h1" class="form-control"
                            value="{{ $page->h1 ?? '' }}" required>
                    </div>
                    <div class="form-group" style="color: #000 !important;">
                        <label for="seoEditor">Контент</label>
                        <button id="toggle-editor" type="button" style="margin-bottom: 10px;">Редактировать HTML</button>
                        
                        <div id="editor-container">
                            <div id="seoEditor">{!! $page->content !!}</div>
                            <textarea id="html-editor">{!! $page->content !!}</textarea>
                        </div>
                    
                        <input type="hidden" name="content" id="content" value="">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Обновить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<style>
    #html-editor {
        display: none;
        width: 100%;
        height: 300px;
        border: 1px solid #ccc;
        font-family: monospace;
        padding: 10px;
    }
</style>




<x-admin.footer></x-admin.footer>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
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

        let quill = new Quill('#seoEditor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow'
        });

        let toggleButton = document.getElementById("toggle-editor");
        let htmlEditor = document.getElementById("html-editor");
        let quillContainer = document.querySelector(".ql-container");
        let isHtmlMode = false;

        toggleButton.addEventListener("click", function() {
            if (!isHtmlMode) {
                // Переключаемся в HTML
                htmlEditor.value = quill.root.innerHTML;
                htmlEditor.style.display = "block";
                quillContainer.style.display = "none";
                toggleButton.textContent = "Редактировать в Quill";
            } else {
                // Переключаемся обратно в Quill
                quill.root.innerHTML = htmlEditor.value;
                htmlEditor.style.display = "none";
                quillContainer.style.display = "block";
                toggleButton.textContent = "Редактировать HTML";
            }
            isHtmlMode = !isHtmlMode;
        });
        // Перехват вставки изображений
        quill.getModule("toolbar").addHandler("image", function () {
            let input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("accept", "image/*");
            input.click();

            input.onchange = async function () {
                let file = input.files[0];
                if (file) {
                    let formData = new FormData();
                    formData.append("image", file);

                    try {
                        let response = await fetch("/admin/pages/upload-image", {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
                            }
                        });

                        let result = await response.json();
                        if (result.url) {
                            let range = quill.getSelection();
                            quill.insertEmbed(range.index, "image", result.url);
                        }
                    } catch (error) {
                        console.error("Ошибка загрузки изображения:", error);
                    }
                }
            };
        });

        quill.on("text-change", function () {
            document.getElementById("content").value = quill.root.innerHTML;
        });
    });
</script>
