<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\VideoReviews;


class videoReviewsController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'cover_image' => 'nullable|image|max:4096',
            'video' => 'required|mimes:mp4,mov,avi,flv,webm|max:512000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
        ]);

        $categoryId = $request->filled('category_id') ? $request->category_id : null;
        $subcategoryId = $request->filled('subcategory_id') ? $request->subcategory_id : null;
        if (!$categoryId && !$subcategoryId) {
            return response()->json(['message' => 'Нужно выбрать категорию или подкатегорию.'], 422);
        }

        $coverImagePath = $request->hasFile('cover_image')
            ? $request->file('cover_image')->store('video_reviews/covers', 'public')
            : null;
        $videoPath = $request->file('video')->store('video_reviews/videos', 'public');

        $videoReview = VideoReviews::create([
            'cover_image' => $coverImagePath,
            'video' => $videoPath,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
        ]);

        return response()->json(['videoReview' => $videoReview], 201);
    }


    public function update(Request $request, $id)
    {
        $videoReview = VideoReviews::findOrFail($id);
        $data = $request->validate([
            'cover_image' => 'nullable|image|max:4096',
            'video' => 'nullable|mimes:mp4,mov,avi,flv,webm|max:512000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
        ]);

        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('video_reviews/covers', 'public');
            $videoReview->cover_image = $coverImagePath;
        }

        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('video_reviews/videos', 'public');
            $videoReview->video = $videoPath;
        }

        $categoryId = $request->filled('category_id') ? $request->category_id : null;
        $subcategoryId = $request->filled('subcategory_id') ? $request->subcategory_id : null;
        if (!$categoryId && !$subcategoryId) {
            return response()->json(['message' => 'Нужно выбрать категорию или подкатегорию.'], 422);
        }

        $videoReview->title = $data['title'];
        $videoReview->description = $data['description'] ?? null;
        $videoReview->category_id = $categoryId;
        $videoReview->subcategory_id = $subcategoryId;
        $videoReview->save();

        return response()->json(['videoReview' => $videoReview]);
    }


    public function destroy($id)
    {
        $videoReview = VideoReviews::findOrFail($id);
        $videoReview->delete();

        return response()->json(['success' => true]);
    }
}
