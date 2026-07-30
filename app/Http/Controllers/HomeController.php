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
use App\Models\VideoReviews;
use App\Support\PreviewCardData;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'getProductsByCategory']);
    }
    protected $cartService;

    protected function serializePreviewProduct(Product $product): array
    {
        return PreviewCardData::fromProduct($product);
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

        $categories = Category::all();
        $models = ProdModel::all();

        $cart = $request->session()->get('cart', []);

        $headerInfo = ThrouElement::firstOrFail();


        //  Списки в подвал
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();

        $mainSlider = Slider::all();
        $homeVideoReviews = VideoReviews::where('description', 'home_video')
            ->latest()
            ->get();

        return view('front.home', compact(
            'seoSection',
            'homePageFields',
            'iconCards',
            'faqs',
            'reviews',
            'sliders',
            'homeActions',
            'homePopulars',
            'categories',
            'models',
            'cart',
            'headerInfo',
            'curtainSubcats',
            'blindSubcats',
            'mainSlider',
            'homeVideoReviews'
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
