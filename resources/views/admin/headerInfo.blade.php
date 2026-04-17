<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Редактировать Шапку и подвал</h1>
            {{-- {{ $subcategory->id }} --}}
        </div>
    </div>
</div>


<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <form action="{{ route('admin.header_info.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="logo_color">Цвет логотипа</label>
                        <input type="text" name="logo_color" id="logo_color"
                            value="{{ $headerInfo->logo_color ?? '' }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="working_hours">Время работы</label>
                        <input type="text" name="working_hours" id="working_hours"
                            value="{{ $headerInfo->working_hours ?? '' }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Номер телефона</label>
                        <input type="text" name="phone_number" id="phone_number"
                            value="{{ $headerInfo->phone_number ?? '' }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="address">Адрес</label>
                        <input type="text" name="address" id="address" value="{{ $headerInfo->address ?? '' }}"
                            class="form-control">
                    </div>



                    <div class="form-group">
                        <label for="text_after_logo">Текст под лого</label>
                        <textarea class="form-control" name="text_after_logo" id="text_after_logo" cols="30" rows="5">{{ $headerInfo->text_after_logo ?? '' }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Подкатегории штор</label>
                        <select name="curtain_subcategories[]" class="form-control" multiple style="min-height: 300px;">
                            @foreach ($curtainSubcats as $subcat)
                                <option value="{{ $subcat->id }}" @if (in_array($subcat->id, $headerInfo->curtain_subcategories ?? [])) selected @endif>
                                    {{ $subcat->titleh1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Подкатегории жалюзи</label>
                        <select name="blind_subcategories[]" class="form-control" multiple style="min-height: 300px;">
                            @foreach ($blindSubcats as $subcat)
                                <option value="{{ $subcat->id }}" @if (in_array($subcat->id, $headerInfo->blind_subcategories ?? [])) selected @endif>
                                    {{ $subcat->titleh1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>

            </div>
        </div>
    </div>
</div>
<x-admin.footer></x-admin.footer>
