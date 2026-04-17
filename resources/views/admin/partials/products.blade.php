@foreach ($products as $prod)
    <div class="catCard">
        <div class="catCard_catWrap">
            <div class="cardCell">{{ $prod->id }}</div>
            <div class="cardCell"><img src="{{ asset($prod->image_path) }}" alt=""></div>
            <div class="cardCell">
                <a href="{{ route('product.show', [
            'category_slug' => $prod->category->slug,
            'subcategory_slug' => $prod->subcategory->slug,
            'product_slug' => $prod->slug,
        ]) }}">{{ $prod->h1 }}</a>
            </div>
            <div class="cardCell" style="justify-content:center">
                {{ $prod->category->titleh1 ?? 'Без категории' }}
            </div>
            <div class="cardCell" style="justify-content:center">
                {{ $prod->subcategory->titleh1 ?? 'Без подкатегории' }}
            </div>
            <div class="cardCell control">
                <a href="{{ route('product.index', $prod->slug) }}" class="badge badge-primary">Редактировать</a>
            </div>
            <div class="cardCell control">
                <a href="{{ route('product.destroy', $prod->slug) }}"
                    class="badge badge-secondary delete-category">Удалить</a>
            </div>
        </div>
    </div>
@endforeach