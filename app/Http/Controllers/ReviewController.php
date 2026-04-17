<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class ReviewController extends Controller
{


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Валидация изображения
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reviewData = $request->only(['title', 'text']);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public'); // Сохранение файла в 'public/avatars'
            $reviewData['avatar'] = $avatarPath;
        }

        $review = Review::create($reviewData);

        return response()->json(['success' => 'Отзыв успешно создан', 'review' => $review]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Валидация изображения
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = Review::findOrFail($id);
        $reviewData = $request->only(['title', 'text']);

        if ($request->hasFile('avatar')) {
            // Удаление старого аватара, если он существует
            if ($review->avatar) {
                Storage::disk('public')->delete($review->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public'); // Сохранение файла в 'public/avatars'
            $reviewData['avatar'] = $avatarPath;
        }

        $review->update($reviewData);

        return response()->json(['success' => 'Отзыв успешно обновлен', 'review' => $review]);
    }
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        if ($review->avatar) {
            Storage::disk('public')->delete($review->avatar);
        }
        $review->delete();

        return response()->json(['success' => true]);
    }
}
