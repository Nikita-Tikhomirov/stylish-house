<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Page;
use App\Models\Subcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateNavigationMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('menu_structure')) {
            return;
        }

        $decoded = json_decode((string) $this->input('menu_structure'), true);
        $this->merge(['items' => is_array($decoded) ? $decoded : null]);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:80'],
            'items.*' => ['array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('items', []) as $index => $item) {
                $this->validateNode($validator, $item, "items.$index", 0, null);
            }
        });
    }

    private function validateNode(
        Validator $validator,
        mixed $node,
        string $path,
        int $depth,
        ?array $parent
    ): void {
        if (! is_array($node)) {
            $validator->errors()->add($path, 'Элемент меню должен быть объектом.');
            return;
        }

        $nodeType = (string) ($node['node_type'] ?? '');
        $placement = (string) ($node['placement'] ?? ($parent['placement'] ?? 'mega'));
        $label = trim((string) ($node['label'] ?? ''));

        if ($label === '' || mb_strlen($label) > 120) {
            $validator->errors()->add("$path.label", 'Укажите название длиной до 120 символов.');
        }

        if (! in_array($placement, ['mega', 'quick', 'utility'], true)) {
            $validator->errors()->add("$path.placement", 'Неизвестная область меню.');
        }

        $expectedType = match (true) {
            $depth === 0 && $placement === 'mega' => 'tab',
            $depth === 0 => 'link',
            $depth === 1 => 'section',
            $depth === 2 => 'link',
            default => null,
        };

        if ($nodeType !== $expectedType) {
            $validator->errors()->add("$path.node_type", 'Нарушена структура: вкладка → колонка → ссылка.');
        }

        $this->validateSource($validator, $node, $path, $nodeType);

        $children = $node['children'] ?? [];
        if (! is_array($children)) {
            $validator->errors()->add("$path.children", 'Дочерние элементы должны быть списком.');
            return;
        }

        if ($depth >= 2 && $children !== []) {
            $validator->errors()->add("$path.children", 'Ссылка не может содержать дочерние элементы.');
            return;
        }

        if ($depth === 0 && $placement !== 'mega' && $children !== []) {
            $validator->errors()->add("$path.children", 'Быстрая ссылка не может содержать дочерние элементы.');
            return;
        }

        foreach ($children as $index => $child) {
            $this->validateNode($validator, $child, "$path.children.$index", $depth + 1, [
                'node_type' => $nodeType,
                'placement' => $placement,
            ]);
        }
    }

    private function validateSource(Validator $validator, array $node, string $path, string $nodeType): void
    {
        $sourceType = $node['source_type'] ?? null;
        $sourceId = $node['source_id'] ?? null;
        $url = trim((string) ($node['url'] ?? ''));

        if ($nodeType === 'section' && ! $sourceType) {
            return;
        }

        if ($nodeType === 'tab' && ! $sourceType) {
            return;
        }

        if (! in_array($sourceType, ['category', 'subcategory', 'page', 'custom'], true)) {
            $validator->errors()->add("$path.source_type", 'Выберите источник ссылки.');
            return;
        }

        if ($sourceType === 'custom') {
            if (! $this->isInternalUrl($url)) {
                $validator->errors()->add("$path.url", 'Разрешены только внутренние ссылки, начинающиеся с /.');
            }
            return;
        }

        if (! is_numeric($sourceId) || ! $this->sourceExists($sourceType, (int) $sourceId)) {
            $validator->errors()->add("$path.source_id", 'Выбранная страница больше не существует.');
        }
    }

    private function isInternalUrl(string $url): bool
    {
        return $url === '#'
            || ($url !== '' && str_starts_with($url, '/') && ! str_starts_with($url, '//'));
    }

    private function sourceExists(string $sourceType, int $sourceId): bool
    {
        return match ($sourceType) {
            'category' => Category::query()->whereKey($sourceId)->exists(),
            'subcategory' => Subcategory::query()->whereKey($sourceId)->exists(),
            'page' => Page::query()->whereKey($sourceId)->exists(),
            default => false,
        };
    }
}
