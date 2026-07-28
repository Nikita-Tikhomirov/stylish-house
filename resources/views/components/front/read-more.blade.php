@props(['text', 'id'])

@if (filled(trim((string) $text)))
    <div class="prodForm__description readMore" data-read-more>
        <p id="{{ $id }}" class="readMore__content" data-read-more-content>{{ $text }}</p>
        <button class="readMore__toggle" type="button" data-read-more-toggle aria-controls="{{ $id }}"
            aria-expanded="false">Подробнее</button>
    </div>
@endif
