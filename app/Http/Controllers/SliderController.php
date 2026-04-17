<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;

class SliderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image_path' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url',
        ]);

        $imagePath = $request->file('image_path')->store('sliders', 'public');

        $slider = Slider::create([
            'image_path' => $imagePath,
            'title' => $request->title,
            'description' => $request->description,
            'link' => $request->link,
        ]);

        return response()->json(['success' => true, 'slider' => $slider], 201);
    }

    public function update(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);
    
        $request->validate([
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url',
        ]);
    
        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
            'link' => $request->link,
        ];
    
        if ($request->hasFile('image_path')) {
            $updateData['image_path'] = $request->file('image_path')->store('sliders', 'public');
        }
    
        $slider->update($updateData);
    
        return response()->json(['success' => true, 'slider' => $slider, 'image_path' => $updateData['image_path'] ]);
    }
    

    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);
        $slider->delete();

        return response()->json(['success' => true, 'message' => 'Slide deleted successfully.']);
    }
}
