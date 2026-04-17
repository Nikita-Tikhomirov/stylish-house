<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SeoSection;
use App\Models\HomePage;
use App\Models\IconCard;
use App\Models\HomePageFaq;
use App\Models\Review;
use App\Models\FirstScreenSlider;
use App\Models\Subcategory;
use App\Models\ProdModel;
use App\Models\Order;
use App\Models\Product;
use App\Models\Slider;







class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            $items = json_decode($order->items, true);
            if (!empty($items)) {
                $productIds = array_column($items, 'productId');
                $products = Product::whereIn('id', $productIds)->pluck('title', 'id');
                foreach ($items as &$item) {
                    $item['productName'] = $products[$item['productId']] ?? 'Название не найдено';
                }
                $order->items = $items;
            }
        }
        return view('admin.home', compact('orders'));


    }


    public function show()
    {
        // Получаем первую (и единственную) запись из таблицы SeoSection
        $seoSection = SeoSection::firstOrFail();
        $homePageFields = HomePage::firstOrFail();
        $iconCards = IconCard::all();
        $faqs = HomePageFaq::all();
        $reviews = Review::all();
        $sliders = FirstScreenSlider::all();
        $models = ProdModel::all();
        $mainSlider = Slider::all();

        // Передаем данные в шаблон
        return view('admin.homeEdit', compact('mainSlider', 'seoSection', 'homePageFields', 'iconCards', 'faqs', 'reviews', 'sliders', 'models'));
    }




    public function updateDeliveryText(Request $request)
    {
        $request->validate([
            'section_delivery_title' => 'required|string',
            'section_delivery_top_text' => 'required|string',
            'section_delivery_bottom_text' => 'required|string',
        ]);
        // Найдите или создайте экземпляр SeoSection
        $homePageFields = HomePage::firstOrFail();

        if (!$homePageFields) {
            $homePageFields = new HomePage();
        }

        // Обновите контент
        $homePageFields->update([
            'section_delivery_title' => $request->section_delivery_title,
            'section_delivery_top_text' => $request->section_delivery_top_text,
            'section_delivery_bottom_text' => $request->section_delivery_bottom_text,
        ]);

        // Ответ клиенту
        return response()->json(['message' => 'Контент успешно обновлен.']);
    }

    public function updateSectionRequest(Request $request)
    {
        $request->validate([
            'section_request_title' => 'required|string',
            'section_request_subtitle' => 'required|string',
            'section_request_text' => 'required|string',
        ]);
        // Найдите или создайте экземпляр SeoSection
        $homePageFields = HomePage::firstOrFail();

        if (!$homePageFields) {
            $homePageFields = new HomePage();
        }

        // Обновите контент
        $homePageFields->update([
            'section_request_title' => $request->section_request_title,
            'section_request_subtitle' => $request->section_request_subtitle,
            'section_request_text' => $request->section_request_text,
        ]);

        // Ответ клиенту
        return response()->json(['message' => 'Контент успешно обновлен.']);
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer|min:1|max:4',
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->input('status');
        $order->save();

        return response()->json(['success' => true, 'message' => 'Статус обновлен']);
    }

    public function savemeta(Request $request){
        $homePageFields = HomePage::first();
        // $homePageFields->meta_title = $request->meta_title;
        // $homePageFields->meta_description = $request->meta_description;


        $homePageFields->update([
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
 
        ]);

        return response()->json(['success' => 'Мета поля обновлены', 'message' => 'Мета поля обновлены',]);
    }

        public function destroyOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['success' => true, 'message' => 'Заказ успешно удален']);
    }

    

}
