<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SeoSection;
use App\Models\HomePage;
use App\Models\IconCard;
use App\Models\HomePageFaq;
use App\Models\Review;
use App\Models\Category;
use App\Models\FirstScreenSlider;
use App\Models\Product;
use App\Models\ProdModel;
use App\Services\CartService;
use App\Models\ThrouElement;
use App\Models\Slider;
use App\Models\Subcategory;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('index');
    }
    protected $cartService;

    protected function encodeAssetPath(?string $path): ?string
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

    protected function serializePreviewProduct(Product $product): array
    {
        return [
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
            'discount' => $product->discount,
            'min_price' => $product->min_price,
            'min_width' => $product->min_width,
            'min_height' => $product->min_height,
            'model' => $product->model?->title,
            'modelid' => $product->model_id,
            'cloth' => $product->cloth,
            'fabric_photo' => $this->encodeAssetPath($product->fabric_photo),
            'fabric_thumb_path' => $this->encodeAssetPath($product->fabric_thumb_path),
        ];
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $seoSection = SeoSection::firstOrFail();
        $homePageFields = HomePage::firstOrFail();
        $iconCards = IconCard::all();
        $faqs = HomePageFaq::all();
        $reviews = Review::all();
        $sliders = FirstScreenSlider::with('product')
            ->get()
            ->map(function ($slider) {
                $product = Product::leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
                    ->select('products.*', 'prod_model.title as model_title', 'prod_model.id as model_id')
                    ->where('products.id', $slider->product_id)
                    ->first();

                $slider->product = $product; // Обновляем продукт с дополнительными данными модели
                return $slider;
            });


        $homeActions = Product::where('home_actions', 'on')
            ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
            ->select('products.*', 'prod_model.title as model_title')
            ->with(['category', 'subcategory'])
            ->get();


        $categories = Category::all();
        $firstCategory = $categories->first();



        $homePopulars = Product::where('home_populars', 'on')
            ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
            ->select('products.*', 'prod_model.title as model_title')
            ->where('category_id', $firstCategory->id)
            ->with('category')
            ->get();


        // Получаем категории, подкатегории и товары, где show_in_catalog = true
        $categoriesInCatalogMenu = Category::where('show_in_catalog', true)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('show_in_catalog', true)
                        ->with([
                            'products' => function ($query) {
                                $query->where('show_in_catalog', true);
                            }
                        ]);
                }
            ])
            ->get();

        $categoriesInHeaderMenu = Category::where('show_in_menu', true)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('show_in_menu', true)
                        ->with([
                            'products' => function ($query) {
                                $query->where('show_in_menu', true);
                            }
                        ]);
                }
            ])
            ->get();

        $categories = Category::all();
        $models = ProdModel::all();

        $cart = $request->session()->get('cart', []);

        $headerInfo = ThrouElement::firstOrFail();


        //  Списки в подвал
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();

        $mainSlider = Slider::all();

        return view('front.home', compact(
            'seoSection',
            'homePageFields',
            'iconCards',
            'faqs',
            'reviews',
            'categoriesInCatalogMenu',
            'categoriesInHeaderMenu',
            'sliders',
            'homeActions',
            'homePopulars',
            'categories',
            'models',
            'cart',
            'headerInfo',
            'curtainSubcats',
            'blindSubcats',
            'mainSlider'
        ));
    }
    // Метод для AJAX-запросов, чтобы загружать товары для выбранной категории
    public function getProductsByCategory($categoryId)
    {
        $products = Product::where('home_populars', 'on')
            ->where('category_id', $categoryId)
            ->with(['category', 'subcategory', 'model'])
            ->get();

        return response()->json(
            $products->map(fn (Product $product) => $this->serializePreviewProduct($product))
        );
    }





}
