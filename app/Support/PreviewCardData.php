<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProdModel;

class PreviewCardData
{
    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public static function fromProduct(Product $product, array $extra = []): array
    {
        $model = self::resolveProductModel($product);
        $categorySlug = $product->category?->slug;
        $subcategorySlug = $product->subcategory?->slug;

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
                'slug' => $subcategorySlug,
            ],
            'category_url' => $categorySlug
                ? CanonicalUrl::route('category.show', ['slug' => $categorySlug])
                : null,
            'subcategory_url' => $categorySlug && $subcategorySlug
                ? CanonicalUrl::route('subcategory.show', [
                    'category_slug' => $categorySlug,
                    'subcategory_slug' => $subcategorySlug,
                ])
                : null,
            'product_url' => $categorySlug && $subcategorySlug && $product->slug
                ? CanonicalUrl::route('product.show', [
                    'category_slug' => $categorySlug,
                    'subcategory_slug' => $subcategorySlug,
                    'product_slug' => $product->slug,
                ])
                : null,
            'price' => $product->price,
            'old_price' => $product->old_price,
            'discount' => $product->discount !== null ? (int) $product->discount : null,
            'min_price' => $product->min_price !== null ? (int) $product->min_price : null,
            'min_width' => $product->min_width !== null ? (int) $product->min_width : null,
            'min_height' => $product->min_height !== null ? (int) $product->min_height : null,
            'model' => $model?->title,
            'modelid' => $product->model_id,
            'model_title' => $model?->title,
            'model_id' => $product->model_id,
            'cloth' => $product->cloth,
            'fabric_photo' => self::encodeAssetPath($product->fabric_photo),
            'fabric_thumb_path' => self::encodeAssetPath($product->fabric_thumb_path),
        ], $extra);
    }

    protected static function resolveProductModel(Product $product): ?ProdModel
    {
        if ($product->relationLoaded('model')) {
            return $product->getRelation('model');
        }

        return $product->model_id ? ProdModel::find($product->model_id) : null;
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
