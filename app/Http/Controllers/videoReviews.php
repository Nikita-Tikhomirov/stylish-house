<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\VideoReview;




class videoReviews extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'cover_image' => 'required|image|max:2048',
        'video' => 'required|mimes:mp4,mov,avi,flv|max:100000000000000',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    $coverImagePath = $request->file('cover_image')->store('video_reviews/covers', 'public');
    $videoPath = $request->file('video')->store('video_reviews/videos', 'public');

    $videoReview = VideoReview::create([
        'cover_image' => $coverImagePath,
        'video' => $videoPath,
        'title' => $request->title,
        'description' => $request->description,
    ]);

    return response()->json(['videoReview' => $videoReview], 201);
}

public function update(Request $request, $id)
{
    $videoReview = VideoReview::findOrFail($id);

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
    $videoReview->save();

    return response()->json(['videoReview' => $videoReview]);
}

public function destroy($id)
{
    $videoReview = VideoReview::findOrFail($id);
    $videoReview->delete();

    return response()->json(['success' => true]);
}


}
