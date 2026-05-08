<?php

namespace App\Support;

use App\Models\Product;

class PreviewCardData
{
    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public static function fromProduct(Product $product, array $extra = []): array
    {
        return array_merge([
            'id' => $product->id,
            'slug' => $product->slug,
            'h1' => $product->h1,
            'image_path' => $product->image_path,
            'image_thumb_path' => $product->image_thumb_path,
            'category' => [
                'slug' => $product->category?->slug,
                'titleh1' => $product->category?->titleh1,
            ],
            'subcategory' => [
                'slug' => $product->subcategory?->slug,
            ],
            'price' => $product->price,
            'old_price' => $product->old_price,
            'discount' => $product->discount !== null ? (int) $product->discount : null,
            'min_price' => $product->min_price !== null ? (int) $product->min_price : null,
            'min_width' => $product->min_width !== null ? (int) $product->min_width : null,
            'min_height' => $product->min_height !== null ? (int) $product->min_height : null,
            'model' => $product->model?->title,
            'modelid' => $product->model_id,
            'cloth' => $product->cloth,
            'fabric_photo' => self::encodeAssetPath($product->fabric_photo),
            'fabric_thumb_path' => self::encodeAssetPath($product->fabric_thumb_path),
        ], $extra);
    }

    protected static function encodeAssetPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $cleanPath = ltrim($path, '/');
        $dir = dirname($cleanPath);
        $file = basename($cleanPath);

        return asset(($dir !== '.' ? $dir . '/' : '') . rawurlencode($file));
    }
}
