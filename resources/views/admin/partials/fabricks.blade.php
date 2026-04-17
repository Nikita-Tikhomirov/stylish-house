@foreach ($fabricks as $fabrick)
    <div class="catCard">
        <div class="catCard_catWrap">
            <div class="cardCell">{{ $fabrick->id }}</div>
            <div class="cardCell"><img src="{{ Storage::url($fabrick->image) }}" alt=""></div>
            <div class="cardCell">
                <a href="">
                    {{ $fabrick->name }}
                </a>
            </div>

            <div class="cardCell control">
                <a href="{{ route('admin.fabrics.edit', $fabrick->id) }}" class="badge badge-primary">Редактировать</a>
            </div>
            <div class="cardCell control">

                <a href="{{ route('admin.fabrics.destroy', $fabrick->id) }}" class="badge badge-secondary delete-model">Удалить</a>
            </div>
        </div>
    </div>
@endforeach
