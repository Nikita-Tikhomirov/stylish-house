<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkExample extends Model
{
    use HasFactory;
    protected $fillable = ['image', 'thumb', 'title', 'description', 'category_id', 'subcategory_id'];
}
