@props(['name' => 'privacy_consent'])

<label class="consentCheck">
    <input type="checkbox" name="{{ $name }}" value="1" required>
    <span>Я согласен с <a href="{{ \App\Support\CanonicalUrl::route('policy') }}" target="_blank" rel="noopener">политикой
            конфиденциальности</a></span>
</label>
