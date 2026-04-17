<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Создать страницу</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                <form action="{{ route('pages.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title">Заголовок</label>
                        <input type="text" name="title" id="title" class="form-control" value=""
                            required>
                    </div>
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <input type="text" name="description" id="description" class="form-control" value=""
                            required>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" value=""
                            required>
                    </div>
                    <div class="form-group">
                        <label for="h1">Заголовок h1</label>
                        <input type="text" name="h1" id="h1" class="form-control" value=""
                            required>
                    </div>
                    <div class="form-group" style="color: #000 !important">
                        {{-- <label for="content">Контент</label>
                        <textarea name="content" id="content" class="form-control">{{ $page->content ?? '' }}</textarea> --}}
                        <label for="seoEditor">Контент</label>
                        <div id="seoEditor"></div>
                        <input type="hidden" name="content" id="content">

                    </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-success">{{ isset($page) ? 'Обновить' : 'Создать' }}</button>
            </div>
            </form>

        </div>

    </div>
</div>
</div>



<x-admin.footer></x-admin.footer>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            ['link', 'image', 'video', 'formula'],
            [{
                'header': 1
            }, {
                'header': 2
            }],
            [{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }, {
                'list': 'check'
            }],
            [{
                'script': 'sub'
            }, {
                'script': 'super'
            }],
            [{
                'indent': '-1'
            }, {
                'indent': '+1'
            }],
            [{
                'direction': 'rtl'
            }],
            [{
                'size': ['small', false, 'large', 'huge']
            }],
            [{
                'header': [1, 2, 3, 4, 5, 6, false]
            }],
            [{
                'color': []
            }, {
                'background': []
            }],
            [{
                'font': []
            }],
            [{
                'align': []
            }],
            ['clean']
        ];

        var quill = new Quill('#seoEditor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow',
        });
        quill.on('text-change', function() {
            var content = quill.root.innerHTML;
            document.getElementById('content').value = content;
        });

    });
</script>
