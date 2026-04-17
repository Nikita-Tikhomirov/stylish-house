<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoReviews extends Model
{
    use HasFactory;
    protected $fillable = ['cover_image', 'title', 'video', 'description','category_id','subcategory_id'];
}
