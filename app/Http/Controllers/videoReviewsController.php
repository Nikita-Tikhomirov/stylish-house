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
        $request->validate([
            'cover_image' => 'required|image|max:2048',
            'video' => 'required|mimes:mp4,mov,avi,flv|max:100000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($request) {
                    if (!$request->has('subcategory_id') && !$value) {
                        $fail('Either category_id or subcategory_id must be provided.');
                    }
                }
            ],
            'subcategory_id' => [
                'nullable',
                'exists:subcategories,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->has('category_id') && !$value) {
                        $fail('subcategory_id must be provided if category_id is given.');
                    }
                }
            ],
        ]);

        $coverImagePath = $request->file('cover_image')->store('video_reviews/covers', 'public');
        $videoPath = $request->file('video')->store('video_reviews/videos', 'public');

        $videoReview = VideoReviews::create([
            'cover_image' => $coverImagePath,
            'video' => $videoPath,
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id ?? null,
            'subcategory_id' => $request->subcategory_id ?? null,
        ]);

        return response()->json(['videoReview' => $videoReview], 201);
    }


    public function update(Request $request, $id)
    {
        $videoReview = VideoReviews::findOrFail($id);

        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('video_reviews/covers', 'public');
            $videoReview->cover_image = $coverImagePath;
        }

        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('video_reviews/videos', 'public');
            $videoReview->video = $videoPath;
        }

        $videoReview->title = $request->title;
        $videoReview->description = $request->description;
        $videoReview->category_id = $request->category_id ?? $videoReview->category_id;
        $videoReview->subcategory_id = $request->subcategory_id ?? $videoReview->subcategory_id;
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
